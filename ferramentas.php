<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include 'includes/head_main.php'; ?>
    <title>Métricas de Performance - Ryan Coach</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/menu.css">
    
    <style>
        /* =========================================
           DESIGN SYSTEM V2 (High Contrast Tech)
           ========================================= */
        
        body {
            background-color: #080808;
            /* Um fundo mais dinâmico com iluminação sutil */
            background-image: 
                radial-gradient(circle at top center, rgba(255, 186, 66, 0.08) 0%, transparent 40%),
                radial-gradient(circle at bottom right, rgba(163, 12, 12, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .tools-wrapper {
            padding-top: 120px;
            padding-bottom: 80px;
            max-width: 1100px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        /* --- Cabeçalho --- */
        .page-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.8rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            text-shadow: 0 0 20px rgba(255, 186, 66, 0.2);
        }

        .page-header span {
            color: #FFBA42;
        }

        .page-header p {
            color: #cccccc; /* Cinza bem claro para leitura fácil */
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* --- Grid --- */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 40px;
            align-items: start;
        }

        /* --- Card Design Melhorado --- */
        .tech-card {
            /* Fundo mais sólido e levemente mais claro que o body para destacar */
            background: linear-gradient(145deg, #1a1a1a, #111111);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 35px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        /* Borda brilhante no topo ao passar o mouse */
        .tech-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 186, 66, 0.5);
            box-shadow: 0 15px 40px rgba(0,0,0,0.6), 0 0 20px rgba(255, 186, 66, 0.1);
        }

        .card-header-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FFBA42, #e09612);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #000; /* Ícone preto no fundo dourado para contraste máximo */
            box-shadow: 0 0 15px rgba(255, 186, 66, 0.3);
        }

        .tech-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            color: #fff;
            margin: 0;
        }

        .tech-card .desc {
            color: #b0b0b0; /* Cinza claro */
            font-size: 0.95rem;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* --- Inputs (Mais visíveis) --- */
        .input-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            color: #FFBA42; /* Label Dourada para guiar o olho */
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-input {
            width: 100%;
            background: #252525; /* Fundo bem mais claro que antes */
            border: 1px solid #333;
            padding: 14px;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            transition: 0.3s;
        }

        .custom-input::placeholder {
            color: #666;
        }

        .custom-input:focus {
            background: #2a2a2a;
            border-color: #FFBA42;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 186, 66, 0.1);
        }

        /* --- Botão --- */
        .btn-calculate {
            width: 100%;
            background: #fff; /* Botão Branco para contraste máximo */
            color: #000;
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            padding: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-transform: uppercase;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-calculate:hover {
            background: #FFBA42; /* Fica dourado no hover */
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(255, 186, 66, 0.4);
        }

        /* --- Resultados --- */
        .result-display {
            margin-top: 30px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #444; /* Borda lateral padrão */
            display: none;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; margin-top: 10px; }
            to { opacity: 1; margin-top: 30px; }
        }

        .res-label {
            color: #aaa;
            font-size: 0.9rem;
            text-transform: uppercase;
            display: block;
        }

        .res-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: #fff;
            display: block;
            margin: 5px 0;
        }

        .res-tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #000;
            background: #fff;
        }

        /* Barra IMC */
        .imc-track {
            width: 100%;
            height: 10px;
            background: #333;
            border-radius: 5px;
            margin-top: 15px;
            overflow: hidden;
        }
        .imc-fill {
            height: 100%;
            width: 0%;
            background: #ccc;
            border-radius: 5px;
            transition: width 1s ease, background 0.3s;
        }

        /* --- Responsividade --- */
        @media (max-width: 768px) {
            .tools-wrapper { padding-top: 100px; }
            .page-header h1 { font-size: 2rem; }
            
            /* No mobile, inputs ficam um abaixo do outro se precisar */
            .input-row { flex-direction: column; gap: 15px; }
            
            /* Ajuste do Grid */
            .tools-grid { 
                grid-template-columns: 1fr; /* Uma coluna só */
                max-width: 450px; /* Limita largura no mobile */
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    <div class="tools-wrapper">
        
        <header class="page-header">
            <h1>Métricas de <span>Performance</span></h1>
            <p>Dados precisos para construir a estratégia perfeita. Entenda seus números.</p>
        </header>

        <div class="tools-grid">
            
            <div class="tech-card">
                <div class="card-header-row">
                    <div class="card-icon"><i class="fa-solid fa-weight-scale"></i></div>
                    <div>
                        <h3>Diagnóstico de IMC</h3>
                    </div>
                </div>
                
                <p class="desc">Calcule seu Índice de Massa Corporal para entender a relação básica entre seu peso e altura.</p>
                
                <form id="formIMC">
                    <div class="input-row">
                        <div class="form-group">
                            <label>Peso (kg)</label>
                            <input type="number" id="imcWeight" class="custom-input" placeholder="75.5" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label>Altura (m)</label>
                            <input type="number" id="imcHeight" class="custom-input" placeholder="1.75" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-calculate">Calcular Índice</button>
                </form>

                <div id="resultIMC" class="result-display">
                    <span class="res-label">Seu IMC:</span>
                    <span class="res-number" id="imcValue">0.00</span>
                    <span id="imcBadge" class="res-tag">--</span>
                    
                    <div class="imc-track">
                        <div class="imc-fill" id="imcBar"></div>
                    </div>
                </div>
            </div>

            <div class="tech-card">
                <div class="card-header-row">
                    <div class="card-icon"><i class="fa-solid fa-fire"></i></div>
                    <div>
                        <h3>Gasto Calórico (TMB)</h3>
                    </div>
                </div>

                <p class="desc">Estime quantas calorias seu corpo queima diariamente (Metabolismo Basal + Treino).</p>
                
                <form id="formTMB">
                    <div class="input-row">
                        <div class="form-group">
                            <label>Gênero</label>
                            <select id="tmbGender" class="custom-input">
                                <option value="m">Homem</option>
                                <option value="f">Mulher</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Idade</label>
                            <input type="number" id="tmbAge" class="custom-input" placeholder="25" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Peso (kg)</label>
                            <input type="number" id="tmbWeight" class="custom-input" placeholder="Kg" required>
                        </div>
                        <div class="form-group">
                            <label>Altura (cm)</label>
                            <input type="number" id="tmbHeight" class="custom-input" placeholder="Cm" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Atividade Física</label>
                        <select id="tmbActivity" class="custom-input">
                            <option value="1.2">Sedentário (Sem treino)</option>
                            <option value="1.375">Leve (1-3 dias/sem)</option>
                            <option value="1.55">Moderado (3-5 dias/sem)</option>
                            <option value="1.725">Intenso (6-7 dias/sem)</option>
                            <option value="1.9">Atleta (2x por dia)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-calculate">Calcular Calorias</button>
                </form>

                <div id="resultTMB" class="result-display" style="border-left-color: #FFBA42;">
                    <span class="res-label">Gasto Diário Estimado:</span>
                    <span class="res-number" style="color: #FFBA42;"><span id="tmbValue">0</span> kcal</span>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 5px;">
                            Perder peso: <strong style="color: #fff;">~<span id="cutValue">0</span> kcal</strong>
                        </p>
                        <p style="color: #ccc; font-size: 0.9rem;">
                            Ganhar massa: <strong style="color: #fff;">~<span id="bulkValue">0</span> kcal</strong>
                        </p>
                    </div>
                </div>
            </div>

        </div>
        
        <div style="margin-top: 60px; background: linear-gradient(45deg, #222, #111); border: 1px solid #333; border-radius: 20px; padding: 40px; text-align: center;">
            <h2 style="color: #fff; font-family: 'Orbitron'; margin-bottom: 15px;">Não sabe o que fazer com esses números?</h2>
            <p style="color: #aaa; margin-bottom: 30px;">Ter os dados é o primeiro passo. O segundo é ter um plano nutricional e de treino alinhado a eles.</p>
            <a href="planos.php" class="CTA-rounded"><span>Assinar Consultoria</span></a>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // === LÓGICA IMC ===
        document.getElementById('formIMC').addEventListener('submit', function(e) {
            e.preventDefault();
            const p = parseFloat(document.getElementById('imcWeight').value);
            const a = parseFloat(document.getElementById('imcHeight').value);

            if (p > 0 && a > 0) {
                const imc = p / (a * a);
                
                const resBox = document.getElementById('resultIMC');
                const valEl = document.getElementById('imcValue');
                const badgeEl = document.getElementById('imcBadge');
                const barEl = document.getElementById('imcBar');

                resBox.style.display = 'block';
                valEl.innerText = imc.toFixed(2);

                let color = '#ccc';
                let text = '';
                let width = '0%';

                if (imc < 18.5) { 
                    text = 'Abaixo do Peso'; color = '#3498db'; width = '20%'; 
                } else if (imc < 24.9) { 
                    text = 'Peso Ideal'; color = '#2ecc71'; width = '50%'; 
                } else if (imc < 29.9) { 
                    text = 'Sobrepeso'; color = '#f1c40f'; width = '75%'; 
                } else { 
                    text = 'Obesidade'; color = '#e74c3c'; width = '100%'; 
                }

                badgeEl.innerText = text;
                badgeEl.style.backgroundColor = color;
                resBox.style.borderLeftColor = color;
                
                setTimeout(() => {
                    barEl.style.width = width;
                    barEl.style.backgroundColor = color;
                }, 100);
            }
        });

        // === LÓGICA TMB ===
        document.getElementById('formTMB').addEventListener('submit', function(e) {
            e.preventDefault();
            const gender = document.getElementById('tmbGender').value;
            const age = document.getElementById('tmbAge').value;
            const weight = document.getElementById('tmbWeight').value;
            const height = document.getElementById('tmbHeight').value;
            const act = document.getElementById('tmbActivity').value;

            if(age && weight && height) {
                let tmb = 0;
                if(gender === 'm') {
                    tmb = 88.36 + (13.4 * weight) + (4.8 * height) - (5.7 * age);
                } else {
                    tmb = 447.6 + (9.2 * weight) + (3.1 * height) - (4.3 * age);
                }
                
                const total = Math.round(tmb * act);
                
                document.getElementById('resultTMB').style.display = 'block';
                document.getElementById('tmbValue').innerText = total;
                document.getElementById('cutValue').innerText = total - 500;
                document.getElementById('bulkValue').innerText = total + 300;
            }
        });
    </script>

    <script src="assets/js/navbar.js"></script>
</body>
</html>