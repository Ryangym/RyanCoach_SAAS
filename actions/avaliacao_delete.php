<?php
session_start();
require_once '../config/db_connect.php';

// 1. Verificação básica de sessão
if (!isset($_SESSION['user_id'])) { die("Acesso negado"); }

$id_avaliacao = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$meu_id = $_SESSION['user_id'];
$tipo_conta = $_SESSION['tipo_conta'] ?? '';

if ($id_avaliacao) {
    try {
        $pdo->beginTransaction();

        // 2. BUSCA DADOS DA AVALIAÇÃO E DO ALUNO DONO DELA
        // Fazemos um JOIN para saber quem é o 'coach_id' do aluno dono dessa avaliação
        $sql = "SELECT a.id, a.aluno_id, u.coach_id 
                FROM avaliacoes a 
                JOIN usuarios u ON a.aluno_id = u.id 
                WHERE a.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_avaliacao]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            die("Erro: Avaliação não encontrada.");
        }

        $aluno_id_dono = $dados['aluno_id'];
        $coach_id_dono = $dados['coach_id'];

        // 3. VERIFICAÇÃO DE PERMISSÃO (Hierarquia)
        $pode_apagar = false;

        if ($tipo_conta === 'admin') {
            // Admin apaga tudo
            $pode_apagar = true;
        } 
        elseif ($tipo_conta === 'coach') {
            // Coach só apaga se o aluno for dele
            if ($coach_id_dono == $meu_id) {
                $pode_apagar = true;
            }
        } 
        elseif ($tipo_conta === 'atleta') {
            // Atleta só apaga se for dele mesmo
            if ($aluno_id_dono == $meu_id) {
                $pode_apagar = true;
            }
        }

        if (!$pode_apagar) {
            die("Acesso Negado: Você não tem permissão para excluir esta avaliação.");
        }

        // 4. APAGAR ARQUIVOS FÍSICOS (Fotos/Vídeos)
        $stmt_arq = $pdo->prepare("SELECT caminho_ou_url, tipo FROM avaliacoes_arquivos WHERE avaliacao_id = ?");
        $stmt_arq->execute([$id_avaliacao]);
        $arquivos = $stmt_arq->fetchAll(PDO::FETCH_ASSOC);

        foreach ($arquivos as $arq) {
            // Só tentamos apagar se for arquivo local (tipo 'foto')
            // Se for 'video' geralmente é link do Youtube, então ignoramos o unlink
            if ($arq['tipo'] === 'foto') {
                // Caminho absoluto baseado na estrutura que definimos no add (assets/uploads/avaliacoes/nome.jpg)
                $file_path = __DIR__ . '/../assets/uploads/' . $arq['caminho_ou_url'];
                
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        // 5. APAGAR DO BANCO DE DADOS
        // O banco deve estar configurado com ON DELETE CASCADE nas chaves estrangeiras, 
        // mas o DELETE na tabela pai remove a avaliação.
        $stmt_del = $pdo->prepare("DELETE FROM avaliacoes WHERE id = ?");
        $stmt_del->execute([$id_avaliacao]);

        $pdo->commit();

        // 6. REDIRECIONAMENTO INTELIGENTE
        if ($tipo_conta === 'admin') {
            $url = "../admin.php?pagina=aluno_avaliacoes&id=$aluno_id_dono&msg=deleted";
        } elseif ($tipo_conta === 'coach') {
            $url = "../coach.php?pagina=aluno_avaliacoes&id=$aluno_id_dono&msg=deleted";
        } else {
            // Atleta
            $url = "../usuario.php?pagina=avaliacoes&msg=deleted";
        }
        
        header("Location: $url");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erro ao excluir: " . $e->getMessage();
    }
} else {
    echo "ID inválido.";
}
?>