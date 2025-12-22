<?php
// Garante que a sessão esteja iniciada para verificar o login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica de Redirecionamento
$is_logged = isset($_SESSION['user_id']);
$painel_url = 'login.php'; // Padrão (se algo der errado)

// Define a foto da sessão ou usa um fallback genérico
$nav_foto = $_SESSION['user_foto'] ?? 'assets/img/user-default.png';

if ($is_logged) {
    // Se for admin, manda pro admin.php, senão pro usuario.php
    if (isset($_SESSION['user_nivel']) && $_SESSION['user_nivel'] === 'admin') {
        $painel_url = 'admin.php';
    } else {
        $painel_url = 'usuario.php';
    }
}
?>

<nav class="mobile-navbar">
    <button class="menu-toggle" id="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <img class="logo-mobilenav" src="assets/img/icones/icon-nav.png" alt="">

    <nav class="mobile-nav" id="mobile-nav">
        
        <?php if ($is_logged): ?>
            <button class="btnLogin-popup-mobile" onclick="window.location.href='<?php echo $painel_url; ?>'">
                <img src="<?php echo $nav_foto; ?>" alt="Painel">
                <p>Meu Painel</p>
            </button>
        <?php else: ?>
            <button class="btnLogin-popup-mobile" onclick="window.location.href='login.php'">
                <img src="assets/img/user-default.png" alt="Login">
                <p>Login</p>
            </button>
        <?php endif; ?>

        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="index.php#modalidades">Modalidades</a></li>
            <li><a href="index.php#ft-footer">Contato</a></li>
            <li><a href="planos.php">Planos</a></li>
        </ul>
        <div class="redes-sociais-mobile">
                <a href="https{SEU_LINK_WHATSAPP}" target="_blank" title="WhatsApp" class="whatsapp-link">
                    <img src="assets/img/icones/whatsapp-fill-svgrepo-com.svg" alt="WhatsApp">
                </a>
                <a href="https{SEU_LINK_INSTAGRAM}" target="_blank" title="Instagram" class="instagram-link">
                    <img src="assets/img/icones/instagram-fill-svgrepo-com.svg" alt="Instagram">
                </a>
                <a href="https{SEU_LINK_TIKTOK}" target="_blank" title="TikTok" class="tiktok-link">
                    <img src="assets/img/icones/tiktok-fill-svgrepo-com.svg" alt="TikTok">
                </a>
                <a href="https{SEU_LINK_TELEGRAM}" target="_blank" title="Telegram" class="telegram-link">
                    <img src="assets/img/icones/telegram-fill-svgrepo-com.svg" alt="Telegram">
                </a>
                <a href="https{SEU_LINK_YOUTUBE}" target="_blank" title="YouTube" class="youtube-link">
                    <img src="assets/img/icones/youtube-fill-svgrepo-com.svg" alt="YouTube">
                </a>
        </div>
    </nav>
</nav>

<nav class="desktop-navbar">
    <div class="glass-morphism">
        <a href="index.php" class="logo">
            <h2 class="logo">Ryan Coach</h2>
        </a>
        <div class="links-content">
            <a class="navlinks" href="index.php">Inicio</a>
            <a class="navlinks" href="ferramentas.php">Ferramentas</a>
            <a class="navlinks" href="planos.php">Planos</a>
        </div>

        <?php if ($is_logged): ?>
            
            <a href="<?php echo $painel_url; ?>" title="Ir para meu Painel" class="login-nav-link">
                <img src="<?php echo $nav_foto; ?>" alt="Meu Painel" class="login-nav">
            </a>

        <?php else: ?>

            <div style="position: relative;">
                <img src="assets/img/user-default.png" alt="Login" class="login-nav" id="userMenuToggle" style="cursor: pointer;">
                
                <div id="profileMenu" class="profile-dropdown-menu">
                    <div class="profile-card">
                        <ul class="profile-list">
                            <li class="profile-element">
                                <a href="login.php" class="profile-link">
                                    <i class="fa-regular fa-user" style="margin-right: 10px;"></i>
                                    <p class="profile-label">Entrar/Cadastro</p>
                                </a>
                            </li>
                            <li class="profile-element">
                                <a href="loginAdmin.php" class="profile-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-user-lock-icon lucide-user-lock">
                                        <circle cx="10" cy="7" r="4" />
                                        <path d="M10.3 15H7a4 4 0 0 0-4 4v2" />
                                        <path d="M15 15.5V14a2 2 0 0 1 4 0v1.5" />
                                        <rect width="8" height="5" x="13" y="16" rx=".899" />
                                    </svg>
                                    <p class="profile-label">Admin Login</p>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</nav>