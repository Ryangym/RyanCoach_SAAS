<?php
// 1. Garante variáveis padrão
$foto_usuario = $_SESSION['user_foto'] ?? 'assets/img/icones/user-default.png';
$nome_usuario = $_SESSION['user_nome'] ?? 'Aluno';
$user_id      = $_SESSION['user_id'] ?? 0;

// 2. Formata o Nome (Primeiro nome em Maiúsculo)
$partes_nome = explode(' ', trim($nome_usuario));
$primeiro_nome = strtoupper($partes_nome[0]);

// 3. LÓGICA DO NÍVEL (PLANO)
// Verifica se a conexão $pdo existe (ela deve vir do arquivo que inclui a sidebar)
$plano_atual = 'start'; // Padrão

if (isset($pdo) && $user_id) {
    $stmt = $pdo->prepare("SELECT plano_atual FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $dado = $stmt->fetch(PDO::FETCH_ASSOC);
    $plano_atual = $dado['plano_atual'] ?? 'start';
}

// Define o Texto e a Cor baseada no plano
if ($plano_atual === 'pro') {
    $texto_level = 'Pro Member';
    $cor_level   = 'var(--gold)'; // Dourado
} else {
    $texto_level = 'Start Member';
    $cor_level   = '#ccc'; // Cinza/Prata
}
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
            <div class="status-indicator" style="background-color: <?php echo $cor_level; ?>"></div>
        </div>
        <p class="usuario-nome"><?php echo $primeiro_nome; ?></p>
        
        <p class="usuario-level" style="color: <?php echo $cor_level; ?>; font-weight:bold;">
            <?php echo $texto_level; ?>
        </p>
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