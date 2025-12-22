<?php 
session_start(); 
// Se o usuário tentar acessar essa página direto sem estar logado, manda pro login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include 'includes/head_main.php'; ?>
    <title>Acesso Expirado</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="lock-body">

    <div class="lock-card">
        
        <div class="lock-icon-wrapper">
            <i class="fa-solid fa-lock lock-icon"></i>
        </div>

        <h1 class="lock-title">Acesso Bloqueado</h1>
        
        <div class="lock-message">
            <p>Olá, <strong><?php echo htmlspecialchars($_SESSION['user_nome']); ?></strong>.</p>
            <p style="margin-top: 10px;">
                Seu plano de consultoria expirou ou está pendente de renovação. Para acessar seus treinos e dieta, regularize sua assinatura.
            </p>
        </div>

        <a href="https://wa.me/5535999928473?text=Olá Ryan, meu acesso está bloqueado. Gostaria de renovar!" target="_blank" class="btn-lock">
            <i class="fa-brands fa-whatsapp"></i> Falar com Suporte
        </a>

        <div class="lock-footer">
            <a href="actions/logout.php" class="lock-link">
                <i class="fa-solid fa-right-from-bracket"></i> Sair da conta
            </a>
        </div>

    </div>

</body>
</html>