<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) { die("Acesso negado"); }

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if ($id) {
    try {
        $pdo->beginTransaction();

        // 1. Identifica permissão e ID do Aluno (para redirecionar certo depois)
        // Só permite apagar se for o dono ou admin.
        if ($_SESSION['user_nivel'] === 'admin') {
            $stmt = $pdo->prepare("SELECT aluno_id FROM avaliacoes WHERE id = ?");
            $stmt->execute([$id]);
            $aluno_id = $stmt->fetchColumn();
        } else {
            $aluno_id = $_SESSION['user_id'];
            // Verifica se a avaliação pertence mesmo ao aluno logado
            $stmt = $pdo->prepare("SELECT id FROM avaliacoes WHERE id = ? AND aluno_id = ?");
            $stmt->execute([$id, $aluno_id]);
            if (!$stmt->fetch()) {
                die("Erro: Avaliação não encontrada ou sem permissão.");
            }
        }

        // 2. Apagar ARQUIVOS FÍSICOS (Fotos) do servidor
        $stmt_arq = $pdo->prepare("SELECT caminho_ou_url, tipo FROM avaliacoes_arquivos WHERE avaliacao_id = ?");
        $stmt_arq->execute([$id]);
        $arquivos = $stmt_arq->fetchAll(PDO::FETCH_ASSOC);

        foreach ($arquivos as $arq) {
            if ($arq['tipo'] === 'foto') {
                // Caminho completo do arquivo
                $file_path = __DIR__ . '/../assets/uploads/' . $arq['caminho_ou_url'];
                // Remove prefixo duplicado se houver (segurança)
                $file_path = str_replace('assets/uploads/avaliacoes/avaliacoes/', 'assets/uploads/avaliacoes/', $file_path);
                
                if (file_exists($file_path)) {
                    unlink($file_path); // Deleta do disco
                }
            }
        }

        // 3. Apagar do BANCO DE DADOS
        // O ON DELETE CASCADE do banco já apaga as linhas da tabela 'avaliacoes_arquivos' automaticamente
        $stmt_del = $pdo->prepare("DELETE FROM avaliacoes WHERE id = ?");
        $stmt_del->execute([$id]);

        $pdo->commit();

        // 4. Redirecionar
        $url = ($_SESSION['user_nivel'] === 'admin') 
            ? "../admin.php?pagina=aluno_avaliacoes&id=$aluno_id&msg=deleted" 
            : "../usuario.php?pagina=avaliacoes&msg=deleted";
        
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