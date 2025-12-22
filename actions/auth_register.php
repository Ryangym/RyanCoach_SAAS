<?php
session_start();
require_once '../config/db_connect.php'; // Ajuste o caminho se necessário (../config...)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Receber e Limpar dados
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING); // Novo campo
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha']; // Novo campo

    // 2. Validação de Senha
    if ($senha !== $confirmar_senha) {
        echo "<script>alert('As senhas não conferem!'); window.history.back();</script>";
        exit;
    }

    // 3. Verifica se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Email já cadastrado!'); window.location.href='../login.php';</script>";
    } else {
        // 4. Criar Hash e Salvar (Incluindo Telefone)
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nome, email, telefone, senha, nivel) VALUES (:nome, :email, :telefone, :senha, 'aluno')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute(['nome' => $nome, 'email' => $email, 'telefone' => $telefone, 'senha' => $senhaHash])) {
            echo "<script>alert('Cadastro realizado! Faça login.'); window.location.href='../login.php';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar.'); window.history.back();</script>";
        }
    }
}
?>