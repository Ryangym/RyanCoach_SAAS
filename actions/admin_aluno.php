<?php
session_start();
require_once '../config/db_connect.php';

// 1. Segurança: Apenas Admin pode acessar
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_conta'] !== 'admin') {
    die("Acesso negado.");
}

$acao = $_REQUEST['acao'] ?? '';

try {
    if ($acao === 'editar') {
        // --- EDITAR USUÁRIO ---
        
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
        
        // Data de Expiração
        $data_exp = !empty($_POST['data_expiracao_plano']) ? $_POST['data_expiracao_plano'] : NULL;
        
        // --- TIPO DE CONTA (Form -> Banco) ---
        $tipo_form = $_POST['tipo_conta'] ?? 'aluno';
        $tipo_conta = 'atleta'; // Padrão

        if ($tipo_form === 'aluno' || $tipo_form === 'atleta') $tipo_conta = 'atleta';
        if ($tipo_form === 'personal' || $tipo_form === 'coach') $tipo_conta = 'coach';
        if ($tipo_form === 'admin') $tipo_conta = 'admin';

        // --- NOVO: PLANO ATUAL ---
        $plano_form = $_POST['plano_atual'] ?? 'start';
        $plano_atual = 'start'; // Padrão

        // Validação (Whitelist)
        $planos_permitidos = ['start', 'pro', 'coach'];
        if (in_array($plano_form, $planos_permitidos)) {
            $plano_atual = $plano_form;
        }

        $nova_senha = $_POST['nova_senha'] ?? '';

        // Query de Atualização
        $sql = "UPDATE usuarios SET 
                nome = :nome, 
                email = :email, 
                telefone = :telefone, 
                data_expiracao_plano = :dexp, 
                tipo_conta = :tipo_conta,
                plano_atual = :plano_atual"; // Adicionado campo
        
        $params = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'dexp' => $data_exp,
            'tipo_conta' => $tipo_conta,
            'plano_atual' => $plano_atual, // Adicionado parametro
            'id' => $id
        ];

        // Atualiza senha se preenchida
        if (!empty($nova_senha)) {
            $sql .= ", senha = :senha";
            $params['senha'] = password_hash($nova_senha, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: ../admin.php?pagina=alunos&msg=sucesso");
        exit;

    } elseif ($acao === 'excluir') {
        // --- EXCLUIR USUÁRIO ---
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        if ($id == $_SESSION['user_id']) {
            header("Location: ../admin.php?pagina=alunos&msg=erro_proprio");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: ../admin.php?pagina=alunos&msg=excluido");
        exit;
    }

} catch (PDOException $e) {
    die("Erro SQL: " . $e->getMessage());
}
?>