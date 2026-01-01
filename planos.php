<?php
// planos.php - Tabela de Preços SaaS
require_once 'includes/head_main.php';
?>
<head>
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/planos.css">
</head>

<body class="saas-body">
    <a href="index.php">
        <nav class="saas-nav">
            <div class="logo-container">
                <img src="assets/img/icones/icon-nav.png" alt="Ryan Coach">
                <span class="title">Ryan Coach</span>
            </div>
            <a href="login.php" class="btn-login-nav">ENTRAR</a>
        </nav>
    </a>
    
    <section class="planos-header">
        <div class="container">
            <h1 class="hero-title" style="margin-bottom: 10px;">
                Escolha seu <span class="text-gold">Plano</span>
            </h1>
            <p class="hero-sub" style="margin-bottom: 0;">
                Atleta ou Treinador? Temos a ferramenta certa.
            </p>
        </div>
    </section>

    <section class="pricing-section">
        <div class="container pricing-grid">
            
            <div class="plan-card basic">
                <div class="card-header">
                    <span class="plan-name">ALUNO START</span>
                    <div class="plan-price">
                        <span class="currency">R$</span>
                        <span class="amount">19</span>
                        <span class="cents">,90</span>
                    </div>
                    <p class="plan-desc">O essencial para organizar seus treinos.</p>
                </div>
                
                <div class="card-body">
                    <ul class="feature-list">
                        <li><i class="fa-solid fa-dumbbell"></i> Criação de Treinos ilimitados</li>
                        
                        <li><i class="fa-solid fa-stopwatch"></i> Registro de Cargas e Cronômetro</li>
                        
                        <li><i class="fa-solid fa-ruler-combined"></i> Cadastro de Avaliações</li>
                        
                        <li class="disabled"><i class="fa-solid fa-chart-line"></i> Gráficos de Evolução</li>
                        
                        <li class="disabled"><i class="fa-solid fa-file-pdf"></i> Gerador de PDF</li>
                    </ul>
                    <a href="https://wa.me/5535999928473?text=Olá!%20Gostaria%20de%20assinar%20o%20plano%20ALUNO%20START%20(R$%2019,90)." 
                        target="_blank" class="btn-plan-basic">
                        <i class="fa-brands fa-whatsapp"></i> ASSINAR START
                    </a>
                </div>
            </div>

            <div class="plan-card pro">
                <div class="badge-popular">
                    <i class="fa-solid fa-fire"></i> MAIS VENDIDO
                </div>
                <div class="card-glow"></div>
                
                <div class="card-header">
                    <span class="plan-name">ALUNO PRO</span>
                    <div class="plan-price">
                        <span class="currency">R$</span>
                        <span class="amount">29</span>
                        <span class="cents">,90</span>
                    </div>
                    <p class="plan-desc">Ferramentas de atleta de elite.</p>
                </div>
                
                <div class="card-body">
                    <ul class="feature-list">
                        <li class="highlight"><i class="fa-solid fa-circle-check"></i> <span>Tudo do Plano Start</span></li>
                        
                        <li class="highlight"><i class="fa-solid fa-calendar-check"></i> <span>Ficha de Treino Periodizada</span></li>
                        
                        <li class="highlight"><i class="fa-solid fa-file-pdf"></i> <span>Gerador de PDF Premium</span></li>
                        
                        <li class="highlight"><i class="fa-solid fa-arrow-trend-up"></i> <span>Gráficos de Carga & Peso</span></li>
                        
                        <li class="highlight"><i class="fa-solid fa-clock-rotate-left"></i> <span>Histórico Vitalício</span></li>
                    </ul>
                    <a href="https://wa.me/5535999928473?text=Opa!%20Quero%20evoluir%20meu%20treino.%20Tenho%20interesse%20no%20ALUNO%20PRO%20(R$%2029,90)." 
                        target="_blank" class="btn-plan-pro">
                        <i class="fa-brands fa-whatsapp"></i> QUERO SER PRO
                    </a>
                </div>
            </div>

            <div class="plan-card coach">
                <div class="card-header">
                    <span class="plan-name">SOU PERSONAL</span>
                    <div class="plan-price">
                        <span class="currency">R$</span>
                        <span class="amount">9</span>
                        <span class="cents">,90</span>
                        <span class="per-student">/aluno</span>
                    </div>
                    <p class="plan-desc">Gestão completa para seus alunos.</p>
                </div>
                
                <div class="card-body">
                    <ul class="feature-list">
                        <li class="highlight"><i class="fa-solid fa-users"></i> <span>Alunos ilimitados</span></li>
                        <li class="highlight"><i class="fa-solid fa-laptop-file"></i> <span>Painel do Treinador</span></li>
                        <li><i class="fa-solid fa-file-invoice-dollar"></i> <span>Gestão Financeira</span></li>
                        <li><i class="fa-solid fa-file-pdf"></i> <span>Envio de PDF via Whats</span></li>
                        <li><i class="fa-solid fa-clipboard-check"></i> <span>Avaliação Física Completa</span></li>
                    </ul>
                    <a href="https://wa.me/5535999928473?text=Sou%20Personal%20Trainer%20e%20quero%20conhecer%20o%20plano%20para%20COACHS." 
                        target="_blank" class="btn-plan-coach">
                        <i class="fa-brands fa-whatsapp"></i> CRIAR CONTA COACH
                    </a>
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
                    <p>Teste a plataforma. Se não gostar, devolvemos seu dinheiro na hora.</p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

</body>
</html>