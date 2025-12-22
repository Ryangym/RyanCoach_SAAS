<?php
$foto_admin = $_SESSION['user_foto'] ?? 'assets/img/user-default.png';
$nome_admin = $_SESSION['user_nome'] ?? 'Admin';
$partes_admin = explode(' ', trim($nome_admin));
$primeiro_nome_admin = strtoupper($partes_admin[0]);
?>
<header class="mobile-top-bar">
        <div class="mobile-logo">Ryan Coach</div>
        <div class="mobile-user-actions">
            <img src="<?php echo $foto_admin; ?>" alt="Perfil" class="mobile-profile-pic">
            <button onclick="window.location.href='index.php'" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </div>
    </header>

    <aside id="main-aside">
    
    <div class="aside-header">
        <h2 class="logo">Ryan Coach</h2>
        <div class="profile-container">
            <img src="<?php echo $foto_admin; ?>" alt="Admin Profile" class="foto-perfil" style="border-color: #ff4242;"> 
            <div class="status-indicator" style="background-color: #ff4242;"></div>
        </div>
        <p class="usuario-nome"><?php echo $primeiro_nome_admin; ?></p>
        <p class="usuario-level" style="color: #ff4242; background: rgba(255, 66, 66, 0.1);">Master Coach</p>
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

        <button data-pagina="perfil" onclick="carregarConteudo('perfil')" class="desktop-only">
            <i class="fa-solid fa-gear"></i>
            <span>Configurações</span>
        </button>

        <button onclick="carregarConteudo('admin_menu')" class="mobile-only">
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

<style>
    .mobile-only { display: none !important; }
    @media (max-width: 768px) {
        .desktop-only { display: none !important; }
        .mobile-only { display: flex !important; }
    }
</style>