<?php
session_start();
require_once '../config/db_connect.php';

// Segurança: Apenas Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso negado.");
}

$acao = $_REQUEST['acao'] ?? ''; // Pode vir via POST (editar) ou GET (excluir)

try {
    if ($acao === 'editar') {
        // --- EDITAR ALUNO (INCLUINDO NÍVEL) ---
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
        $data_exp = $_POST['data_expiracao'] ?: NULL;
        $nivel = $_POST['nivel'] ?? 'aluno'; // Novo campo: aluno ou admin
        
        $nova_senha = $_POST['nova_senha'] ?? '';

        // Monta Query Dinâmica (para senha opcional)
        $sql = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone, data_expiracao = :dexp, nivel = :nivel";
        $params = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'dexp' => $data_exp,
            'nivel' => $nivel,
            'id' => $id
        ];

        if (!empty($nova_senha)) {
            $sql .= ", senha = :senha";
            $params['senha'] = password_hash($nova_senha, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: ../admin.php?pagina=alunos&msg=editado");
        exit;

    } elseif ($acao === 'excluir') {
        // --- EXCLUIR ALUNO ---
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // Evita que o admin se exclua
        if ($id == $_SESSION['user_id']) {
            die("Você não pode excluir sua própria conta.");
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: ../admin.php?pagina=alunos&msg=excluido");
        exit;
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>