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
        
        // Data de Expiração do Plano
        $data_exp = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : NULL;
        
        // Tipo de Conta (Validando 'coach' no lugar de 'personal')
        $tipo_conta = $_POST['tipo_conta'] ?? 'atleta'; 
        $tipos_permitidos = ['atleta', 'coach', 'admin'];
        
        // Se vier 'personal' do formulário antigo, força para 'coach'
        if ($tipo_conta === 'personal') {
            $tipo_conta = 'coach';
        }
        
        if (!in_array($tipo_conta, $tipos_permitidos)) {
            $tipo_conta = 'atleta';
        }

        $nova_senha = $_POST['nova_senha'] ?? '';

        // Query de Atualização
        $sql = "UPDATE usuarios SET 
                nome = :nome, 
                email = :email, 
                telefone = :telefone, 
                data_expiracao_plano = :dexp, 
                tipo_conta = :tipo_conta"; 
        
        $params = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'dexp' => $data_exp,
            'tipo_conta' => $tipo_conta,
            'id' => $id
        ];

        // Atualiza a senha se foi enviada
        if (!empty($nova_senha)) {
            $sql .= ", senha = :senha";
            $params['senha'] = password_hash($nova_senha, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Sucesso
        header("Location: ../admin.php?pagina=alunos&msg=sucesso");
        exit;

    } elseif ($acao === 'excluir') {
        // --- EXCLUIR USUÁRIO ---
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        if ($id == $_SESSION['user_id']) {
            die("Você não pode excluir sua própria conta.");
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: ../admin.php?pagina=alunos&msg=excluido");
        exit;
    }

} catch (PDOException $e) {
    echo "Erro SQL: " . $e->getMessage();
    exit;
}
?>