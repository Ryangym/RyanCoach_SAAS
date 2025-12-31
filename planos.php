<?php
// planos.php - Tabela de Preços SaaS
require_once 'includes/head_main.php';
?>
<head>
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/planos.css">
</head>

<body class="saas-body">

    <nav class="saas-nav">
        <div class="logo-container">
            <a href="index.php" style="text-decoration: none; color: #fff; display: flex; align-items: center; gap: 10px;">
                <img src="assets/img/icones/LOGO LIMPA.png" alt="Logo">
                <span>RYAN COACH</span>
            </a>
        </div>
        <a href="login.php" class="btn-login-nav">ÁREA DO ALUNO</a>
    </nav>

    <section class="planos-header">
        <div class="container">
            <h1 class="hero-title" style="font-size: 3rem; margin-bottom: 10px;">
                Escolha seu <span class="text-gold">Nível</span>
            </h1>
            <p class="hero-sub" style="color: #ccc;">
                Evolução constante. Sem fidelidade.
            </p>
        </div>
    </section>

    <section class="pricing-section">
        <div class="container pricing-grid">
            
            <div class="plan-card basic">
                <div class="card-header">
                    <span class="plan-name">START</span>
                    <div class="plan-price">
                        <span class="currency">R$</span>
                        <span class="amount">19</span>
                        <span class="cents">,90</span>
                    </div>
                    <p class="plan-desc">O essencial para começar a treinar.</p>
                </div>
                
                <div class="card-body">
                    <ul class="feature-list">
                        <li><i class="fa-solid fa-mobile-screen"></i> Acesso ao App</li>
                        <li><i class="fa-solid fa-clipboard-list"></i> 1 Ficha de Treino</li>
                        <li><i class="fa-solid fa-play"></i> Vídeos de Execução</li>
                        
                        <li class="disabled"><i class="fa-solid fa-xmark"></i> Gráficos de Evolução</li>
                        <li class="disabled"><i class="fa-solid fa-xmark"></i> Gerador de PDF</li>
                        <li class="disabled"><i class="fa-solid fa-xmark"></i> Periodização</li>
                    </ul>
                    <a href="actions/auth_register.php?plan=basic" class="btn-plan basic">COMEÇAR AGORA</a>
                </div>
            </div>

            <div class="plan-card pro">
                <div class="badge-popular">
                    <i class="fa-solid fa-fire"></i> MAIS VENDIDO
                </div>
                
                <div class="card-header">
                    <span class="plan-name">PRO ELITE</span>
                    <div class="plan-price">
                        <span class="currency">R$</span>
                        <span class="amount">29</span>
                        <span class="cents">,90</span>
                    </div>
                    <p class="plan-desc">Ferramentas de atleta profissional.</p>
                </div>
                
                <div class="card-body">
                    <ul class="feature-list">
                        <li><i class="fa-solid fa-infinity"></i> <span>Fichas Ilimitadas</span></li>
                        <li><i class="fa-solid fa-file-pdf"></i> <span>Gerador de PDF Premium</span></li>
                        <li><i class="fa-solid fa-chart-line"></i> <span>Gráficos de Carga & Peso</span></li>
                        <li><i class="fa-solid fa-calendar-days"></i> <span>Periodização Automática</span></li>
                        <li><i class="fa-solid fa-database"></i> <span>Histórico Vitalício</span></li>
                    </ul>
                    <a href="actions/auth_register.php?plan=pro" class="btn-plan pro">QUERO SER ELITE</a>
                </div>
            </div>

        </div>
    </section>

    <section class="faq-section">
        <div class="container">
            <div class="guarantee-seal">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <h3>Garantia Blindada de 7 Dias</h3>
                    <p>Teste a plataforma PRO. Se não gostar, devolvemos seu dinheiro na hora.</p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

</body>
</html>