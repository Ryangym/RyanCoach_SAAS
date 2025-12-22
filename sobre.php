<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include 'includes/head_main.php'; ?>
    <title>Sobre o Coach - Ryan Coach</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/menu.css">
    
    <style>
        /* =========================================
           DESIGN SYSTEM: SOBRE (Dark Premium)
           ========================================= */
        
        body {
            background-color: #080808;
            /* Fundo com iluminação focada no centro */
            background-image: radial-gradient(circle at 50% 0%, rgba(255, 186, 66, 0.1) 0%, transparent 50%);
            background-attachment: fixed;
        }

        .about-wrapper {
            padding-top: 120px;
            padding-bottom: 80px;
            max-width: 1100px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        /* --- HERO SECTION (Foto + Bio Resumida) --- */
        .hero-split {
            display: flex;
            align-items: center;
            gap: 60px;
            margin-bottom: 100px;
        }

        .hero-image-container {
            flex: 1;
            position: relative;
        }

        .coach-photo {
            width: 100%;
            max-width: 450px;
            border-radius: 20px;
            /* Borda brilhante e sombra */
            border: 1px solid rgba(255, 186, 66, 0.3);
            box-shadow: 0 0 30px rgba(255, 186, 66, 0.15), 20px 20px 0px rgba(20, 20, 20, 0.8);
            filter: grayscale(20%) contrast(1.1);
            transition: 0.5s;
        }

        .coach-photo:hover {
            filter: grayscale(0%) contrast(1.2);
            transform: scale(1.02);
        }

        /* Elemento decorativo atrás da foto */
        .hero-image-container::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            width: 150px;
            height: 150px;
            border-top: 4px solid var(--gold);
            border-left: 4px solid var(--gold);
            z-index: -1;
        }

        .hero-text {
            flex: 1.2;
        }

        .hero-text h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3.5rem;
            line-height: 1;
            color: #fff;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .hero-text h1 span {
            color: transparent;
            -webkit-text-stroke: 1px var(--gold);
            display: block;
            font-size: 4.5rem;
            opacity: 0.8;
        }

        .hero-text h2 {
            font-size: 1.2rem;
            color: var(--gold);
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .hero-text p {
            color: #ccc;
            line-height: 1.8;
            font-size: 1.05rem;
            margin-bottom: 20px;
        }

        /* --- STATS GRID (Números) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 100px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px;
            text-align: center;
            border-radius: 15px;
            transition: 0.3s;
        }

        .stat-card:hover {
            background: rgba(255, 186, 66, 0.05);
            border-color: var(--gold);
            transform: translateY(-5px);
        }

        .stat-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            color: #fff;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #888;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* --- FILOSOFIA (Cards de Texto) --- */
        .philosophy-section {
            margin-bottom: 100px;
        }

        .section-title {
            text-align: center;
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            color: #fff;
            margin-bottom: 50px;
        }

        .cards-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .info-box {
            flex: 1;
            min-width: 300px;
            background: linear-gradient(145deg, #1a1a1a, #111);
            padding: 40px;
            border-radius: 20px;
            border-left: 4px solid var(--gold);
            position: relative;
        }

        .info-box i {
            font-size: 2rem;
            color: var(--gold);
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #fff;
            font-family: 'Orbitron', sans-serif;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .info-box p {
            color: #aaa;
            line-height: 1.6;
        }

        /* --- CTA FINAL --- */
        .final-cta {
            text-align: center;
            background: url('assets/img/background/1000097481.png') no-repeat center center;
            background-size: cover;
            padding: 80px 20px;
            border-radius: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Overlay escuro na imagem */
        .final-cta::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1;
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        /* --- Responsividade --- */
        @media (max-width: 900px) {
            .hero-split { flex-direction: column-reverse; gap: 40px; text-align: center; }
            .hero-image-container::before { display: none; }
            .hero-text h1 { font-size: 2.5rem; }
            .hero-text h1 span { font-size: 3.5rem; }
            .coach-photo { max-width: 350px; }
        }
    </style>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    <div class="about-wrapper">
        
        <div class="hero-split">
            <div class="hero-text">
                <h2>Head Coach</h2>
                <h1>Ryan <span>Trainer</span></h1>
                
                <p>Mais do que um treinador, sou um estrategista de performance. Minha jornada no fisiculturismo e na preparação física me ensinou que o corpo humano é uma máquina adaptável, desde que receba os estímulos corretos.</p>
                
                <p>Não acredito em "fórmulas mágicas" ou treinos de blogueira. Meu trabalho é baseado em <strong>Biomecânica</strong>, <strong>Fisiologia</strong> e <strong>Disciplina</strong>.</p>
                
                <p>Se você está cansado de treinar e não ver mudança no espelho, o problema não é sua genética. É a falta de um plano. E é exatamente isso que eu vou te entregar.</p>

                <div style="margin-top: 30px;">
                    <a href="https://www.instagram.com/ct.olympo" target="_blank" class="btn-outline" style="margin-right: 15px;">
                        <i class="fa-brands fa-instagram"></i> Meu Instagram
                    </a>
                </div>
            </div>

            <div class="hero-image-container">
                <img src="assets/img/ryan_coach_atualizado.png" alt="Foto do Ryan Coach" class="coach-photo">
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">+500</span>
                <span class="stat-label">Alunos Transformados</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">8</span>
                <span class="stat-label">Anos de Experiência</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">100%</span>
                <span class="stat-label">Foco em Resultado</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Suporte Técnico</span>
            </div>
        </div>

        <section class="philosophy-section">
            <h2 class="section-title">Minha Filosofia de Trabalho</h2>
            
            <div class="cards-row">
                <div class="info-box">
                    <i class="fa-solid fa-brain"></i>
                    <h3>Ciência Aplicada</h3>
                    <p>Nada de "achismo". Cada série, repetição e intervalo do seu treino tem um motivo fisiológico para estar ali. Otimizamos seu tempo para gerar o máximo de hipertrofia e queima de gordura.</p>
                </div>

                <div class="info-box">
                    <i class="fa-solid fa-bullseye"></i>
                    <h3>Individualidade Biológica</h3>
                    <p>O treino do campeão olímpico pode não funcionar para você. Respeito sua estrutura óssea, histórico de lesões e rotina diária para criar algo sustentável a longo prazo.</p>
                </div>

                <div class="info-box">
                    <i class="fa-solid fa-hand-fist"></i>
                    <h3>Mentalidade Blindada</h3>
                    <p>O corpo só vai onde a mente permite. Além de passar exercícios, meu papel é te cobrar, motivar e te ensinar a ter a disciplina necessária para vencer.</p>
                </div>
            </div>
        </section>

        <div class="final-cta">
            <div class="cta-content">
                <h2 style="font-family: 'Orbitron'; color: #fff; font-size: 2rem; margin-bottom: 20px;">Pronto para treinar de verdade?</h2>
                <p style="color: #ccc; max-width: 600px; margin: 0 auto 30px;">Não espere a "segunda-feira perfeita". O melhor momento para começar a construir o corpo que você quer é agora.</p>
                
                <a href="planos.php" class="CTA-rounded">
                    <span>Quero fazer parte do Time</span>
                </a>
            </div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>