<?php
session_start();
require_once 'config/db_connect.php';
require_once 'verificar_plano.php';

// 1. Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Busca dados atualizados do usuário (CORREÇÃO AQUI)
// Alteramos 'data_expiracao' para 'data_expiracao_plano' e 'nivel' para 'tipo_conta'
$sql_user = "SELECT id, nome, email, foto, tipo_conta, plano_atual, data_expiracao_plano 
             FROM usuarios 
             WHERE id = :id";

$stmt = $pdo->prepare($sql_user);
$stmt->execute(['id' => $user_id]);
$dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o usuário foi deletado mas a sessão continua ativa
if (!$dados_usuario) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 3. Atualiza a sessão para garantir sincronia
$_SESSION['tipo_conta'] = $dados_usuario['tipo_conta'];
$_SESSION['plano_atual'] = $dados_usuario['plano_atual'];

// 4. Lógica de Validade do Plano (Opcional por enquanto)
$dias_restantes = 0;
if ($dados_usuario['data_expiracao_plano']) {
    $data_hoje = new DateTime();
    $data_exp = new DateTime($dados_usuario['data_expiracao_plano']);
    if ($data_exp > $data_hoje) {
        $intervalo = $data_hoje->diff($data_exp);
        $dias_restantes = $intervalo->days;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Usuário - Ryan Coach</title>
    
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="assets/css/atleta.css">
    <link rel="stylesheet" href="assets/css/pdf.css">

    <?php include 'includes/head_main.php'; ?>

    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
    <script></script>
</head>
<body>
    
    <div class="background-overlay"></div>

    <?php include 'includes/sidebar_usuario.php'; ?>

    <main id="conteudo">
        </main>

    <script>
    //----------------- Função Global de Navegação --------------------------
    window.carregarConteudo = async function(pagina) {
        const area = document.getElementById('conteudo');
        // Busca a navbar pelo ID que você confirmou
        const navbar = document.querySelector('#main-aside'); 
        const container = document.getElementById('app-content');
        
        // --- CORREÇÃO 1: PEGAR SOMENTE O NOME DA PÁGINA (ANTES DO &) ---
        // Se pagina for "realizar_treino&id=10", paginaBase vira "realizar_treino"
        const paginaBase = pagina.split('&')[0]; 

        // Lista de páginas onde a navbar deve SUMIR (Modo Foco)
        const paginasModoFoco = ['realizar_treino'];

        if (navbar) {
            // --- CORREÇÃO 2: USAR A PAGINA BASE NA COMPARAÇÃO ---
            if (paginasModoFoco.includes(paginaBase)) {
                // MODO FOCO: Esconde a barra
                navbar.style.display = 'none';
                
                // Remove padding para aproveitar tela toda
                if(container) container.style.paddingBottom = '0'; 
            } else {
                // MODO NORMAL: Mostra a barra
                // --- CORREÇÃO 3: LIMPAR O STYLE INLINE ---
                // Usar '' (vazio) faz o elemento voltar a usar o CSS original do arquivo .css
                navbar.style.display = ''; 
                
                // Devolve o espaço do footer
                if(container) container.style.paddingBottom = '80px'; 
            }
        }

        // Feedback Visual
        area.innerHTML = '<div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';
        area.classList.add('loading');

        try {
            // Requisição
            const req = await fetch(`ajax/get_conteudo.php?pagina=${pagina}`);
            if (!req.ok) throw new Error('Erro na rede');
                
            const html = await req.text();
                
            area.innerHTML = html;
            area.classList.remove('loading');

            // --- LÓGICA DE RESTAURAÇÃO DE ABA ---
            const lastTab = localStorage.getItem('lastActiveTab');
            if (lastTab && document.getElementById(lastTab)) {
                if (typeof openTab === 'function') {
                    openTab(null, lastTab);
                }
            }

            // Executa scripts que vieram no HTML (Gráficos, etc)
            const scripts = area.querySelectorAll("script");
            scripts.forEach(s => {
                const newScript = document.createElement("script");
                if (s.src) newScript.src = s.src;
                else newScript.textContent = s.textContent;
                document.body.appendChild(newScript);
            });

            // Atualiza Menu Lateral (Botão Ativo)
            // Aqui também usamos a paginaBase para marcar o botão correto
            const botoes = document.querySelectorAll('#main-aside button');
            botoes.forEach(btn => {
                if (btn.dataset.pagina === paginaBase) btn.classList.add('active');
                else btn.classList.remove('active');
            });

        } 
        catch (err) {
            console.error(err);
            area.innerHTML = '<p class="error">Erro ao carregar.</p>';
        }
    };

    // TELA INICIAL AO ABRIR A PAGINA
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const paginaUrl = urlParams.get('page');

        if (paginaUrl) {
            carregarConteudo(paginaUrl);
            window.history.replaceState({}, document.title, window.location.pathname);
        } else {
            carregarConteudo('dashboard');
        }

        // Listener do Menu Lateral
        const mainAside = document.getElementById('main-aside');
        if(mainAside){
            mainAside.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (btn && btn.dataset.pagina) {
                    if (btn.dataset.pagina === 'logout') window.location.href = 'actions/logout.php';
                    else carregarConteudo(btn.dataset.pagina);
                }
            });
        }
    });

    // Preview de Imagem
    window.previewImage = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('preview-img');
                if (img) img.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    };
        
    // Função de Abas (Global para funcionar no HTML injetado)
    window.abrirTreino = function(evt, id) {
        const contents = document.getElementsByClassName("treino-content");
        for (let i = 0; i < contents.length; i++) contents[i].style.display = "none";
            
        const tabs = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < tabs.length; i++) tabs[i].classList.remove("active");
            
        document.getElementById(id).style.display = "block";
        evt.currentTarget.classList.add("active");
    };

    function abrirTreino(evt, divName) {
        var i, content, tablinks;
        content = document.getElementsByClassName("treino-content");
        for (i = 0; i < content.length; i++) {
            content[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(divName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    </script>
    
<!-- ----------------- HTML CRONÔMETROS ------------------------------->

    <div id="float-timer" class="timer-widget" style="display: none;">
        
        <div class="timer-close-btn" onclick="fecharTimer()">
            <i class="fa-solid fa-times"></i>
        </div>

        <div class="timer-display" id="timer-val">00:00</div>
        
        <div class="timer-controls">
            <button type="button" class="t-btn reset" onclick="resetTimer()">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            
            <button type="button" class="t-btn toggle" id="btn-timer-toggle" onclick="toggleTimer()">
                <i class="fa-solid fa-play"></i>
            </button>
        </div>
    </div>


    <div id="tech-timer-overlay" style="display:none;">
        <div class="tech-timer-circle">
            <svg viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" class="bg-ring"></circle>
                <circle cx="50" cy="50" r="45" class="progress-ring" id="tech-progress-ring"></circle>
            </svg>
            <div class="timer-content">
                <span id="tech-timer-val">00:00</span>
                <button onclick="stopTechTimer()" class="btn-close-timer"><i class="fa-solid fa-times"></i></button>
            </div>
        </div>
    </div>

<!-- ----------------- HTML SELEÇÃO ENTRE TREINO E HISTÓRICO ------------------------------->

    <div id="modalTreinoOpcoes" class="modal-overlay" style="display: none;">
        <div class="modal-content selection-modal">
            <button class="modal-close" onclick="fecharModalTreinos()">&times;</button>
            
            <div id="step-type">
                <h3 class="modal-title">O QUE DESEJA ACESSAR?</h3>
                <div class="modal-grid-options">
                    <div class="option-card" onclick="irParaListaTreinos()">
                        <i class="fa-solid fa-dumbbell"></i>
                        <span>Fichas de Treino</span>
                    </div>
                    <div class="option-card outline" onclick="irParaHistorico()">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Histórico Realizado</span>
                    </div>
                </div>
            </div>

            <div id="step-list" style="display: none;">
                <div class="modal-header-row">
                    <h3 class="modal-title">QUAL PLANEJAMENTO?</h3>
                </div>
                <div id="lista-treinos-container" class="treinos-list-scroll">
                    <div class="loading-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
                </div>
            </div>
        </div>
    </div>

    <script>

// --- LÓGICA DO MODAL DE TREINOS ---
    
    function abrirModalTreinos() {
        document.getElementById('modalTreinoOpcoes').style.display = 'flex';
        voltarStepType(); // Sempre reseta para a primeira tela
    }

    function fecharModalTreinos() {
        document.getElementById('modalTreinoOpcoes').style.display = 'none';
    }

    function irParaHistorico() {
        fecharModalTreinos();
        carregarConteudo('historico');
    }

    function irParaListaTreinos() {
        // 1. Muda a tela do modal
        document.getElementById('step-type').style.display = 'none';
        document.getElementById('step-list').style.display = 'block';
        
        const container = document.getElementById('lista-treinos-container');
        container.innerHTML = '<div style="color:#fff; padding:20px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Buscando...</div>';

        // 2. Busca a lista via AJAX
        fetch('ajax/get_conteudo.php?pagina=listar_treinos_json')
            .then(res => res.json())
            .then(data => {
                container.innerHTML = ''; // Limpa loading
                
                if (data.length === 0) {
                    container.innerHTML = '<p style="color:#888;">Nenhum treino encontrado.</p>';
                    return;
                }

                data.forEach(treino => {
                    const btn = document.createElement('button');
                    btn.className = 'btn-treino-select';
                    
                    // Formata data simples
                    const dataInicio = new Date(treino.data_inicio).toLocaleDateString('pt-BR');
                    
                    btn.innerHTML = `
                        <strong>${treino.nome}</strong>
                        <span>${treino.nivel_plano.toUpperCase()} • Início: ${dataInicio}</span>
                    `;
                    
                    btn.onclick = function() {
                        fecharModalTreinos();
                        // Carrega o treino específico
                        carregarConteudo('treinos&treino_id=' + treino.id);
                    };
                    
                    container.appendChild(btn);
                });
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<p style="color:red;">Erro ao carregar lista.</p>';
            });
    }

    function voltarStepType() {
        document.getElementById('step-list').style.display = 'none';
        document.getElementById('step-type').style.display = 'block';
    }

    // Fecha ao clicar fora
    window.onclick = function(event) {
        const modal = document.getElementById('modalTreinoOpcoes');
        if (event.target == modal) {
            fecharModalTreinos();
        }
    }
    </script>


<!-- ------------------------------------------------------>
<!--------------- HTML MODAIS DE AVALIAÇÃO ----------------->

    <div id="modalAvaliacaoOpcoes" class="modal-overlay" style="display: none;">
        <div class="modal-content selection-modal">
            <button class="modal-close" onclick="fecharModalAvaliacoes()">&times;</button>
            
            <div id="step-type-av">
                <h3 class="modal-title">O QUE DESEJA VER?</h3>
                <div class="modal-grid-options">
                    
                    <div class="option-card" onclick="irParaAvaliacoes()">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <span>Minhas Avaliações</span>
                    </div>
                    
                    <div class="option-card outline" onclick="irParaProgresso()">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Meu Progresso</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="modalNovaAvaliacao" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            
            <div class="modal-header-av">
                <button class="modal-close" onclick="fecharModalAvaliacao()">&times;</button>
                <h3><i class="fa-solid fa-ruler-combined"></i> NOVA AVALIAÇÃO</h3>
            </div>
            
            <form action="actions/avaliacao_add.php" method="POST" enctype="multipart/form-data" id="formAvaliacao">
                <input type="hidden" name="aluno_id" id="av_aluno_id" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">

                <div class="modal-body-scroll">
                    
                    <div class="form-section-box">
                        <span class="section-label-gold">DADOS GERAIS</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label class="label-mini">Data</label>
                                <input type="date" name="data_avaliacao" class="input-dark" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div>
                                <label class="label-mini">Gênero (p/ Cálculo BF)</label>
                                <select name="genero" class="input-dark">
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="label-mini">Idade</label>
                                <input type="number" name="idade" class="input-dark" placeholder="Anos">
                            </div>
                            <div>
                                <label class="label-mini">Altura (cm)</label>
                                <input type="number" name="altura" class="input-dark" placeholder="Ex: 175" required>
                            </div>
                            <div>
                                <label class="label-mini">Peso (kg)</label>
                                <input type="number" step="0.1" name="peso" class="input-dark" placeholder="00.0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-box">
                        <span class="section-label-gold">TRONCO & PERÍMETROS</span>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                            <div>
                                <label class="label-mini">Pescoço</label>
                                <input type="number" step="0.1" name="pescoco" class="input-dark" placeholder="0.0">
                            </div>
                            <div>
                                <label class="label-mini">Ombros</label>
                                <input type="number" step="0.1" name="ombro" class="input-dark" placeholder="0.0">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                            <div>
                                <label class="label-mini">Tórax Inspirado</label>
                                <input type="number" step="0.1" name="torax_inspirado" class="input-dark" placeholder="0.0">
                            </div>
                            <div>
                                <label class="label-mini">Tórax Relaxado</label>
                                <input type="number" step="0.1" name="torax_relaxado" class="input-dark" placeholder="0.0">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="label-mini">Cintura</label>
                                <input type="number" step="0.1" name="cintura" class="input-dark" placeholder="0.0">
                            </div>
                            <div>
                                <label class="label-mini">Abdômen</label>
                                <input type="number" step="0.1" name="abdomen" class="input-dark" placeholder="0.0">
                            </div>
                            <div>
                                <label class="label-mini">Quadril</label>
                                <input type="number" step="0.1" name="quadril" class="input-dark" placeholder="0.0">
                            </div>
                        </div>
                    </div>

                    <div class="form-section-box">
                        <span class="section-label-gold">MEMBROS SUPERIORES (DIR / ESQ)</span>
                        
                        <div style="margin-bottom: 10px;">
                            <label class="label-mini" style="color:#fff;">Braço Relaxado</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="number" step="0.1" name="braco_dir_relaxado" class="input-dark" placeholder="Direito">
                                <input type="number" step="0.1" name="braco_esq_relaxado" class="input-dark" placeholder="Esquerdo">
                            </div>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label class="label-mini" style="color:#fff;">Braço Contraído</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="number" step="0.1" name="braco_dir_contraido" class="input-dark" placeholder="Direito">
                                <input type="number" step="0.1" name="braco_esq_contraido" class="input-dark" placeholder="Esquerdo">
                            </div>
                        </div>

                        <div>
                            <label class="label-mini" style="color:#fff;">Antebraço</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="number" step="0.1" name="antebraco_dir" class="input-dark" placeholder="Direito">
                                <input type="number" step="0.1" name="antebraco_esq" class="input-dark" placeholder="Esquerdo">
                            </div>
                        </div>
                    </div>

                    <div class="form-section-box">
                        <span class="section-label-gold">MEMBROS INFERIORES (DIR / ESQ)</span>
                        
                        <div style="margin-bottom: 10px;">
                            <label class="label-mini" style="color:#fff;">Coxa</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="number" step="0.1" name="coxa_dir" class="input-dark" placeholder="Direita">
                                <input type="number" step="0.1" name="coxa_esq" class="input-dark" placeholder="Esquerda">
                            </div>
                        </div>

                        <div>
                            <label class="label-mini" style="color:#fff;">Panturrilha</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="number" step="0.1" name="panturrilha_dir" class="input-dark" placeholder="Direita">
                                <input type="number" step="0.1" name="panturrilha_esq" class="input-dark" placeholder="Esquerda">
                            </div>
                        </div>
                    </div>

                    <div class="form-section-box">
                        <span class="section-label-gold">FOTOS</span>
                        <input type="file" name="fotos[]" id="foto_input" multiple accept="image/*" style="display: none;" onchange="previewFiles()">
                        <label for="foto_input" class="upload-zone">
                            <i class="fa-solid fa-camera upload-icon"></i>
                            <div class="upload-text">Adicionar Fotos</div>
                        </label>
                        <div id="preview-area" class="preview-container"></div>
                    </div>

                    <div class="form-section-box" style="margin-bottom:0;">
                        <span class="section-label-gold">VÍDEO (OPCIONAL)</span>
                        <label class="label-mini">Link (Youtube / Drive)</label>
                        <input type="text" name="videos_links" class="input-dark" placeholder="Cole o link aqui...">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-save-modal">SALVAR E CALCULAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ---------------------------------------------------------------------------->
    <!--------------- HTML MODAL DE PREVIEW CARGAS DO ULTIMO TREINO ----------------->
    <div id="modalHistoricoExercicio" class="modal-overlay" style="display:none; align-items:center; justify-content:center; z-index:9999;">
        <div class="modal-content" style="max-width: 400px; width: 90%; background: #1a1a1a; border: 1px solid #333;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #333; padding-bottom:10px;">
                <h3 style="color:var(--gold); margin:0; font-size:1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:85%;" id="tituloHistorico">
                    Histórico
                </h3>
                <span onclick="document.getElementById('modalHistoricoExercicio').style.display='none'" style="color:#fff; font-size:1.5rem; cursor:pointer;">&times;</span>
            </div>

            <p style="color:#888; font-size:0.8rem; margin-bottom:15px; text-align:center;">
                Dados do último treino realizado.
            </p>

            <div id="listaHistorico" style="max-height:300px; overflow-y:auto; padding-right:5px;">
                </div>

            <div style="margin-top:20px;">
                <button onclick="document.getElementById('modalHistoricoExercicio').style.display='none'" class="btn-gold" style="width:100%; padding:10px; font-size:0.9rem;">FECHAR</button>
            </div>
        </div>
    </div>


    <script>
/* ==========================================================================
   USUARIO.JS - SCRIPT EXCLUSIVO DO PAINEL DO ATLETA
   ========================================================================== 
   ÍNDICE:
   1. MÓDULO: AVALIAÇÃO FÍSICA (Visualização e Modal)
   2. MÓDULO: DIETA (Check de Refeições)
   3. MÓDULO: CRONÔMETRO FLUTUANTE E MODAL DE TÉCNICAS AVANÇADAS
   4. MÓDULO: GESTÃO DE COACH (Vincular)
   5. MÓDULO: HISTÓRICO (DELETE & EDIT & PREVIEW)
   6. MÓDULO: TUTORIAIS VIDEOS
   7. MÓDULO: TREINOS PRONTOS (Biblioteca)
   ========================================================================== */

/* ==========================================================================
   1. MÓDULO: AVALIAÇÃO FÍSICA
   ========================================================================== */

function abrirModalAvaliacoes() {
    document.getElementById('modalAvaliacaoOpcoes').style.display = 'flex';
}
function fecharModalAvaliacoes() {
    document.getElementById('modalAvaliacaoOpcoes').style.display = 'none';
}
function irParaAvaliacoes() {
    fecharModalAvaliacoes();
    carregarConteudo('avaliacoes');
}
function irParaProgresso() {
    fecharModalAvaliacoes();
    carregarConteudo('progresso');
}
// Fecha ao clicar fora
window.onclick = function(event) {
    const m1 = document.getElementById('modalTreinoOpcoes');
    const m2 = document.getElementById('modalAvaliacaoOpcoes');
    if (event.target == m1) fecharModalTreinos();
    if (event.target == m2) fecharModalAvaliacoes();
}

function abrirModalAvaliacao(idAluno = null) {
    if (idAluno) {
        document.getElementById('av_aluno_id').value = idAluno;
    }
    document.getElementById('modalNovaAvaliacao').style.display = 'flex';
}

function fecharModalAvaliacao() {
    document.getElementById('modalNovaAvaliacao').style.display = 'none';
}

/* ==========================================================================
   2. MÓDULO: DIETA (CHECKLIST)
   ========================================================================== */

async function toggleRefeicao(refeicaoId, btn) {
    // 1. Efeito Visual Imediato (UX Rápida)
    const card = document.getElementById('ref_' + refeicaoId);
    
    // Alterna classes visualmente antes de esperar o servidor
    btn.classList.toggle('checked');
    if(card) card.classList.toggle('completed'); // Deixa o card meio transparente

    // 2. Envia para o Servidor (Background)
    try {
        const response = await fetch('actions/dieta_check.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refeicao_id: refeicaoId })
        });

        if (!response.ok) {
            throw new Error('Erro ao salvar no servidor');
        }
        // Sucesso silencioso (não precisa de alert)

    } catch (error) {
        console.error(error);
        // Reverte o visual se deu erro (feedback de falha)
        btn.classList.toggle('checked');
        if(card) card.classList.toggle('completed');
        alert("Erro de conexão. Tente novamente.");
    }
}

/* ==========================================================================
   3. MÓDULO: CRONÔMETRO FLUTUANTE
   ========================================================================== */

let timerInterval;
let seconds = 0;
let isRunning = false;

function mostrarTimer() {
    document.getElementById('float-timer').style.display = 'flex';
}

async function toggleTimer() { 
    const btn = document.getElementById('btn-timer-toggle');
    const icon = btn.querySelector('i');
    const widget = document.getElementById('float-timer');

    if (isRunning) {
        // --- PAUSAR ---
        clearInterval(timerInterval);
        isRunning = false;
        icon.classList.remove('fa-pause');
        icon.classList.add('fa-play');
        widget.classList.remove('running');
        
        // Opcional: Se quiser que a janelinha feche ao pausar
        // PipEngine.closePip(); 

    } else {
        // --- INICIAR ---
        
        // MUDANÇA AQUI: Apenas inicia o vídeo silencioso.
        // O navegador abrirá o PiP sozinho quando o usuário sair da tela (no Android/Chrome)
        await PipEngine.startVideo();

        isRunning = true;
        icon.classList.remove('fa-play');
        icon.classList.add('fa-pause');
        widget.classList.add('running');
        
        timerInterval = setInterval(() => {
            seconds++;
            updateTimerDisplay(); 
        }, 1000);
    }
}

function resetTimer() {
    clearInterval(timerInterval);
    seconds = 0;
    isRunning = false;
    
    // Atualiza Displays (Visual e PiP) para 00:00
    const textoZero = "00:00";
    const display = document.getElementById('timer-val');
    if(display) display.innerText = textoZero;
    PipEngine.draw(textoZero); 

    const btn = document.getElementById('btn-timer-toggle');
    const icon = btn ? btn.querySelector('i') : null;
    const widget = document.getElementById('float-timer');
    
    if(icon) {
        icon.classList.remove('fa-pause');
        icon.classList.add('fa-play');
    }
    if(widget) widget.classList.remove('running');
}

function fecharTimer() {
    document.getElementById('float-timer').style.display = 'none';
    resetTimer();
    PipEngine.closePip(); // Fecha a janelinha flutuante
}

function updateTimerDisplay() {
    const display = document.getElementById('timer-val');
    
    // Formata o tempo
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    const textoFormatado = (mins < 10 ? "0" : "") + mins + ":" + (secs < 10 ? "0" : "") + secs;

    // 1. Atualiza seu widget visual (HTML)
    if (display) {
        display.innerText = textoFormatado;
    }

    // 2. Atualiza o PiP (Canvas)
    PipEngine.draw(textoFormatado);
}

// --- Lógica de Arrastar (Drag & Drop) ---
const dragItem = document.getElementById("float-timer");

if (dragItem) {
    let active = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    // Listeners no Elemento (Início do arrasto)
    dragItem.addEventListener("touchstart", dragStart, {passive: false});
    dragItem.addEventListener("mousedown", dragStart);

    // Listeners no Documento (Movimento e Fim)
    document.addEventListener("touchend", dragEnd, {passive: false});
    document.addEventListener("touchmove", drag, {passive: false});
    document.addEventListener("mouseup", dragEnd);
    document.addEventListener("mousemove", drag);

    function dragStart(e) {
        // Ignora cliques em botões dentro do timer
        if (e.target.closest('button') || e.target.closest('.fa-times') || e.target.onclick) {
            return;
        }

        if (e.type === "touchstart") {
            initialX = e.touches[0].clientX - xOffset;
            initialY = e.touches[0].clientY - yOffset;
        } else {
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;
        }

        if (e.target === dragItem || dragItem.contains(e.target)) {
            active = true;
        }
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        active = false;
    }

    function drag(e) {
        if (active) {
            e.preventDefault(); // Evita scroll da página no mobile
        
            if (e.type === "touchmove") {
                currentX = e.touches[0].clientX - initialX;
                currentY = e.touches[0].clientY - initialY;
            } else {
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
            }

            xOffset = currentX;
            yOffset = currentY;

            setTranslate(currentX, currentY, dragItem);
        }
    }

    function setTranslate(xPos, yPos, el) {
        el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)";
    }
}

// =================================================================
// 1. MOTOR DO CRONÔMETRO FLUTUANTE (PIP)
// =================================================================
const PipEngine = {
    videoElement: null,
    canvas: null,
    ctx: null,

    init: function() {
        if (this.canvas) return; // Já existe

        // Cria o Canvas (Onde desenhamos)
        this.canvas = document.createElement('canvas');
        this.canvas.width = 300;
        this.canvas.height = 150;
        this.ctx = this.canvas.getContext('2d');
        
        // Cria o Vídeo Falso
        this.videoElement = document.createElement('video');
        this.videoElement.muted = true; // Obrigatório para tocar sozinho
        this.videoElement.playsInline = true; // Obrigatório para iOS
        
        // Deixamos o atributo nativo também (Segurança dupla)
        this.videoElement.setAttribute('autopictureinpicture', ''); 
        
        // Desenha o 00:00 inicial
        this.draw("00:00");
    },

    // Chamado APENAS quando aperta o Play
    startVideo: async function() {
        if (!this.canvas) this.init();

        // Conecta o Canvas ao Vídeo (Stream)
        if (!this.videoElement.srcObject) {
            this.videoElement.srcObject = this.canvas.captureStream();
        }
        
        // Dá o Play no vídeo "invisível"
        try {
            await this.videoElement.play();
        } catch (e) {
            console.log("Erro ao iniciar vídeo background:", e);
        }
    },

    closePip: function() {
        if (document.pictureInPictureElement) {
            document.exitPictureInPicture().catch(() => {});
        }
    },

    draw: function(texto) {
        if (!this.ctx) return;

        // Fundo
        this.ctx.fillStyle = "#111"; 
        this.ctx.fillRect(0, 0, 300, 150);

        // Borda
        this.ctx.lineWidth = 8;
        this.ctx.strokeStyle = "#DAA520"; 
        this.ctx.strokeRect(0, 0, 300, 150);

        // Tempo
        this.ctx.font = "bold 80px Arial";
        this.ctx.fillStyle = "#FFF";
        this.ctx.textAlign = "center";
        this.ctx.textBaseline = "middle";
        this.ctx.fillText(texto, 150, 75);
        
        // Marca
        this.ctx.font = "20px Arial";
        this.ctx.fillStyle = "#DAA520";
        this.ctx.fillText("RYAN COACH", 150, 125);
    }
};

// =================================================================
// 2. GATILHO AUTOMÁTICO (O CÓDIGO QUE FEZ FUNCIONAR)
// =================================================================
// Esse evento dispara sempre que o usuário minimiza o navegador ou troca de aba
document.addEventListener("visibilitychange", async () => {
    // Só tenta abrir se:
    // 1. A página ficou oculta (saiu do app)
    // 2. O cronômetro está rodando (variável global isRunning)
    // 3. O vídeo já foi iniciado
    if (document.visibilityState === "hidden" && typeof isRunning !== 'undefined' && isRunning) {
        
        if (PipEngine.videoElement && !document.pictureInPictureElement) {
            try {
                // Força a entrada no modo PiP
                await PipEngine.videoElement.requestPictureInPicture();
            } catch(e) {
                // Ignora erros silenciosamente (alguns browsers bloqueiam)
            }
        }
    }
});

let techTimerInterval;
let techTimeLeft = 0;
let techTotalTime = 0;

// Função chamada pelo botãozinho do PHP: iniciarTimerRest(15)
function iniciarTimerRest(seconds) {
    // 1. Configura
    techTotalTime = seconds;
    techTimeLeft = seconds;
    
    // 2. Mostra o Overlay
    const overlay = document.getElementById('tech-timer-overlay');
    if(overlay) overlay.style.display = 'flex';
    
    // 3. Define cor baseada na técnica (Opcional, pega do CSS var ou fixa)
    // Aqui deixamos Gold ou Branco por padrão, mas pode customizar
    
    updateTechDisplay();
    
    // 4. Inicia o Loop
    clearInterval(techTimerInterval); // Limpa anteriores por segurança
    techTimerInterval = setInterval(() => {
        techTimeLeft--;
        updateTechDisplay();
        
        if (techTimeLeft <= 0) {
            timerFinished();
        }
    }, 1000);
}

function stopTechTimer() {
    clearInterval(techTimerInterval);
    const overlay = document.getElementById('tech-timer-overlay');
    if(overlay) overlay.style.display = 'none';
}

function timerFinished() {
    stopTechTimer();
    // Opcional: Vibrar o celular se for mobile
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    // Opcional: Tocar um som
    // new Audio('assets/beep.mp3').play();
}

function updateTechDisplay() {
    const display = document.getElementById('tech-timer-val');
    const ring = document.getElementById('tech-progress-ring');
    
    // Formata 00:00
    const mins = Math.floor(techTimeLeft / 60);
    const secs = techTimeLeft % 60;
    if(display) display.innerText = (mins < 10 ? "0" : "") + mins + ":" + (secs < 10 ? "0" : "") + secs;
    
    // Atualiza o Círculo de Progresso SVG
    if(ring) {
        const radius = ring.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        const offset = circumference - (techTimeLeft / techTotalTime) * circumference;
        ring.style.strokeDashoffset = offset;
        
        // Muda cor se estiver acabando (ex: < 5s fica vermelho)
        if(techTimeLeft <= 3) ring.style.stroke = "#ff4242";
        else ring.style.stroke = "#00e676"; // Verde/Ciano padrão
    }
}

// --- ATUALIZAÇÃO DA FUNÇÃO DE FECHAR MODAL ---
// Substitua sua função closeTechniqueModal antiga por esta:
function closeTechniqueModal(id) {
    // 1. Fecha o Modal
    document.getElementById(id).style.display = 'none';
    
    // 2. MATA O TIMER se estiver rodando (Regra que você pediu)
    stopTechTimer();

    // 3. Visual de "Preenchido" no botão
    const btnId = 'btn_' + id.replace('modal_', '');
    const btn = document.getElementById(btnId);
    if(btn) {
        btn.style.opacity = '1';
        btn.innerHTML = '<span><i class="fa-solid fa-check"></i> DADOS SALVOS</span> <i class="fa-solid fa-chevron-down"></i>';
        // Remove estilo inline antigo e aplica um verde sucesso
        btn.style.background = 'rgba(0, 230, 118, 0.2)';
        btn.style.borderColor = '#00e676';
        btn.style.color = '#00e676';
        btn.style.boxShadow = 'none';
    }
}

function openTechniqueModal(id) {
    document.getElementById(id).style.display = 'flex';
}

// Apenas fecha o modal e para o timer (Ação do "X")
function closeTechniqueModal(id) {
    document.getElementById(id).style.display = 'none';
    
    // Se tiver timer rodando, para ele
    if (typeof stopTechTimer === "function") {
        stopTechTimer();
    }
}

// Muda o visual do botão para "Salvo" e fecha (Ação do "SALVAR DADOS")
function confirmTechniqueData(id, type) {
    // 1. Configura as cores baseadas no tipo
    let color = '#00e676'; // Padrão (Rest Pause / Verde)
    let bg = 'rgba(0, 230, 118, 0.2)';

    if (type === 'drop') {
        color = '#ff4081'; // Rosa
        bg = 'rgba(255, 64, 129, 0.2)';
    } else if (type === 'cluster') {
        color = '#ff9100'; // Laranja
        bg = 'rgba(255, 145, 0, 0.2)';
    }

    // 2. Muda o visual do botão principal na lista
    const btnId = 'btn_' + id.replace('modal_', '');
    const btn = document.getElementById(btnId);
    
    if(btn) {
        btn.style.opacity = '1';
        // Ícone de check + Texto
        btn.innerHTML = '<span><i class="fa-solid fa-check"></i> DADOS SALVOS</span> <i class="fa-solid fa-chevron-down"></i>';
        
        // Aplica as cores dinâmicas
        btn.style.background = bg;
        btn.style.borderColor = color;
        btn.style.color = color;
        btn.style.boxShadow = 'none'; // Remove o brilho excessivo quando salvo
    }

    // 3. Fecha o modal
    closeTechniqueModal(id);
}
/* ==========================================================================
   4. MÓDULO: GESTÃO DE COACH
   ========================================================================== */

function abrirModalVincular() {
    const modal = document.getElementById("modalVincularCoach");
    
    if (modal) {
        modal.style.display = "flex";
        document.body.style.overflow = "hidden"; // Bloqueia scroll do fundo
        document.body.appendChild(modal); 
    }
}

function fecharModalVincular() {
    const modal = document.getElementById("modalVincularCoach");
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = ""; // Restaura scroll
    }
}

// Fechar ao clicar fora do modal
window.addEventListener('click', function(event) {
    const modal = document.getElementById("modalVincularCoach");
    if (event.target === modal) {
        fecharModalVincular();
    }
});

/* ==========================================================================
   5. MÓDULO: HISTÓRICO (DELETE & EDIT & PREVIEW)
   ========================================================================== */

function deletarHistorico(dataRef) {
    if(confirm("Tem certeza que deseja apagar este registro do histórico?\nIsso não pode ser desfeito.")) {
        fetch('actions/treino_historico_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: dataRef })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                carregarConteudo('historico');
            } else {
                alert('Erro: ' + (data.message || 'Falha ao apagar'));
            }
        });
    }
}

