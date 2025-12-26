<?php
session_start();
require_once 'config/db_connect.php';

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
    const botoes = document.querySelectorAll('#main-aside button');

    // Feedback Visual
    area.innerHTML = '<div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';
    area.classList.add('loading');

    try {
        // Requisição Limpa
        const req = await fetch(`ajax/get_conteudo.php?pagina=${pagina}`);
        if (!req.ok) throw new Error('Erro na rede');
            
        const html = await req.text();
            
        area.innerHTML = html;
        area.classList.remove('loading');

        // --- 1. LÓGICA DE RESTAURAÇÃO DE ABA (NOVO) ---
        // Verifica se tem aba salva na memória do navegador
        const lastTab = localStorage.getItem('lastActiveTab');
        
        // Se existe aba salva E o elemento existe na nova tela carregada
        if (lastTab && document.getElementById(lastTab)) {
            // Chama a função openTab (verifique se ela está acessível neste escopo)
            if (typeof openTab === 'function') {
                openTab(null, lastTab);
            }
        }

        const scripts = area.querySelectorAll("script");
        scripts.forEach(s => {
            const newScript = document.createElement("script");
            if (s.src) newScript.src = s.src;
            else newScript.textContent = s.textContent;
            document.body.appendChild(newScript);
        });

        // Atualiza Menu Lateral
        const base = pagina.split('&')[0];
        botoes.forEach(btn => {
            // Verifica se o dataset existe antes de comparar
            if (btn.dataset.pagina === base) btn.classList.add('active');
            else btn.classList.remove('active');
        });

    } 
    catch (err) {
        console.error(err);
        area.innerHTML = '<p class="error">Erro ao carregar.</p>';
    }
};
    
    // TELA INICIAL AO ABRIR A PAGINA (DASHBOARD)
    document.addEventListener('DOMContentLoaded', () => {
        carregarConteudo('dashboard'); // Ou 'treinos' se preferir iniciar lá

        // Listener do Menu Lateral
        document.getElementById('main-aside').addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (btn && btn.dataset.pagina) {
                if (btn.dataset.pagina === 'logout') window.location.href = 'actions/logout.php';
                else carregarConteudo(btn.dataset.pagina);
            }
        });
    });

    // LÓGICA DE Preview de Imagem (Perfil)
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
    
<!-- ----------------- HTML CRONÔMETRO ------------------------------->

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

    <script>
/* ==========================================================================
   USUARIO.JS - SCRIPT EXCLUSIVO DO PAINEL DO ATLETA
   ========================================================================== 
   ÍNDICE:
   1. MÓDULO: AVALIAÇÃO FÍSICA (Visualização e Modal)
   2. MÓDULO: DIETA (Check de Refeições)
   3. MÓDULO: CRONÔMETRO FLUTUANTE (Timer & Drag)
   4. MÓDULO: GESTÃO DE COACH (Vincular)
   5. MÓDULO: HISTÓRICO (DELETE & EDIT)
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

function fecharTimer() {
    document.getElementById('float-timer').style.display = 'none';
    resetTimer();
}

function toggleTimer() {
    const btn = document.getElementById('btn-timer-toggle');
    const icon = btn.querySelector('i');
    const widget = document.getElementById('float-timer');

    if (isRunning) {
        // Pausar
        clearInterval(timerInterval);
        isRunning = false;
        icon.classList.remove('fa-pause');
        icon.classList.add('fa-play');
        widget.classList.remove('running');
    } else {
        // Iniciar
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
    updateTimerDisplay();
    
    const btn = document.getElementById('btn-timer-toggle');
    const icon = btn ? btn.querySelector('i') : null;
    const widget = document.getElementById('float-timer');
    
    if(icon) {
        icon.classList.remove('fa-pause');
        icon.classList.add('fa-play');
    }
    if(widget) widget.classList.remove('running');
}

function updateTimerDisplay() {
    const display = document.getElementById('timer-val');
    if (!display) return;
    
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    display.innerText = (mins < 10 ? "0" : "") + mins + ":" + (secs < 10 ? "0" : "") + secs;
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

/* ==========================================================================
   4. MÓDULO: GESTÃO DE COACH
   ========================================================================== */

function abrirModalVincular() {
    const modal = document.getElementById("modalVincularCoach");
    if (modal) {
        // Mover para o body para garantir que fixed posicione relativo à viewport
        document.body.appendChild(modal); 
        
        // Exibir e travar scroll
        modal.style.display = "flex";
        document.body.style.overflow = "hidden"; 
    }
}

function fecharModalVincular() {
    const modal = document.getElementById("modalVincularCoach");
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = ""; // Restaurar scroll
    }
}

/* ==========================================================================
   5. MÓDULO: HISTÓRICO (DELETE & EDIT)
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
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>