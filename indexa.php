<?php
// index.php - Red & Gold Edition
require_once 'includes/head_main.php';
?>
<head>
    <link rel="stylesheet" href="assets/css/stylea.css">
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
                    <a href="#metodo" class="btn-link-sec">Entender o Sistema</a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="phone-mockup">
                    <div class="phone-screen">
                        
                        <div class="screen-header">
                            <div class="screen-avatar"></div>
                            <div class="screen-badge">PRO</div>
                        </div>

                        <div class="screen-chart">
                            <div class="bar" style="height: 30%"></div>
                            <div class="bar" style="height: 50%"></div>
                            <div class="bar" style="height: 45%"></div>
                            <div class="bar active" style="height: 80%"></div>
                            <div class="bar" style="height: 65%"></div>
                        </div>

                        <div class="screen-card">
                            <i class="fa-solid fa-dumbbell"></i>
                            <div>
                                <strong style="color:#fff; display:block;">Supino Reto</strong>
                                <small>Carga: 40kg (↑ 5%)</small>
                            </div>
                        </div>

                        <div class="screen-card gold">
                            <i class="fa-solid fa-chart-line"></i>
                            <div>
                                <strong style="color:var(--gold); display:block;">Periodização</strong>
                                <small style="color:#fff;">Fase: Choque</small>
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

    <section class="section-features">
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
                    <p>Esqueça o bloco de notas. O sistema salva seu histórico e gera gráficos da sua evolução de força e peso.</p>
                </div>

                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3>Periodização Automática</h3>
                    <p>Organize macrociclos e microciclos. Saiba quando é semana de choque ou semana regenerativa.</p>
                </div>

                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-utensils"></i></div>
                    <h3>Dieta Inteligente</h3>
                    <p>Acesse seu plano alimentar completo com cálculo de macros e opções de substituição de alimentos.</p>
                </div>

                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-bolt"></i></div>
                    <h3>Técnicas Avançadas</h3>
                    <p>Suporte nativo para Drop-set, Rest-pause e Cluster. Registre cada quebra e falha com precisão.</p>
                </div>

                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-camera"></i></div>
                    <h3>Comparativo de Fotos</h3>
                    <p>Acompanhe sua transformação. Armazene fotos de "Antes e Depois", registre peso e medidas corporais.</p>
                </div>

                <div class="feature-card">
                    <div class="f-icon"><i class="fa-solid fa-circle-play"></i></div>
                    <h3>Biblioteca de Vídeos</h3>
                    <p>Nunca mais tenha dúvida na execução. Cada exercício possui vídeos demonstrativos integrados.</p>
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
                            <div>
                                <strong>Pedro S.</strong>
                                <span class="user-tag">Plano Pro</span>
                            </div>
                        </div>
                        <div class="stars">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </div>
                    <p>"Finalmente parei de usar o bloco de notas. O gerador de PDF é sensacional, imprimo minha ficha e levo pra academia com visual profissional."</p>
                </div>

                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <strong>Lucas M.</strong>
                                <span class="user-tag">Plano Pro</span>
                            </div>
                        </div>
                        <div class="stars">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </div>
                    <p>"A periodização automática mudou meu jogo. Saber exatamente quando aumentar a carga ou fazer deload me fez sair do platô."</p>
                </div>

                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <strong>Matheus R.</strong>
                                <span class="user-tag">Plano Start</span>
                            </div>
                        </div>
                        <div class="stars">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </div>
                    <p>"Sistema simples e direto. Sem enrolação. É entrar, ver o treino do dia, anotar a carga e treinar. Recomendo muito."</p>
                </div>

                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <strong>João V.</strong>
                                <span class="user-tag">Plano Pro</span>
                            </div>
                        </div>
                        <div class="stars">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                    </div>
                    <p>"Os gráficos de evolução dão uma motivação extra absurda. Ver a linha do peso subindo no supino é viciante."</p>
                </div>

            </div>
        </div>
    </section>

    <section class="section-cta-final">
        <div class="container" style="padding: 0;">
            <h2>Não perca mais tempo.</h2>
            <p>Acesse agora a plataforma e monte seu primeiro treino profissional.</p>
            <a href="planos.php" class="btn-cta-gold btn-large">VER PLANOS DISPONÍVEIS</a>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

</body>
</html>