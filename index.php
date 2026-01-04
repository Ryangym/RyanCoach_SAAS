<?php
// index.php - Ryan Coach Landing Page
require_once 'includes/head_main.php';
?>
<head>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/menu.css">
</head>

<body class="saas-body">

    <nav class="saas-nav">
        <a href="index.php">
            <div class="logo-container">
                <img src="assets/img/icones/icon-nav.png" alt="Ryan Coach">
                <span class="title">Ryan Coach</span>
            </div>
        </a>
        <a href="login.php" class="btn-login-nav">ENTRAR</a>
    </nav>

    <header class="saas-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    Sua Evolução não é Sorte. <br>
                    <span class="text-gold">É Método.</span>
                </h1>
                <p class="hero-sub">
                    O sistema definitivo de gerenciamento de treinos. Periodização, controle de carga e análise de dados para quem busca alta performance.
                </p>
                <div class="hero-actions">
                    <a href="planos.php" class="btn-cta-gold pulse-animation">COMEÇAR AGORA</a>
                    <a href="#features" class="btn-link-sec">Ver Funcionalides</a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="phone-mockup">
                    <div class="mockup-scroll">
                        
                        <div class="clean-header-bg">
                            <div class="header-content-clean">
                                <div class="header-texts">
                                    <span class="greeting-sub">Painel do Atleta</span>
                                    <h1 class="greeting-main">Olá, <span style="color:var(--gold)">Ryan</span></h1>
                                </div>
                                <div class="header-avatar">
                                    <img> </div>
                            </div>
                            
                            <div class="status-bar-float">
                                <div class="sb-item">
                                    <i class="fa-solid fa-fire sb-icon fire"></i>
                                    <div class="sb-info">
                                        <strong>12</strong>
                                        <span>Dias seguidos</span>
                                    </div>
                                </div>
                                <div class="sb-divider"></div>
                                <div class="sb-item">
                                    <i class="fa-solid fa-weight-hanging sb-icon"></i>
                                    <div class="sb-info">
                                        <strong>12.5k</strong>
                                        <span>Volume Total</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dash-content-padded">
                            
                            <h3 class="section-label">HOJE</h3>
                            
                            <div class="today-card">
                                <div class="today-left">
                                    <span class="today-letter">A</span>
                                    <div class="today-info">
                                        <span class="badge-phase">CHOQUE</span>
                                        <h2>Treino A</h2>
                                        <p>Peito, Ombro e Tríceps</p>
                                    </div>
                                </div>
                                <div class="today-action">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                            </div>

                            <h3 class="section-label">ACESSO RÁPIDO</h3>
                            <div class="quick-grid">
                                <div class="quick-card">
                                    <div class="qc-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                    <span>Histórico</span>
                                </div>
                                <div class="quick-card">
                                    <div class="qc-icon"><i class="fa-solid fa-dumbbell"></i></div>
                                    <span>Minha Ficha</span>
                                </div>
                                <div class="quick-card">
                                    <div class="qc-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                                    <span>Avaliação</span>
                                </div>
                                <div class="quick-card">
                                    <div class="qc-icon"><i class="fa-solid fa-user-gear"></i></div>
                                    <span>Perfil</span>
                                </div>
                            </div>

                            <h3 class="section-label">CONSTÂNCIA</h3>
                            <div class="frequency-strip">
                                <div class="freq-header">
                                    <span>Esta Semana</span>
                                    <strong>4/5</strong>
                                </div>
                                <div class="week-pills">
                                    <div class="day-pill done">D</div>
                                    <div class="day-pill done">S</div>
                                    <div class="day-pill done">T</div>
                                    <div class="day-pill done">Q</div>
                                    <div class="day-pill current">Q</div>
                                    <div class="day-pill">S</div>
                                    <div class="day-pill">S</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="glow-effect"></div>
            </div>
        </div>
    </header>

    <div class="infinite-marquee">
        <div class="marquee-content">
            <span><i class="fa-solid fa-bolt"></i> METODOLOGIA RYAN COACH</span>
            <span><i class="fa-solid fa-dumbbell"></i> TREINO DE ALTA PERFORMANCE</span>
            <span><i class="fa-solid fa-chart-line"></i> EVOLUÇÃO CONSTANTE</span>
            <span><i class="fa-solid fa-bolt"></i> METODOLOGIA RYAN COACH</span>
            <span><i class="fa-solid fa-dumbbell"></i> TREINO DE ALTA PERFORMANCE</span>
            <span><i class="fa-solid fa-chart-line"></i> EVOLUÇÃO CONSTANTE</span>
        </div>
    </div>

    <section id="metodo" class="section-authority">
        <div class="container auth-grid">
            <div class="img-frame">
                <img src="assets/img/ryan_coach_atualizado-removebg-preview.png" alt="Ryan Coach" class="ryan-photo">
            </div>
            <div class="auth-text">
                <span class="quote-highlight">"Não existe atalho. Existe estratégia."</span>
                <h3>Performance é algo previsível.</h3>
                <p>
                    Criei o <strong>Ryan Coach App</strong> para resolver o maior problema de quem treina: a falta de organização. 
                    Sem anotação de carga, você não evolui. Sem periodização, você estagna.
                    <br><br>
                    Desenvolvi a plataforma que eu sempre quis ter. Uma ferramenta profissional, feita de quem treina para quem treina, para você ter total autonomia sobre sua evolução.
                </p>
            </div>
        </div>
    </section>

    <section class="section-features" id="features">
        <div class="container">
            <h2 class="section-title">O que o <span class="text-red">Sistema</span> faz por você?</h2>
            
            <div class="features-scroll">
                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <h3>Gerador de PDF Premium</h3>
                    <p>Leve sua ficha para a academia em um PDF profissional, com design limpo e fácil de ler no celular.</p>
                </div>
                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-chart-area"></i></div>
                    <h3>Controle de Cargas</h3>
                    <p>Esqueça o bloco de notas. O sistema mostra todas as cargas e repetições do ultimo treino junto com um cronômetro de descanso.</p>
                </div>
                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3>Periodização Automática</h3>
                    <p>Organize macrociclos e microciclos. Saiba quando é semana de choque ou semana regenerativa.</p>
                </div>
                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-utensils"></i></div>
                    <h3>Dieta Inteligente</h3>
                    <p>Cadastre um plano alimentar personalizado e adicione opções de substituição de alimentos.</p>
                </div>
                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                    <h3>Progresso de Avaliações</h3>
                    <p>Cadastre suas avaliações físicas, compare fotos e veja vários gráficos da sua evolução.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-reviews">
        <div class="container">
            <h2 class="section-title">Quem usa, <span class="text-gold">Evolui</span>.</h2>
            <div class="reviews-scroll">
                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                            <div><strong>Pedro S.</strong><span class="user-tag">Plano Pro</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"Finalmente parei de usar o bloco de notas. O gerador de PDF é sensacional, imprimo minha ficha e levo pra academia com visual profissional."</p>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                            <div><strong>Lucas M.</strong><span class="user-tag">Plano Pro</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"A periodização automática mudou meu jogo. Saber exatamente quando aumentar a carga ou fazer deload me fez sair do platô."</p>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                            <div><strong>Matheus R.</strong><span class="user-tag">Plano Start</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"Sistema simples e direto. Sem enrolação. É entrar, ver o treino do dia, anotar a carga e treinar. Recomendo muito."</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-cta-final">
        <div class="container" style="padding: 0;">
            <h2>Não perca mais tempo.</h2>
            <p>Acesse agora a plataforma e monte seu primeiro treino personalizado.</p>
            <a href="planos.php" class="btn-cta-gold btn-large">VER PLANOS DISPONÍVEIS</a>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

</body>