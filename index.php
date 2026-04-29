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

    <section id="demo-video" class="video-section">
        <div class="container">
            
            <div class="section-header-center">
                <h2 class="section-title">Veja o <span class="text-gold">Sistema</span> em Ação</h2>
                <p class="section-subtitle">Interface limpa, rápida e focada no seu resultado.</p>
            </div>

            <div class="video-wrapper">
                <div class="video-container">
                    <iframe 
                        src="https://www.youtube.com/embed/xiLG2PEPyL8?rel=0&modestbranding=1&controls=1&showinfo=0" 
                        title="Demonstração do Sistema Ryan Coach" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="video-glow"></div>
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
                <a href="https://www.instagram.com/joa0michel?igsh=azI1NDlrZ3M0ZGdw" class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><img src="assets/img/user-reviews/joaomichel.jpeg" alt=""></div>
                            <div><strong>João</strong><span class="user-tag">Plano Pro</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"Ryan tem me ajudado muito na perda de peso e no ganho de músculos nos treinos. As fichas de treino são muito boas, diretas, e com exercícios que a gente consegue fazer e, inclusive, até subir a carga.
Melhorei muito mesmo depois que comecei a acompanhar com ele, e recomendo pra caramba! O site motiva a gente a ir treinar todo dia, pra marcar a evolução e ver o progresso.
Só tenho a agradecer, tem me incentivado pra caramba"</p>
                </a>
                <a href="https://www.instagram.com/pedro_azvdo_?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><img src="assets/img/user-reviews/pedroazevedo.jpeg" alt=""></div>
                            <div><strong>Pedro Azevedo</strong><span class="user-tag">Plano Start</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"O treino é excelente, gosto bastante da dinâmica, nao fica pesado e é eficiente. O site é melhor ainda, a interface é muito intuitiva, pratica e facil de ser utilizada."</p>
                </a>
                <a href="https://www.instagram.com/josiel_borges199?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><img src="assets/img/user-reviews/josiel.jpeg" alt=""></div>
                            <div><strong>Josiel</strong><span class="user-tag">Plano Pro</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"Design bastante intuitivo e fácil de usar, realmente mudou minha forma de treinar e anotar meu progresso e isso acaba interferindo diretamente na minha evolução."</p>
                </a>
                <a href="https://www.instagram.com/rezende_vitor943?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><img src="assets/img/user-reviews/vitor.jpeg" alt=""></div>
                            <div><strong>Vitor</strong><span class="user-tag">Plano Start</span></div>
                        </div>
                        <div class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    </div>
                    <p>"Site genial e organizado, melhorou demais a minha rotina na academia, e os treinos então, foi o bum que precisava."</p>
                </a>
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