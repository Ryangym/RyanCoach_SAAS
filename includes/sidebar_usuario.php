<?php
$foto_usuario = $_SESSION['user_foto'] ?? 'assets/img/icones/user-default.png';
$nome_usuario = $_SESSION['user_nome'] ?? 'Aluno';
$partes_nome = explode(' ', trim($nome_usuario));
$primeiro_nome = strtoupper($partes_nome[0]); // Ex: "JOÃO"
?>
<header class="mobile-top-bar">
        <div class="mobile-logo">Ryan Coach</div>
        <div class="mobile-user-actions">
            <img src="<?php echo $foto_usuario; ?>" alt="Perfil" class="mobile-profile-pic">
            <button onclick="window.location.href='index.php'" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </div>
    </header>

    <aside id="main-aside">
        
        <div class="aside-header">
            <h2 class="logo">Ryan Coach</h2>
            <div class="profile-container">
                <img src="<?php echo $foto_usuario; ?>" alt="Foto de perfil" class="foto-perfil">
                <div class="status-indicator"></div>
            </div>
            <p class="usuario-nome"><?php echo $primeiro_nome; ?></p>
            <p class="usuario-level">Pro Member</p>
        </div>
        
        <nav class="nav-buttons">
            <button data-pagina="dashboard" class="active">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </button>
            
            <button onclick="abrirModalTreinos()">
                <i class="fa-solid fa-dumbbell"></i>
                <span>Meus Treinos</span>
            </button>
            
            <button data-pagina="dieta"> <i class="fa-solid fa-utensils"></i>
                <span>Dieta & Nutrição</span>
            </button>
            
            <button onclick="abrirModalAvaliacoes()">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>Avaliações</span>
            </button>
            
            <button data-pagina="menu">
                <i class="fa-solid fa-bars"></i>
                <span>Menu</span>
            </button>
        </nav>

        <div class="aside-footer">
            <button class="btn-logout" onclick="window.location.href='index.php'">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Voltar ao Inicio</span>
            </button>
            <button data-pagina="logout" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sair</span>
            </button>
        </div>

    </aside>