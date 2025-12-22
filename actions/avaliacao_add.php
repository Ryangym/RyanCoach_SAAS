<?php
session_start();
require_once '../config/db_connect.php';

// --- CONFIGURAÇÕES ---
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB (Limite Lógico do Script)
const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$upload_dir = __DIR__ . '/../assets/uploads/avaliacoes/';

// --- 1. VERIFICAÇÃO CRÍTICA DE TAMANHO DO SERVIDOR ---
// Se o arquivo for maior que o post_max_size do PHP, $_POST e $_FILES chegam vazios.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo "<script>
            alert('ERRO CRÍTICO: Os arquivos enviados excedem o limite total do servidor!\\n\\nTente enviar menos fotos por vez ou fotos menores.');
            window.history.back();
          </script>";
    exit;
}

if (!isset($_SESSION['user_id'])) { die("Acesso negado"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arquivos_para_apagar = []; // Lista de rollback manual (arquivos físicos)

    try {
        $pdo->beginTransaction(); // Inicia a transação (nada é salvo de verdade até o commit)

        // ---------------------------------------------------------
        // A. DADOS GERAIS
        // ---------------------------------------------------------
        $registrado_por = ($_SESSION['user_nivel'] === 'admin') ? 'admin' : 'aluno';
        $aluno_id = ($registrado_por === 'admin') ? $_POST['aluno_id'] : $_SESSION['user_id'];
        
        $data = $_POST['data_avaliacao'] ?? date('Y-m-d');
        $genero = $_POST['genero'] ?? 'M';
        $idade = filter_input(INPUT_POST, 'idade', FILTER_SANITIZE_NUMBER_INT);
        $obs = $_POST['observacoes'] ?? '';

        function getFloat($key) {
            if (empty($_POST[$key])) return null;
            return (float) str_replace(',', '.', $_POST[$key]);
        }

        // Cálculos
        $peso = getFloat('peso');
        $altura = getFloat('altura');
        $pescoco = getFloat('pescoco');
        $cintura = getFloat('cintura');
        $abdomen = getFloat('abdomen');
        $quadril = getFloat('quadril');

        $imc = null; $bf = null; $massa_gorda = null; $massa_magra = null;

        if ($peso && $altura) {
            $altura_m = $altura / 100;
            $imc = $peso / ($altura_m * $altura_m);
        }

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

        // Inserir Avaliação (Ainda em memória da transação)
        $sql = "INSERT INTO avaliacoes (
            aluno_id, registrado_por, data_avaliacao, idade, genero, peso_kg, altura_cm,
            pescoco, ombro, torax_inspirado, torax_relaxado,
            braco_dir_relaxado, braco_esq_relaxado, braco_dir_contraido, braco_esq_contraido,
            antebraco_dir, antebraco_esq, cintura, abdomen, quadril,
            coxa_dir, coxa_esq, panturrilha_dir, panturrilha_esq,
            imc, percentual_gordura, massa_magra_kg, massa_gorda_kg, observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $aluno_id, $registrado_por, $data, $idade, $genero, $peso, $altura,
            $pescoco, getFloat('ombro'), getFloat('torax_inspirado'), getFloat('torax_relaxado'),
            getFloat('braco_dir_relaxado'), getFloat('braco_esq_relaxado'), getFloat('braco_dir_contraido'), getFloat('braco_esq_contraido'),
            getFloat('antebraco_dir'), getFloat('antebraco_esq'), $cintura, $abdomen, $quadril,
            getFloat('coxa_dir'), getFloat('coxa_esq'), getFloat('panturrilha_dir'), getFloat('panturrilha_esq'),
            $imc, $bf, $massa_magra, $massa_gorda, $obs
        ]);
        
        $avaliacao_id = $pdo->lastInsertId();

        // ---------------------------------------------------------
        // B. PROCESSAMENTO DE UPLOAD (AQUI QUE A GENTE PEGA O ERRO)
        // ---------------------------------------------------------
        
        // Verifica e cria pasta
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Erro interno: Falha ao criar pasta de uploads.");
            }
        }

        if (!empty($_FILES['fotos']['name'][0])) {
            $total_fotos = count($_FILES['fotos']['name']);

            for ($i = 0; $i < $total_fotos; $i++) {
                $nome_original = $_FILES['fotos']['name'][$i];
                $tmp_name      = $_FILES['fotos']['tmp_name'][$i];
                $error_code    = $_FILES['fotos']['error'][$i];
                $size          = $_FILES['fotos']['size'][$i];

                // 1. Verifica Erros do PHP (Tamanho, Interrupção, etc)
                if ($error_code !== UPLOAD_ERR_OK) {
                    if ($error_code === UPLOAD_ERR_NO_FILE) continue; // Campo vazio, ok

                    $msg_php = "Erro desconhecido";
                    switch ($error_code) {
                        case UPLOAD_ERR_INI_SIZE:   $msg_php = "A foto '$nome_original' excede o limite do servidor (upload_max_filesize)."; break;
                        case UPLOAD_ERR_FORM_SIZE:  $msg_php = "A foto '$nome_original' excede o limite do formulário."; break;
                        case UPLOAD_ERR_PARTIAL:    $msg_php = "O upload da foto '$nome_original' foi interrompido."; break;
                        case UPLOAD_ERR_NO_TMP_DIR: $msg_php = "Pasta temporária ausente no servidor."; break;
                    }
                    // Lança exceção para CANCELAR TUDO
                    throw new Exception($msg_php);
                }

                // 2. Valida Extensão
                $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTS)) {
                    throw new Exception("A foto '$nome_original' tem um formato inválido (.$ext). Use JPG, PNG ou WEBP.");
                }

                // 3. Valida Tamanho Manual (Backup)
                if ($size > MAX_FILE_SIZE) {
                    throw new Exception("A foto '$nome_original' é muito grande (" . round($size/1024/1024, 2) . "MB). O limite é 10MB.");
                }

                // 4. Move o Arquivo
                $novo_nome = 'av_' . $avaliacao_id . '_' . uniqid() . '.' . $ext;
                $caminho_final = $upload_dir . $novo_nome;

                if (move_uploaded_file($tmp_name, $caminho_final)) {
                    // Adiciona à lista para apagar caso dê erro depois
                    $arquivos_para_apagar[] = $caminho_final;

                    // Salva no Banco (Memória da Transação)
                    $caminho_db = 'avaliacoes/' . $novo_nome;
                    $stmt_f = $pdo->prepare("INSERT INTO avaliacoes_arquivos (avaliacao_id, tipo, caminho_ou_url) VALUES (?, 'foto', ?)");
                    $stmt_f->execute([$avaliacao_id, $caminho_db]);
                } else {
                    throw new Exception("Falha ao salvar o arquivo '$nome_original' no disco. Verifique as permissões da pasta.");
                }
            }
        }

        // ---------------------------------------------------------
        // C. VÍDEOS
        // ---------------------------------------------------------
        if (!empty($_POST['videos_links'])) {
            $links = explode(',', $_POST['videos_links']);
            foreach ($links as $l) {
                $l = trim($l);
                if (!empty($l)) {
                    $pdo->prepare("INSERT INTO avaliacoes_arquivos (avaliacao_id, tipo, caminho_ou_url) VALUES (?, 'video', ?)")->execute([$avaliacao_id, $l]);
                }
            }
        }

        // ---------------------------------------------------------
        // SUCESSO TOTAL: CONFIRMA TUDO
        // ---------------------------------------------------------
        $pdo->commit();
        
        $back_url = ($registrado_por === 'admin') 
            ? "../admin.php?pagina=aluno_avaliacoes&id=$aluno_id&msg=sucesso" 
            : "../usuario.php?pagina=avaliacoes&msg=sucesso";
            
        header("Location: $back_url");
        exit;

    } catch (Exception $e) {
        // ---------------------------------------------------------
        // ERRO DETECTADO: DESFAZ TUDO (ROLLBACK)
        // ---------------------------------------------------------
        $pdo->rollBack(); // Apaga o registro da avaliação do banco

        // Apaga as fotos que já tinham subido (Limpeza física)
        foreach ($arquivos_para_apagar as $arquivo) {
            if (file_exists($arquivo)) {
                unlink($arquivo);
            }
        }

        // Avisa o usuário e volta para a tela anterior
        echo "<script>
                alert('NÃO FOI POSSÍVEL SALVAR A AVALIAÇÃO!\\n\\nMotivo: " . addslashes($e->getMessage()) . "\\n\\nNenhuma alteração foi feita.');
                window.history.back();
              </script>";
        exit;
    }
}
?>