<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $tipo_login_esperado = $_POST['tipo_login'] ?? ''; 

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        
        // Verificações de Nível (Mantive sua lógica anterior)
        if ($tipo_login_esperado === 'admin' && $user['nivel'] !== 'admin') {
            echo "<script>alert('Acesso Negado! Alunos não têm permissão.'); window.location.href = '../login.php';</script>";
            exit;
        }
        if ($tipo_login_esperado === 'aluno' && $user['nivel'] === 'admin') {
             echo "<script>alert('Administradores devem usar o Painel Admin.'); window.location.href = '../loginAdmin.php';</script>";
            exit;
        }

        // --- AQUI ESTÁ A MÁGICA DA FOTO ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_nivel'] = $user['nivel'];
        
        // Se tiver foto no banco, usa ela. Se não, usa o bonequinho padrão.
        $_SESSION['user_foto'] = !empty($user['foto']) ? $user['foto'] : 'assets/img/user-default.png';

        // Redirecionamento
        if ($user['nivel'] === 'admin') {
            header("Location: ../admin.php");
        } else {
            header("Location: ../usuario.php");
        }
        exit;

    } else {
        echo "<script>alert('Email ou senha incorretos!'); window.history.back();</script>";
    }
}
?>