// Lógica de Edição
let isEditingHistory = false;

function alternarEdicaoHistorico() {
    isEditingHistory = !isEditingHistory;
    const btn = document.getElementById('btn-editar-hist');
    const container = document.querySelector('.history-details-list');
    
    // Elementos
    const viewEls = container.querySelectorAll('.view-val');
    const inputEls = container.querySelectorAll('.edit-input');

    if (isEditingHistory) {
        // MODO EDIÇÃO ATIVADO
        viewEls.forEach(el => el.style.display = 'none');
        inputEls.forEach(el => el.style.display = 'block');
        
        // Muda botão para Salvar
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.style.background = 'rgba(0, 230, 118, 0.2)'; // Verde
        btn.style.color = '#00e676';
        btn.style.borderColor = '#00e676';
        
    } else {
        // SALVAR ALTERAÇÕES
        salvarEdicaoHistorico(inputEls, btn, viewEls);
    }
}

function salvarEdicaoHistorico(inputs, btn, viewEls) {
    // Feedback visual
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
    
    const updates = {};

    inputs.forEach(input => {
        const cell = input.closest('.editable-cell');
        const id = cell.dataset.id;
        const type = cell.dataset.type; // 'carga' ou 'reps'
        
        if (!updates[id]) updates[id] = {};
        updates[id][type] = input.value;
    });

    fetch('actions/treino_historico_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ updates: updates })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualiza valores visuais
            inputs.forEach(input => {
                const cell = input.closest('.editable-cell');
                const span = cell.querySelector('.view-val');
                span.innerText = input.value;
            });
            
            // Volta ao estado normal
            inputs.forEach(el => el.style.display = 'none');
            viewEls.forEach(el => el.style.display = 'block');
            
            // Reseta botão
            btn.innerHTML = '<i class="fa-solid fa-pen"></i>';
            btn.style.background = 'rgba(255, 186, 66, 0.1)';
            btn.style.color = 'var(--gold)';
            btn.style.borderColor = 'var(--gold)';
            
        } else {
            alert("Erro ao salvar: " + data.message);
            // Reabre edição em caso de erro
            isEditingHistory = true;
            alternarEdicaoHistorico(); // Chama para inverter de volta se der erro? Não, melhor deixar aberto.
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erro de conexão.");
    });
}

