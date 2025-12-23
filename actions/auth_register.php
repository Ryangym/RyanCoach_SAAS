<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Receber e Limpar dados
    // Usamos FILTER_SANITIZE_SPECIAL_CHARS pois STRING está obsoleto em versões novas do PHP
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // 2. Validação de Senha
    if ($senha !== $confirmar_senha) {
        echo "<script>alert('As senhas não conferem!'); window.history.back();</script>";
        exit;
    }

    // 3. Verifica se email já existe
    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Email já cadastrado! Tente fazer login.'); window.location.href='../login.php';</script>";
            exit;
        } 
        
        // 4. Preparação para Inserção (SaaS V2)
        
        // A) Hash da Senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // B) Gerar Código de Convite Único (Nome + 3 números aleatórios)
        // Ex: Se o nome for "João Silva", vira "JOAO492"
        $primeiro_nome = explode(' ', trim($nome))[0];
        // Remove acentos e caracteres especiais para o código ficar limpo
        $primeiro_nome = preg_replace('/[^A-Za-z0-9]/', '', $primeiro_nome);
        $codigo_gerado = strtoupper(substr($primeiro_nome, 0, 10)) . rand(100, 999);

        // 5. Inserir no Banco (Nova Estrutura)
        // Definimos 'atleta' e 'free' como padrão.
        $sql = "INSERT INTO usuarios 
                (nome, email, telefone, senha, tipo_conta, plano_atual, codigo_convite) 
                VALUES 
                (:nome, :email, :tel, :senha, 'atleta', 'free', :codigo)";
        
        $stmt = $pdo->prepare($sql);
        
        $sucesso = $stmt->execute([
            'nome' => $nome, 
            'email' => $email, 
            'tel' => $telefone, 
            'senha' => $senhaHash,
            'codigo' => $codigo_gerado
        ]);

        if ($sucesso) {
            echo "<script>alert('Cadastro realizado com sucesso! Bem-vindo ao time.'); window.location.href='../login.php';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar. Tente novamente.'); window.history.back();</script>";
        }

    } catch (PDOException $e) {
        // Se der erro de duplicidade no código de convite (raro, mas possível), avisa erro genérico
        echo "<script>alert('Erro no sistema: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}
?>