<?php
session_start();
require_once '../config/db_connect.php';

// --- CONFIGURAÇÕES ---
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
// Caminho absoluto para salvar
$upload_dir = __DIR__ . '/../assets/uploads/avaliacoes/';

// --- 1. VERIFICAÇÃO DE SEGURANÇA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo "<script>alert('ERRO: Arquivos excedem o limite do servidor.'); window.history.back();</script>";
    exit;
}

// --- 2. PERMISSÕES E IDENTIFICAÇÃO ---
if (!isset($_SESSION['user_id'])) { die("Acesso negado"); }

$tipo_conta = $_SESSION['tipo_conta'] ?? '';
$meu_id = $_SESSION['user_id'];

// Valida tipos permitidos
if (!in_array($tipo_conta, ['admin', 'coach', 'atleta'])) {
    die("Tipo de conta inválido ou não autorizado.");
}

// LÓGICA DE QUEM É O ALUNO E QUEM ESTÁ REGISTRANDO
// $registrado_por_id: Deve ser o ID do usuário logado (seja ele admin, coach ou atleta)
$registrado_por_id = $meu_id; 

if ($tipo_conta === 'atleta') {
    // 1. Atleta se auto-avaliando
    $aluno_id = $meu_id;
    $redirect_url = "../usuario.php?pagina=avaliacoes&msg=sucesso";

} else {
    // 2. Admin ou Coach avaliando alguém
    $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_SANITIZE_NUMBER_INT);
    
    if (!$aluno_id) die("Erro: ID do aluno não fornecido.");

    // Define URL de retorno
    if ($tipo_conta === 'admin') {
        $redirect_url = "../admin.php?pagina=aluno_avaliacoes&id=$aluno_id&msg=sucesso";
    } else {
        // É Coach
        $redirect_url = "../coach.php?pagina=aluno_avaliacoes&id=$aluno_id&msg=sucesso";
        
        // SEGURANÇA EXTRA COACH: Verifica se o aluno é dele
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND coach_id = ?");
        $stmtCheck->execute([$aluno_id, $meu_id]);
        if ($stmtCheck->rowCount() == 0) {
            die("Erro: Você só pode avaliar seus próprios atletas.");
        }
    }
}