// HISTÓRICO APENAS DO ULTIMO TREINO NA PARTE DO REALIZAR EXERCICIO
function abrirHistoricoExercicio(historicoData, nomeExercicio) {
    const modal = document.getElementById('modalHistoricoExercicio');
    const lista = document.getElementById('listaHistorico');
    const titulo = document.getElementById('tituloHistorico');

    titulo.innerText = nomeExercicio;
    lista.innerHTML = '';

    if (!historicoData || Object.keys(historicoData).length === 0) {
        lista.innerHTML = '<div style="text-align:center; padding:30px; color:#666;">Nenhum histórico recente.</div>';
        modal.style.display = 'flex';
        return;
    }

    // Converter para array
    let seriesParaExibir = [];
    for (const sKey in historicoData) {
        const seriesObj = historicoData[sKey];
        for (const nKey in seriesObj) {
            const records = seriesObj[nKey]; 
            if (Array.isArray(records)) {
                seriesParaExibir.push({ numero: nKey, registros: records });
            }
        }
    }

    // Ordenar
    seriesParaExibir.sort((a, b) => parseInt(a.numero) - parseInt(b.numero));

    // Monta Tabela
    let tabelaHtml = `
        <table class="hist-sets-table" style="width:100%; border-collapse: separate; border-spacing: 0 8px;">
            <thead>
                <tr>
                    <th style="text-align:left; color:#666; font-size:0.7rem; padding-bottom:5px;" width="15%">#</th>
                    <th style="text-align:left; color:#666; font-size:0.7rem; padding-bottom:5px;" width="30%">TIPO</th>
                    <th style="text-align:center; color:#666; font-size:0.7rem; padding-bottom:5px;" width="25%">KG</th>
                    <th style="text-align:right; color:#666; font-size:0.7rem; padding-bottom:5px;" width="30%">REPS</th>
                </tr>
            </thead>
            <tbody>
    `;

    seriesParaExibir.forEach((item) => {
        const registroPrincipal = item.registros[0];
        
        let dadosTecnicos = {};
        if (registroPrincipal.dados_tecnicos) {
            try {
                dadosTecnicos = (typeof registroPrincipal.dados_tecnicos === 'object') 
                                ? registroPrincipal.dados_tecnicos 
                                : JSON.parse(registroPrincipal.dados_tecnicos);
            } catch (e) { console.log("Erro JSON", e); }
        }

        let tecnicaTipo = (registroPrincipal.tecnica || dadosTecnicos.tecnica || 'normal').toLowerCase();
        
        // --- 1. DEFINE A COR BASEADA NA CATEGORIA (Warmup, Top, Work...) ---
        let categoriaRaw = (registroPrincipal.categoria || 'work').toLowerCase();
        
        // Correção de nomes comuns
        if(categoriaRaw === 'working') categoriaRaw = 'work';
        
        let catVisual = categoriaRaw; // Ex: 'warmup' (vai virar class="hist-badge warmup")
        let labelVisual = categoriaRaw.toUpperCase(); 
        let rowStyle = 'background: #222;';

        // Ajuste de Labels específicos
        if(categoriaRaw === 'top') labelVisual = 'TOP SET';
        if(categoriaRaw === 'backoff') labelVisual = 'BACK-OFF';

        // --- 2. SOBRESCEVE SE FOR TÉCNICA AVANÇADA ---
        
        // DROP SET
        if (tecnicaTipo === 'dropset') {
            catVisual = 'technique-drop'; // Usa a variável rosa
            labelVisual = 'DROP SET';
            
            item.registros.forEach((reg, idx) => {
                let isDrop = idx > 0;
                let currentNumDisplay = isDrop 
                    ? `<i class="fa-solid fa-turn-up fa-rotate-90" style="margin-right:2px; font-size:0.6rem; opacity:0.7;"></i> ${idx}`
                    : `#${item.numero}`;
                
                let currentNumStyle = isDrop 
                    ? "color:var(--color-drop); font-weight:bold; font-size:0.8rem; padding: 10px 0 10px 10px; border-radius: 6px 0 0 6px;"
                    : "color:var(--color-drop); font-weight:bold; padding: 10px; border-radius: 6px 0 0 6px;";
                
                let currentRowStyle = isDrop 
                    ? "background: linear-gradient(90deg, rgba(255, 64, 129, 0.1) 0%, rgba(20,20,20,1) 100%);"
                    : "background: rgba(255, 64, 129, 0.05);"; // Fundo sutil rosa

                tabelaHtml += `
                    <tr style="${currentRowStyle}">
                        <td style="${currentNumStyle}">${currentNumDisplay}</td>
                        <td style="padding: 10px;">
                            ${!isDrop ? `<span class="hist-badge ${catVisual}">${labelVisual}</span>` : ''}
                        </td>
                        <td style="text-align:center; padding: 10px;">
                            <span style="color:#fff; font-weight:bold;">${parseFloat(reg.carga_kg)}</span>
                        </td>
                        <td style="text-align:right; padding: 10px; border-radius: 0 6px 6px 0;">
                            <span style="color:#fff;">${reg.reps_realizadas}</span>
                        </td>
                    </tr>
                `;
            });
            return; 
        }

        // REST PAUSE
        if (tecnicaTipo === 'restpause') {
            catVisual = 'technique-rest'; // Usa a variável verde
            labelVisual = 'REST PAUSE';
            rowStyle = "background: rgba(0, 230, 118, 0.05);"; // Fundo sutil verde
        } 
        
        // CLUSTER
        else if (tecnicaTipo === 'clusterset') {
            catVisual = 'technique-cluster'; // Usa a variável laranja
            labelVisual = 'CLUSTER';
            rowStyle = "background: rgba(255, 145, 0, 0.05);"; // Fundo sutil laranja
        }

        // Lógica de Reps (String composta ou número simples)
        let repsDisplay = registroPrincipal.reps_realizadas;
        if (dadosTecnicos.reps_string) repsDisplay = dadosTecnicos.reps_string;

        // Renderiza Linha Única (Normal, Rest ou Cluster)
        tabelaHtml += `
            <tr style="${rowStyle}">
                <td style="color: #666; font-weight: bold; padding: 10px; border-radius: 6px 0 0 6px;">#${item.numero}</td>
                <td style="padding: 10px;">
                    <span class="hist-badge ${catVisual}">${labelVisual}</span>
                </td>
                <td style="text-align:center; padding: 10px;">
                    <span style="color:#fff; font-weight:bold;">${parseFloat(registroPrincipal.carga_kg)}</span>
                </td>
                <td style="text-align:right; padding: 10px; border-radius: 0 6px 6px 0;">
                    <span style="color:#fff;">${repsDisplay}</span>
                </td>
            </tr>
        `;
    });

    tabelaHtml += `</tbody></table>`;
    lista.innerHTML = tabelaHtml;
    modal.style.display = 'flex';
}

