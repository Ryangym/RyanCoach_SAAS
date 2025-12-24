<?php
// 1. Configurações do Usuário
$foto_user = $_SESSION['user_foto'] ?? 'assets/img/user-default.png';
$nome_user = $_SESSION['user_nome'] ?? 'Usuário';
$partes_nome = explode(' ', trim($nome_user));
$primeiro_nome = strtoupper($partes_nome[0]);

// 2. Lógica de Estilo e Texto (Coach vs Admin)
$tipo_conta = $_SESSION['tipo_conta'] ?? 'coach'; // Pega da sessão

if ($tipo_conta === 'admin') {
    // Estilo ADMIN (Azul)
    $titulo_nivel = 'SUPER ADMIN';
    $cor_destaque = '#00a8ff'; // Azul vibrante
    $bg_badge = 'rgba(0, 168, 255, 0.1)'; // Azul transparente
    $menu_action = 'admin_menu'; // Caso você tenha um menu específico para admin
} else {
    // Estilo COACH (Vermelho Padrão)
    $titulo_nivel = 'MASTER COACH';
    $cor_destaque = '#ff4242'; // Vermelho padrão
    $bg_badge = 'rgba(255, 66, 66, 0.1)'; // Vermelho transparente
    $menu_action = 'coach_menu';
}
?>

<header class="mobile-top-bar">
    <div class="mobile-logo">Ryan Coach</div>
    <div class="mobile-user-actions">
        <img src="<?php echo $foto_user; ?>" alt="Perfil" class="mobile-profile-pic" style="border: 2px solid <?php echo $cor_destaque; ?>;">
        <button onclick="window.location.href='index.php'" class="mobile-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<aside id="main-aside">

    <div class="aside-header">
        <h2 class="logo">Ryan Coach</h2>
        
        <div class="profile-container">
            <img src="<?php echo $foto_user; ?>" alt="Profile" class="foto-perfil" style="border-color: <?php echo $cor_destaque; ?>;"> 
            <div class="status-indicator" style="background-color: <?php echo $cor_destaque; ?>;"></div>
        </div>
        
        <p class="usuario-nome"><?php echo $primeiro_nome; ?></p>
        
        <p class="usuario-level" style="color: <?php echo $cor_destaque; ?>; background: <?php echo $bg_badge; ?>;">
            <?php echo $titulo_nivel; ?>
        </p>
    </div>

    <nav class="nav-buttons">
        <button data-pagina="dashboard" class="active">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Visão Geral</span>
        </button>
            
        <button data-pagina="alunos">
            <i class="fa-solid fa-users"></i>
            <span>Gerenciar Alunos</span>
        </button>
            
        <button data-pagina="treinos_editor">
            <i class="fa-solid fa-dumbbell"></i>
            <span>Editor de Treinos</span>
        </button>
            
        <button data-pagina="financeiro">
            <i class="fa-solid fa-sack-dollar"></i>
            <span>Financeiro</span>
        </button>

        <!-- <button data-pagina="perfil" onclick="carregarConteudo('perfil')" class="desktop-only">
            <i class="fa-solid fa-gear"></i>
            <span>Configurações</span>
        </button> -->

        <button onclick="carregarConteudo('<?php echo $menu_action; ?>')" class="mobile-only">
            <i class="fa-solid fa-bars"></i>
            <span>Menu</span>
        </button>
    </nav>

    <div class="aside-footer">
        <button class="btn-logout" onclick="window.location.href='index.php'">
            <i class="fa-solid fa-globe"></i>
            <span>Ver Site</span>
        </button>
        <button data-pagina="logout" class="btn-logout" onclick="window.location.href='actions/logout.php'">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Sair</span>
        </button>
    </div>

</aside>

<!-- <style>
    .mobile-only { display: none !important; }
    @media (max-width: 768px) {
        .desktop-only { display: none !important; }
        .mobile-only { display: flex !important; }
    }
</style> -->