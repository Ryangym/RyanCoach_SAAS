<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Sanitização dos dados recebidos
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);
    $senha = $_POST['senha'];
    
    // CAMPO NOVO: O código que o usuário digitou (ou veio pelo link)
    $codigo_recebido = filter_input(INPUT_POST, 'codigo_indicacao', FILTER_SANITIZE_STRING);

    // --- CONFIGURAÇÃO DE TESTE GRÁTIS ---
    // Define quantos dias o usuário ganha ao se cadastrar
    $dias_gratis = 30; 
    
    // Calcula a data de expiração (Hoje + X dias)
    $data_expiracao = date('Y-m-d', strtotime("+$dias_gratis days"));

    try {
        // 2. Verifica se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Email já cadastrado! Tente fazer login.'); window.location.href='../login.php';</script>";
            exit;
        } 
        
        // 3. LÓGICA DE INDICAÇÃO E VÍNCULO (O Coração do Sistema)
        $coach_id_novo = null;
        $indicado_por_novo = null;

        if (!empty($codigo_recebido)) {
            // Busca quem é o dono desse código
            $stmt = $pdo->prepare("SELECT id, tipo_conta FROM usuarios WHERE codigo_convite = :cod");
            $stmt->execute(['cod' => $codigo_recebido]);
            $padrinho = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($padrinho) {
                // CENÁRIO A: O dono do código é um COACH ou PERSONAL
                if ($padrinho['tipo_conta'] === 'coach' || $padrinho['tipo_conta'] === 'personal') {
                    $coach_id_novo = $padrinho['id'];    // Vira aluno dele
                    $indicado_por_novo = $padrinho['id']; // E conta como indicação dele
                } 
                // CENÁRIO B: O dono do código é um ATLETA (Indique um amigo)
                else {
                    $indicado_por_novo = $padrinho['id']; // Só conta como indicação
                    // $coach_id_novo continua null (o aluno entra 'sem professor' ou escolhe depois)
                }
            }
        }

        // 4. Preparação do Novo Usuário
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Gerar Código de Convite Único para esse novo usuário
        $primeiro_nome = explode(' ', trim($nome))[0];
        $primeiro_nome = preg_replace('/[^A-Za-z0-9]/', '', $primeiro_nome); // Limpa acentos
        $codigo_gerado = strtoupper(substr($primeiro_nome, 0, 10)) . rand(100, 999);

        // 5. Inserção no Banco
        // Atualizado para incluir 'data_expiracao_plano' e definir plano como 'start'
        $sql = "INSERT INTO usuarios 
                (nome, email, telefone, senha, tipo_conta, plano_atual, data_expiracao_plano, codigo_convite, coach_id, indicado_por) 
                VALUES 
                (:nome, :email, :tel, :senha, 'atleta', 'start', :data_exp, :codigo_proprio, :coach, :indicacao)";
        
        $stmt = $pdo->prepare($sql);
        
        $sucesso = $stmt->execute([
            ':nome' => $nome, 
            ':email' => $email, 
            ':tel' => $telefone, 
            ':senha' => $senhaHash,
            ':data_exp' => $data_expiracao, // Salva a data calculada (30 dias)
            ':codigo_proprio' => $codigo_gerado,
            ':coach' => $coach_id_novo,
            ':indicacao' => $indicado_por_novo
        ]);

        if ($sucesso) {
            echo "<script>alert('Cadastro realizado com sucesso! Você ganhou 30 dias grátis.'); window.location.href='../login.php';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar. Tente novamente.'); window.history.back();</script>";
        }

    } catch (PDOException $e) {
        echo "<script>alert('Erro no sistema: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}
?>