/* ==========================================================================
   6. MÓDULO: TUTORIAIS VIDEOS
   ========================================================================== */

   // Função para abrir vídeos (YouTube)
function abrirModalVideo(videoId, titulo) {
    // 1. Cria o HTML do Iframe (Responsivo)
    const htmlVideo = `
        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; background:#000;">
            <iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0" 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border:0;" 
                    allow="autoplay; encrypted-media" allowfullscreen>
            </iframe>
        </div>
    `;

    // 2. Tenta usar o modal que já existe no seu sistema (geralmente no footer.php)
    // Se o ID do seu modal for diferente, ajuste aqui (ex: 'modalGerenciarAluno', etc)
    // O ideal é ter um modal genérico no footer.
    
    // Verifique se existe um modal genérico, senão criamos um dinâmico
    let modal = document.getElementById('modalVideoGlobal');
    
    if (!modal) {
        // Cria o modal dinamicamente se não existir no HTML
        modal = document.createElement('div');
        modal.id = 'modalVideoGlobal';
        modal.className = 'modal-overlay'; // Usa suas classes de CSS existentes
        modal.style.display = 'none';
        modal.onclick = function(e) { if(e.target === modal) fecharModalVideo(); };
        
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 800px; width: 95%; background: #1a1a1a; border: 1px solid var(--gold);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 id="modalVideoTitulo" style="color:var(--gold); margin:0;"></h3>
                    <button onclick="fecharModalVideo()" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <div id="modalVideoBody"></div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // 3. Preenche e Abre
    document.getElementById('modalVideoTitulo').innerText = titulo;
    document.getElementById('modalVideoBody').innerHTML = htmlVideo;
    
    modal.style.display = 'flex';
    // Pequeno delay para animação se tiver CSS de fade
    setTimeout(() => { modal.style.opacity = '1'; }, 10);
}

function fecharModalVideo() {
    const modal = document.getElementById('modalVideoGlobal');
    if (modal) {
        // Limpa o iframe para parar o som
        document.getElementById('modalVideoBody').innerHTML = '';
        modal.style.display = 'none';
    }
}

/* ==========================================================================
   7. MÓDULO: TREINOS PRONTOS (Biblioteca)
   ========================================================================== */

   function adotarModelo(tipoModelo) {
    // 1. Confirmação
    if(!confirm("Tem certeza que deseja adotar este treino? Ele será adicionado à sua lista.")) return;

    // 2. Feedback Visual (Loading)
    // Como estamos na página, podemos colocar um overlay ou mudar o texto do botão
    // Mas para simplificar, vamos usar o loader global se tiver, ou apenas o cursor
    document.body.style.cursor = 'wait';

    const formData = new FormData();
    formData.append('modelo', tipoModelo);

    fetch('actions/treino_modelo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.body.style.cursor = 'default';
        
        if(data.success) {
            // SUCESSO!
            // Redireciona para a lista de treinos ("Minhas Fichas")
            carregarConteudo('treinos'); 
            
            // Opcional: Toast ou Alerta bonito
            // alert('Treino criado com sucesso!'); 
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        document.body.style.cursor = 'default';
        console.error('Erro:', error);
        alert('Erro de conexão.');
    });
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/gerar_pdf.js"></script>
</body>
</html>