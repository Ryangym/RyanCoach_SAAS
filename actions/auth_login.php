<?php
session_start();
require_once '../config/db_connect.php';

// Limpa dados de entrada
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'];

if (empty($email) || empty($senha)) {
    header("Location: ../login.php?erro=campos_vazios");
    exit;
}

try {
    // Busca o usuário com as NOVAS colunas da V2
    $sql = "SELECT id, nome, email, senha, tipo_conta, plano_atual, foto, coach_id 
            FROM usuarios 
            WHERE email = :email";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se usuário existe e senha confere
    if ($user && password_verify($senha, $user['senha'])) {
        
        // --- SESSÃO ATUALIZADA PARA O SAAS ---
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_nome']    = $user['nome'];
        $_SESSION['user_email']   = $user['email'];
        $_SESSION['user_foto']    = $user['foto'];
        
        // Novas variáveis de controle
        $_SESSION['tipo_conta']   = $user['tipo_conta'];   // 'atleta', 'personal', 'admin'
        $_SESSION['plano_atual']  = $user['plano_atual'];  // 'free', 'pro', 'founder'
        $_SESSION['coach_id']     = $user['coach_id'];     // Se tiver um coach vinculado
        
        // Log de acesso (opcional, só para controle)
        // atualizar_ultimo_login($pdo, $user['id']); 

        // --- REDIRECIONAMENTO INTELIGENTE ---
        if ($user['tipo_conta'] === 'admin') {
            header("Location: ../admin.php");
        } else {
            // Tanto 'atleta' quanto 'personal' vão para a dashboard principal por enquanto
            // Lá dentro vamos mostrar coisas diferentes
            header("Location: ../usuario.php");
        }
        exit;

    } else {
        header("Location: ../login.php?erro=dados_invalidos");
        exit;
    }

} catch (PDOException $e) {
    // Em produção, evite mostrar o erro exato do banco
    header("Location: ../login.php?erro=sistema");
    exit;
}
?>