// --- 3. PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arquivos_para_apagar = []; // Para rollback manual

    try {
        $pdo->beginTransaction();

        // Dados Básicos
        $data = $_POST['data_avaliacao'] ?? date('Y-m-d');
        $genero = $_POST['genero'] ?? 'M';
        $idade = filter_input(INPUT_POST, 'idade', FILTER_SANITIZE_NUMBER_INT);
        $obs = $_POST['observacoes'] ?? '';

        // Helper para limpar floats (Aceita virgula e ponto)
        function getFloat($key) {
            if (empty($_POST[$key])) return null;
            return (float) str_replace(',', '.', $_POST[$key]);
        }

        // Coleta Medidas
        $peso = getFloat('peso');
        $altura = getFloat('altura');
        $pescoco = getFloat('pescoco');
        $cintura = getFloat('cintura');
        $abdomen = getFloat('abdomen');
        $quadril = getFloat('quadril');

        // Cálculos (IMC, BF, Massas)
        $imc = null; $bf = null; $massa_gorda = null; $massa_magra = null;

        if ($peso && $altura) {
            $altura_m = $altura / 100;
            $imc = $peso / ($altura_m * $altura_m);
        }

        // Fórmula de Navy para BF
        if ($peso && $altura && $pescoco && $cintura) {
            if ($genero === 'M') {
                $circ_abd = $abdomen ?: $cintura;
                if (($circ_abd - $pescoco) > 0) {
                    $bf = 495 / (1.0324 - 0.19077 * log10($circ_abd - $pescoco) + 0.15456 * log10($altura)) - 450;
                }
            } elseif ($genero === 'F' && $quadril) {
                if (($cintura + $quadril - $pescoco) > 0) {
                    $bf = 495 / (1.29579 - 0.35004 * log10($cintura + $quadril - $pescoco) + 0.22100 * log10($altura)) - 450;
                }
            }
        }

        if ($bf && $peso) {
            $massa_gorda = $peso * ($bf / 100);
            $massa_magra = $peso - $massa_gorda;
        }

        // Inserção no Banco (CORREÇÃO: Campo registrado_por_id)
        $sql = "INSERT INTO avaliacoes (
            aluno_id, registrado_por_id, data_avaliacao, idade, genero, peso_kg, altura_cm,
            pescoco, ombro, torax_inspirado, torax_relaxado,
            braco_dir_relaxado, braco_esq_relaxado, braco_dir_contraido, braco_esq_contraido,
            antebraco_dir, antebraco_esq, cintura, abdomen, quadril,
            coxa_dir, coxa_esq, panturrilha_dir, panturrilha_esq,
            imc, percentual_gordura, massa_magra_kg, massa_gorda_kg, observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $aluno_id, $registrado_por_id, $data, $idade, $genero, $peso, $altura,
            $pescoco, getFloat('ombro'), getFloat('torax_inspirado'), getFloat('torax_relaxado'),
            getFloat('braco_dir_relaxado'), getFloat('braco_esq_relaxado'), getFloat('braco_dir_contraido'), getFloat('braco_esq_contraido'),
            getFloat('antebraco_dir'), getFloat('antebraco_esq'), $cintura, $abdomen, $quadril,
            getFloat('coxa_dir'), getFloat('coxa_esq'), getFloat('panturrilha_dir'), getFloat('panturrilha_esq'),
            $imc, $bf, $massa_magra, $massa_gorda, $obs
        ]);
        
        $avaliacao_id = $pdo->lastInsertId();

        // --- UPLOAD DE FOTOS ---
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        if (!empty($_FILES['fotos']['name'][0])) {
            $total = count($_FILES['fotos']['name']);
            for ($i = 0; $i < $total; $i++) {
                $name = $_FILES['fotos']['name'][$i];
                $tmp  = $_FILES['fotos']['tmp_name'][$i];
                $error = $_FILES['fotos']['error'][$i];
                $size = $_FILES['fotos']['size'][$i];

                if ($error !== UPLOAD_ERR_OK) {
                    if ($error === UPLOAD_ERR_NO_FILE) continue;
                    throw new Exception("Erro no upload da foto: $name");
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTS)) throw new Exception("Formato inválido: $name");
                if ($size > MAX_FILE_SIZE) throw new Exception("Foto muito grande: $name");

                // Gera nome único para evitar sobrescrever
                $new_name = 'av_' . $avaliacao_id . '_' . uniqid() . '.' . $ext;
                $dest = $upload_dir . $new_name;

                if (move_uploaded_file($tmp, $dest)) {
                    $arquivos_para_apagar[] = $dest; // Salva para caso precise desfazer
                    // Caminho relativo para salvar no banco (pasta 'avaliacoes/' já dentro de assets/uploads)
                    $db_path = 'avaliacoes/' . $new_name;
                    $pdo->prepare("INSERT INTO avaliacoes_arquivos (avaliacao_id, tipo, caminho_ou_url) VALUES (?, 'foto', ?)")->execute([$avaliacao_id, $db_path]);
                } else {
                    throw new Exception("Falha ao salvar arquivo no disco.");
                }
            }
        }

        // --- LINKS DE VÍDEO ---
        if (!empty($_POST['videos_links'])) {
            $links = explode(',', $_POST['videos_links']);
            foreach ($links as $l) {
                $l = trim($l);
                if (!empty($l)) {
                    $pdo->prepare("INSERT INTO avaliacoes_arquivos (avaliacao_id, tipo, caminho_ou_url) VALUES (?, 'video', ?)")->execute([$avaliacao_id, $l]);
                }
            }
        }

        $pdo->commit();
        header("Location: $redirect_url");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Limpa arquivos físicos se deu erro no banco
        foreach ($arquivos_para_apagar as $arq) {
            if (file_exists($arq)) unlink($arq);
        }

        // Log de erro para debug (opcional)
        error_log("Erro Avaliação Add: " . $e->getMessage());

        echo "<script>alert('ERRO: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}
?>