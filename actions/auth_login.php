<?php
// Limpa qualquer lixo antes do JSON
ob_start();

session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'];

if (empty($email) || empty($senha)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo_conta, foto FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        
        // Cria a sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_foto'] = $user['foto'];
        $_SESSION['tipo_conta'] = $user['tipo_conta'];

        // --- Redirecionamento Estrito ---
        $redirect = 'usuario.php'; // Padrão (atleta)

        if ($user['tipo_conta'] === 'admin') {
            $redirect = 'admin.php';
        } 
        elseif ($user['tipo_conta'] === 'coach') {
            $redirect = 'coach.php';
        }
        
        ob_clean();
        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
        exit;

    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'E-mail ou senha incorretos.']);
        exit;
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Erro interno do sistema.']);
    exit;
}
?>