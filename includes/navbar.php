<?php
// Garante sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Lógica de Redirecionamento e Foto
$is_logged = isset($_SESSION['user_id']);
$nav_foto = $_SESSION['user_foto'] ?? 'assets/img/user-default.png';

// Destino padrão (para quem não está logado)
$link_destino = 'login.php';

// Se estiver logado, muda o destino conforme o nível
if ($is_logged) {
    $tipo = $_SESSION['tipo_conta'] ?? '';
    
    switch ($tipo) {
        case 'admin':
            $link_destino = 'admin.php';
            break;
        case 'coach':
            $link_destino = 'coach.php';
            break;
        case 'atleta':
        case 'aluno':
            $link_destino = 'usuario.php';
            break;
        default:
            $link_destino = 'login.php'; // Segurança
    }
}
?>

<nav class="mobile-navbar">
    <button class="menu-toggle" id="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <a href="index.php">
        <img class="logo-mobilenav" src="assets/img/icones/icon-nav.png" alt="Ryan Coach">
    </a>

    <nav class="mobile-nav" id="mobile-nav">
        
        <button class="btnLogin-popup-mobile" onclick="window.location.href='<?php echo $link_destino; ?>'">
            <img src="<?php echo $nav_foto; ?>" alt="Perfil" style="border-radius: 50%; object-fit: cover;">
            <p><?php echo $is_logged ? 'Meu Painel' : 'Entrar'; ?></p>
        </button>

        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="ferramentas.php">Ferramentas</a></li>
            <li><a href="planos.php">Planos</a></li>
            <li><a href="#footer">Contato</a></li>
        </ul>

        <div class="redes-sociais-mobile">
             <a href="#" class="whatsapp-link"><img src="assets/img/icones/whatsapp-fill-svgrepo-com.svg" alt="Wpp"></a>
             <a href="#" class="instagram-link"><img src="assets/img/icones/instagram-fill-svgrepo-com.svg" alt="Insta"></a>
        </div>
    </nav>
</nav>

<nav class="desktop-navbar">
    <div class="glass-morphism">
        <a href="index.php" class="logo">
            <h2 class="logo">Ryan Coach</h2>
        </a>
        
        <div class="links-content">
            <a class="navlinks" href="index.php">Início</a>
            <a class="navlinks" href="ferramentas.php">Ferramentas</a>
            <a class="navlinks" href="planos.php">Planos</a>
        </div>

        <a href="<?php echo $link_destino; ?>" title="<?php echo $is_logged ? 'Ir para Painel' : 'Fazer Login'; ?>" class="login-nav-link" style="text-decoration: none;">
            <img src="<?php echo $nav_foto; ?>" alt="Perfil" class="login-nav" style="<?php echo $is_logged ? 'border: 2px solid var(--gold); padding: 2px;' : ''; ?>">
        </a>

    </div>
</nav>