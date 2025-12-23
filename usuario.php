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
    
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/usuario.css">

    <?php include 'includes/head_main.php'; ?>

    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
</head>
<body>
    
    <div class="background-overlay"></div>

    <?php include 'includes/sidebar_usuario.php'; ?>

    <main id="conteudo">
        </main>

    <script>
        // Função Global de Navegação
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

                // Atualiza Menu Lateral
                // Pega só o nome da página (antes do &)
                const base = pagina.split('&')[0];
                botoes.forEach(btn => {
                    if (btn.dataset.pagina === base) btn.classList.add('active');
                    else btn.classList.remove('active');
                });

            } catch (err) {
                console.error(err);
                area.innerHTML = '<p class="error">Erro ao carregar.</p>';
            }
        };

        // Inicialização
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

        // Preview de Imagem (Perfil)
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
    </script>
    <script>
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
                    <button class="btn-back" onclick="voltarStepType()"><i class="fa-solid fa-arrow-left"></i></button>
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
    // Abre o modal. 
    // Se passar 'idAluno', é o admin abrindo para um aluno específico.
    function abrirModalAvaliacao(idAluno = null) {
        if (idAluno) {
            document.getElementById('av_aluno_id').value = idAluno;
        }
        document.getElementById('modalNovaAvaliacao').style.display = 'flex';
    }

    function fecharModalAvaliacao() {
        document.getElementById('modalNovaAvaliacao').style.display = 'none';
    }

    
   // --- LÓGICA DE UPLOAD BLINDADA ---
    
    // Configurações
    const TARGET_SIZE = 2 * 1024 * 1024; // Tenta comprimir se maior que 2MB
    const HARD_LIMIT = 15 * 1024 * 1024; // 15MB (Bloqueia envio se for maior que isso)
    const MAX_WIDTH = 1600; 
    const QUALITY = 0.7;

    // Monitora seleção de arquivos
    const fotoInput = document.getElementById('foto_input');
    if(fotoInput) {
        fotoInput.addEventListener('change', async function(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            // Feedback visual
            const label = document.querySelector('.upload-zone .upload-text');
            const originalText = "Adicionar Fotos"; // Texto padrão
            label.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Preparando fotos...';
            
            const dataTransfer = new DataTransfer();
            const previewArea = document.getElementById('preview-area');
            previewArea.innerHTML = ""; 

            // Processa cada arquivo individualmente
            for (let file of files) {
                
                // 1. Validação de Tipo
                if (!/\.(jpe?g|png|webp)$/i.test(file.name)) {
                    continue; // Ignora arquivos que não são imagem
                }

                // 2. Validação de Limite Extremo (Server Crash)
                if (file.size > HARD_LIMIT) {
                    alert(`A imagem "${file.name}" é GIGANTE (${(file.size/1024/1024).toFixed(1)}MB). O limite é 15MB. Ela será ignorada.`);
                    continue;
                }

                let finalFile = file;

                // 3. Tenta Comprimir se for grande
                if (file.size > TARGET_SIZE) {
                    try {
                        console.log(`Tentando comprimir ${file.name}...`);
                        finalFile = await compressImage(file);
                        console.log(`Sucesso: ${(finalFile.size/1024/1024).toFixed(2)}MB`);
                    } catch (err) {
                        console.warn("Falha na compressão, usando original:", err);
                        finalFile = file; // Fallback: usa a original se der erro
                    }
                }

                // Adiciona à lista final
                dataTransfer.items.add(finalFile);

                // Gera Preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'thumb-preview';
                    previewArea.appendChild(img);
                }
                reader.readAsDataURL(finalFile);
            }

            // Atualiza o input com a nova lista (processada ou original)
            document.getElementById('foto_input').files = dataTransfer.files;
            
            // Atualiza texto do botão
            const total = dataTransfer.files.length;
            if (total > 0) {
                label.innerText = total + (total === 1 ? ' foto pronta' : ' fotos prontas');
                document.querySelector('.upload-zone').style.borderColor = 'var(--gold)';
            } else {
                label.innerText = originalText;
            }
        });
    }

    // Função de Compressão
    function compressImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (event) => {
                const img = new Image();
                img.src = event.target.result;
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;

                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Erro no Canvas Blob'));
                            return;
                        }
                        const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(newFile);
                    }, 'image/jpeg', QUALITY);
                };
                img.onerror = (err) => reject(err);
            };
            reader.onerror = (err) => reject(err);
        });
    }

    // Validação no Envio (Submit)
    const formAvaliacao = document.querySelector('form[action*="avaliacao_add"]');
    if (formAvaliacao) {
        formAvaliacao.onsubmit = function(e) {
            const btn = document.querySelector('.btn-save-modal');
            
            // Feedback visual
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ENVIANDO...';
            btn.disabled = true;
            btn.style.opacity = "0.7";
            
            return true;
        };
    }
    </script>

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

    <script>
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
    </script>
    <script>
        window.toggleAccordion = function(id) {
        const card = document.getElementById(id);
        if (!card) return;

        // SELETOR CORRIGIDO: Agora busca pela classe certa
        const body = card.querySelector(".accordion-body");
        const arrow = card.querySelector(".accordion-arrow");
        
        if (body.style.display === "none" || body.style.display === "") {
            body.style.display = "block";
            card.classList.add("active");
            if(arrow) arrow.style.transform = "rotate(90deg)"; // Gira a setinha
        } else {
            body.style.display = "none";
            card.classList.remove("active");
            if(arrow) arrow.style.transform = "rotate(0deg)"; // Volta a setinha
        }
    };
    </script>
    <script>
    // --- LÓGICA DO DASHBOARD PROGRESSO (FIXED) ---

    // 1. Trocar Tabelas
    window.switchTable = function(tabName, btn) {
        document.querySelectorAll('.table-container').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-pill').forEach(el => el.classList.remove('active'));
        
        document.getElementById('tab-' + tabName).style.display = 'block';
        btn.classList.add('active');
    };

    // 2. Gráfico Master
    let masterChartInstance = null;
    let chartDataStore = null;

    window.initMasterChart = function() {
        const input = document.getElementById('chart-master-data');
        if (!input) return;
        
        try {
            chartDataStore = JSON.parse(input.value);
            // Inicia com Peso por padrão
            renderChart('peso');
        } catch (e) {
            console.error("Erro ao ler dados do gráfico", e);
        }
    };

    window.switchChart = function(metric, btn) {
        document.querySelectorAll('.chart-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
        renderChart(metric);
    };

    function renderChart(metric) {
        const ctx = document.getElementById('masterChart');
        if (!ctx || !chartDataStore) return;

        // Se já existe um gráfico, DESTRUA ele antes de criar outro
        if (masterChartInstance) {
            masterChartInstance.destroy();
        }

        // Configurações Visuais
        let label = 'Peso (kg)';
        let color = '#FFBA42'; // Gold
        let data = chartDataStore[metric];

        if (metric === 'bf') { label = '% Gordura'; color = '#ff4d4d'; } // Vermelho
        if (metric === 'magra') { label = 'Massa Magra (kg)'; color = '#00e676'; } // Verde

        // Monta o Gradiente
        const context = ctx.getContext('2d');
        const gradient = context.createLinearGradient(0, 0, 0, 300);
        
        // Conversão Hex para RGB simples para o gradiente
        let r=255, g=186, b=66; // Gold default
        if(metric === 'bf') { r=255; g=77; b=77; }
        if(metric === 'magra') { r=0; g=230; b=118; }

        gradient.addColorStop(0, `rgba(${r}, ${g}, ${b}, 0.5)`);
        gradient.addColorStop(1, `rgba(${r}, ${g}, ${b}, 0)`);

        masterChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartDataStore.labels,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: color,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#161616',
                    pointBorderColor: color,
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                layout: { padding: { top: 10, bottom: 10, left: 0, right: 10 } },
                scales: {
                    y: { 
                        grid: { color: 'rgba(255,255,255,0.05)' }, 
                        ticks: { color: '#888', font: { size: 11 } } 
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: '#888', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 6 } 
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    }
    </script>
    <script>
        // --- LÓGICA DE CHECK DA DIETA ---
    async function toggleRefeicao(refeicaoId, btn) {
        // 1. Efeito Visual Imediato (UX Rápida)
        const card = document.getElementById('ref_' + refeicaoId);
        const icon = btn.querySelector('i');
        
        // Alterna classes visualmente antes de esperar o servidor
        btn.classList.toggle('checked');
        card.classList.toggle('completed'); // Deixa o card meio transparente

        // 2. Envia para o Servidor (Background)
        try {
            const response = await fetch('actions/dieta_check.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ refeicao_id: refeicaoId })
            });

            const data = await response.json();
            
            // Se o servidor confirmar, ótimo. Se der erro, desfazemos.
            if (!response.ok) {
                throw new Error('Erro ao salvar');
            }

        } catch (error) {
            console.error(error);
            // Reverte o visual se deu erro (feedback de falha)
            btn.classList.toggle('checked');
            card.classList.toggle('completed');
            alert("Erro de conexão. Tente novamente.");
        }
    }
    </script>
    
    <script>
    // --- LÓGICA DO CRONÔMETRO ---
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
            clearInterval(timerInterval);
            isRunning = false;
            icon.classList.remove('fa-pause');
            icon.classList.add('fa-play');
            widget.classList.remove('running');
        } else {
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
        const icon = btn.querySelector('i');
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

    // --- LÓGICA DE ARRASTAR (CORRIGIDA) ---
    const dragItem = document.getElementById("float-timer");
    let active = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    // 1. O clique inicial TEM que ser no cronômetro
    dragItem.addEventListener("touchstart", dragStart, {passive: false});
    dragItem.addEventListener("mousedown", dragStart);

    // 2. O movimento e a soltura são no DOCUMENTO (Global)
    // Isso impede que o cronômetro "escape" se você mexer o mouse rápido
    document.addEventListener("touchend", dragEnd, {passive: false});
    document.addEventListener("touchmove", drag, {passive: false});
    document.addEventListener("mouseup", dragEnd);
    document.addEventListener("mousemove", drag);

    function dragStart(e) {
        // Não arrasta se clicar nos botões ou no X de fechar
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

        // Verifica se o clique foi realmente no widget
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
            e.preventDefault(); // Impede o celular de rolar a tela enquanto arrasta
        
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
</script>

<script>
    // --- LÓGICA DE FICHA COMPLETA ---

    function abrirModalPDFCompleto() {
        document.getElementById('modalPDFConfig').style.display = 'flex';
    }

    function selectPdfColor(el, color) {
        document.querySelectorAll('.color-pick').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('pdf_selected_color').value = color;
    }

    // --- FUNÇÃO DESENHISTA FINAL (LÓGICA DE NOME LIMPA) ---
    function renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores) {
        
        const { tema, fundo, borda } = configCores;
        const template = document.getElementById('template-impressao-full');
        
        template.querySelector('.pdf-sheet').style.backgroundColor = fundo;
        
        template.querySelector('#render-aluno-nome').innerText = nomeAluno;
        if(template.querySelector('#render-plano-nome')) {
            template.querySelector('#render-plano-nome').innerText = nomePlano.toUpperCase();
        }
        
        const headerMain = template.querySelector('#pdf-header-main');
        if(headerMain) headerMain.style.borderBottom = `4px solid ${tema}`;

        const container = document.getElementById('pdf-container-treinos');
        container.innerHTML = ''; 

        for (const [letra, conteudo] of Object.entries(dados)) {
            const exercicios = conteudo.exercicios;
            
            // 1. TÍTULO: O Dia vindo do BD
            let nomeDia = conteudo.dia_real; 

            // 2. SUBTÍTULO LIMPO:
            // Regra: Se tem nome, usa o nome. Se não tem, usa "TREINO X".
            let nomeTreinoBD = conteudo.nome ? conteudo.nome.trim() : "";
            let subtitulo = "";

            if (nomeTreinoBD && nomeTreinoBD !== "") {
                // Se existe nome, exibe APENAS o nome (Ex: "PEITO E TRICEPS")
                subtitulo = nomeTreinoBD;
            } else {
                // Se não tem nome, exibe o padrão (Ex: "TREINO A")
                subtitulo = `TREINO ${letra}`;
            }

            let htmlBlock = `
                <div class="day-block" style="page-break-inside: avoid; background: transparent; margin-bottom: 20px;">
                    
                    <div class="day-header" style="border-top: 2px solid ${borda}; border-right: 2px solid ${borda}; border-left: 2px solid ${borda}; background: ${tema}; text-align: center;">
                        <span class="day-title" style="color: #fff; font-weight:800;">${nomeDia}</span>
                    </div>

                    <div class="day-subheader" style="border-bottom: 1px solid ${borda}; margin-bottom: 10px; padding: 5px 10px;">
                        <span class="day-subtitle">
                            ${subtitulo}
                        </span>
                    </div>
                    
                    <div class="exercises-list">
            `;

            exercicios.forEach((ex, idx) => {
                let nomeEx = ex.nome_exercicio.toLowerCase();
                nomeEx = nomeEx.charAt(0).toUpperCase() + nomeEx.slice(1);

                htmlBlock += `
                    <div class="ex-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div class="ex-info-side" style="border-top: 1px solid ${borda}; border-right: 1px solid ${borda}; border-left: 1px solid ${borda}; background: ${tema};">
                            <span class="ex-text" style="font-family: 'Times New Roman', Times, serif; font-size: 13px;">
                                ${nomeEx}
                            </span>
                        </div>
                        <div class="ex-sets-side" style="width: 50%; display: flex; justify-content: flex-end; gap: 5px; flex-wrap: wrap;">
                `;

                if (ex.lista_series && ex.lista_series.length > 0) {
                    ex.lista_series.forEach(serie => {
                        const cat = serie.categoria.toLowerCase();
                        let label = "";

                        if (cat === 'warmup') { label = "Warm up"; } 
                        else if (cat === 'backoff') { label = "Back off"; } 
                        else { label = cat.charAt(0).toUpperCase() + cat.slice(1); }
                        label += " set";

                        const qtd = serie.quantidade ? serie.quantidade : 1;

                        htmlBlock += `<span class="set-box type-${serie.categoria}" style="border: none;">${qtd}x ${label}</span>`;
                    });
                } else {
                    htmlBlock += `<span style="font-size:10px; color:#ccc;">-</span>`;
                }

                htmlBlock += `</div></div>`;
            });

            htmlBlock += `</div></div>`; 
            container.innerHTML += htmlBlock;
        }
        
        return template;
    }

    // 2. AÇÃO: BAIXAR PDF
    function gerarFichaCompleta() {
        const nomeAluno = document.getElementById('pdf_aluno_nome').value;
        const nomePlano = document.getElementById('plano-nome-atual').value;
        
        // Pega as Cores Novas
        const configCores = {
            tema: document.getElementById('pdf_theme_color').value,
            fundo: document.getElementById('pdf_bg_color').value,
            borda: document.getElementById('pdf_border_color').value
        };

        const jsonRaw = document.getElementById('json-dados-treinos').value;
        const dados = JSON.parse(jsonRaw);

        // Renderiza
        const template = renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores);

        const btn = document.querySelector('#modalPDFConfig .btn-gold');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando...';
        btn.disabled = true;

        const opt = {
            margin: 0,
            filename: `Ficha_${nomeAluno}.pdf`,
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        template.style.display = 'block';

        html2pdf().set(opt).from(template).save().then(() => {
            template.style.display = 'none';
            btn.innerHTML = oldText;
            btn.disabled = false;
            document.getElementById('modalPDFConfig').style.display = 'none';
        });
    }

    // --- AÇÃO 3: PREVIEW ROBUSTO (COM ZOOM E SCROLL) ---
    function debugPreviewPDF() {
        // 1. Pega os dados
        const nomeAluno = document.getElementById('pdf_aluno_nome').value;
        const nomePlano = document.getElementById('plano-nome-atual').value;
        
        const configCores = {
            tema: document.getElementById('pdf_theme_color').value,
            fundo: document.getElementById('pdf_bg_color').value,
            borda: document.getElementById('pdf_border_color').value
        };

        const jsonRaw = document.getElementById('json-dados-treinos').value;
        const dados = JSON.parse(jsonRaw);

        // 2. Renderiza a ficha
        const template = renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores);

        // 3. LIMPEZA GERAL
        if (document.getElementById('pdf-viewer-overlay')) {
            document.getElementById('pdf-viewer-overlay').remove();
        }

        // 4. Esconde o Modal
        document.getElementById('modalPDFConfig').style.display = 'none';

        // 5. CRIA O OVERLAY COM TOOLBAR
        const overlay = document.createElement('div');
        overlay.id = 'pdf-viewer-overlay';
        overlay.className = 'pdf-viewer-overlay';
        
        // Toolbar HTML
        overlay.innerHTML = `
            <div class="pdf-toolbar">
                <div class="pdf-toolbar-title">
                    <i class="fa-solid fa-file-pdf"></i> Visualização
                </div>
                <div class="pdf-toolbar-actions">
                    <button class="btn-preview-action" id="btn-toggle-zoom" onclick="togglePdfZoom()">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> <span style="display:none;" id="zoom-text">Zoom</span>
                    </button>
                    <button class="btn-preview-action btn-preview-close" id="btn-close-final">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div id="pdf-content-wrapper" style="transition: all 0.3s ease;">
                </div>
        `;
        
        document.body.appendChild(overlay);

        // 6. Configura a Folha
        const wrapper = document.getElementById('pdf-content-wrapper');
        template.style.display = 'block';
        template.classList.add('preview-mode-active');
        wrapper.appendChild(template); // Move a ficha para dentro do wrapper

        // 7. LÓGICA DE ZOOM (FIT vs 100%)
        let isZoomed = false;
        const a4Width = 794; 
        const screenWidth = window.innerWidth;
        
        // Calcula o scale para "Caber na Tela"
        // Subtraímos 40px das margens laterais
        let fitScale = (screenWidth - 40) / a4Width;
        if (fitScale > 1) fitScale = 1; // Se a tela for grande, mantém 100%

        // Aplica o Zoom Inicial (Ajustado à tela)
        template.style.transform = `scale(${fitScale})`;
        
        // Ajusta a altura do wrapper para não ficar espaço em branco gigante em baixo
        // (Quando usamos scale, o elemento ocupa o espaço original no DOM, precisamos corrigir visualmente)
        const updateWrapperHeight = (scale) => {
            const originalHeight = template.offsetHeight;
            const scaledHeight = originalHeight * scale;
            // Define altura do wrapper e margem negativa para compensar o scale
            wrapper.style.height = `${scaledHeight}px`;
            wrapper.style.marginBottom = '50px'; // Um respiro no final
            // Margin-bottom negativa no template se necessário, mas ajustar o wrapper costuma bastar
        };
        
        // Timeout para garantir que o render terminou antes de medir altura
        setTimeout(() => updateWrapperHeight(fitScale), 100);

        // --- FUNÇÃO DO BOTÃO DE ZOOM ---
        window.togglePdfZoom = function() {
            isZoomed = !isZoomed;
            const btnIcon = document.querySelector('#btn-toggle-zoom i');
            
            if (isZoomed) {
                // MODO 100% (Leitura detalhada)
                template.style.transform = `scale(1)`;
                btnIcon.className = 'fa-solid fa-compress'; // Ícone de diminuir
                updateWrapperHeight(1);
            } else {
                // MODO AJUSTADO (Visão geral)
                template.style.transform = `scale(${fitScale})`;
                btnIcon.className = 'fa-solid fa-magnifying-glass-plus'; // Ícone de aumentar
                updateWrapperHeight(fitScale);
                overlay.scrollTop = 0; // Volta pro topo
            }
        };

        // --- FUNÇÃO FECHAR (BOTÃO DA TOOLBAR) ---
        document.getElementById('btn-close-final').onclick = function() {
            // Devolve o template escondido pro body
            document.body.appendChild(template);
            template.style.display = 'none';
            template.style.transform = 'none';
            template.classList.remove('preview-mode-active');
            
            // Remove Overlay
            overlay.remove();
            
            // Volta Modal
            document.getElementById('modalPDFConfig').style.display = 'flex';
        };
    }






    // ---------------------------------------------------------------
        // 3. EDITOR DE TREINOS (CRIAÇÃO)
        // ---------------------------------------------------------------

        function toggleNovoTreino() {
            const modal = document.getElementById('box-novo-treino');
            
            if (modal.style.display === 'none' || modal.style.display === '') {
                modal.style.display = 'flex'; // FLEX é essencial para centralizar
            } else {
                modal.style.display = 'none';
            }
        }

        // Fecha se clicar fora do modal (fundo escuro)
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('box-novo-treino');
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        function togglePeriodizacao() {
            var nivel = document.getElementById("selectNivel").value;
            var aviso = document.getElementById("aviso-periodizacao");
            if (nivel === "basico") { aviso.style.display = "none"; } 
            else { aviso.style.display = "block"; }
        }

        // Fecha a lista se clicar fora (Genérico para qualquer dropdown)
        window.addEventListener('click', function(e) {
            // Dropdown Treino
            let dropTreino = document.getElementById("dropdown-alunos-treino");
            let inputTreino = document.getElementById("busca-aluno-treino");
            if (dropTreino && e.target !== inputTreino && !dropTreino.contains(e.target)) {
                dropTreino.style.display = 'none';
            }
        });


        // ---------------------------------------------------------------
        // 4. PAINEL DO TREINO (ABAS, EXERCÍCIOS E PERIODIZAÇÃO)
        // ---------------------------------------------------------------
        
        // Gerenciamento de Abas (A, B, C)
        function openTab(evt, divName) {
            var i, content, tablinks;
            content = document.getElementsByClassName("division-content");
            for (i = 0; i < content.length; i++) { content[i].className = content[i].className.replace(" active", ""); }
            tablinks = document.getElementsByClassName("div-tab-btn");
            for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
            document.getElementById(divName).className += " active";
            evt.currentTarget.className += " active";
        }

        // --- MODAL EXERCÍCIO (Com Lista de Séries) ---
        let seriesArray = [];

        // Função para ABRIR NOVO (Limpa tudo)
        function openExercicioModal(divId, treinoId) {
            // Reseta formulário e variáveis
            document.getElementById("formExercicio").reset();
            document.getElementById("formExercicio").action = "actions/treino_add_exercicio.php"; // Ação de Adicionar
            document.getElementById("modal_divisao_id").value = divId;
            document.getElementById("modal_treino_id").value = treinoId;
            document.getElementById("modal_exercicio_id").value = ""; // Limpa ID
            
            document.querySelector("#modalExercicio .section-title").innerText = "Novo Exercício";
            document.querySelector("#modalExercicio .btn-gold[type='submit']").innerText = "SALVAR EXERCÍCIO";

            seriesArray = [];
            renderSetsList();
            
            document.getElementById("modalExercicio").style.display = "flex";
        }

        // Função para ABRIR EDIÇÃO (Preenche tudo)
        function editarExercicio(exData, treinoId, divId) {
            // Preenche campos simples
            document.getElementById("formExercicio").action = "actions/treino_edit_exercicio.php"; // Ação de Editar
            document.getElementById("modal_divisao_id").value = divId;
            document.getElementById("modal_treino_id").value = treinoId;
            document.getElementById("modal_exercicio_id").value = exData.id;
            
            document.querySelector("input[name='nome_exercicio']").value = exData.nome_exercicio;
            document.querySelector("select[name='tipo_mecanica']").value = exData.tipo_mecanica;
            document.querySelector("input[name='video_url']").value = exData.video_url || "";
            document.querySelector("input[name='observacao']").value = exData.observacao_exercicio || "";

            // Muda Título do Modal
            document.querySelector("#modalExercicio .section-title").innerText = "Editar Exercício";
            document.querySelector("#modalExercicio .btn-gold[type='submit']").innerText = "ATUALIZAR EXERCÍCIO";

            // Preenche as Séries
            seriesArray = [];
            if (exData.series && exData.series.length > 0) {
                exData.series.forEach(s => {
                    seriesArray.push({
                        qtd: s.quantidade,
                        tipo: s.categoria,
                        reps: s.reps_fixas || "",
                        desc: s.descanso_fixo || "",
                        rpe: s.rpe_previsto || ""
                    });
                });
            }
            renderSetsList();

            document.getElementById("modalExercicio").style.display = "flex";
        }

        function closeExercicioModal() {
            document.getElementById("modalExercicio").style.display = "none";
        }

        function addSetToList() {
            const qtd = document.getElementById("set_qtd").value;
            const tipo = document.getElementById("set_tipo").value;
            const reps = document.getElementById("set_reps").value;
            const desc = document.getElementById("set_desc").value;
            const rpe = document.getElementById("set_rpe").value;

            if(qtd > 0) {
                seriesArray.push({ qtd, tipo, reps, desc, rpe });
                renderSetsList();
            }
        }

        function renderSetsList() {
            const listDiv = document.getElementById("temp-sets-list");
            const jsonInput = document.getElementById("series_json_input");
            listDiv.innerHTML = "";
            
            if(seriesArray.length === 0) {
                listDiv.innerHTML = "<p style='color:#666; font-size:0.8rem; text-align:center; margin-top:10px;'>Nenhuma série adicionada.</p>";
            } else {
                seriesArray.forEach((s, index) => {
                    listDiv.innerHTML += `
                        <div class="temp-set-item">
                            <span><strong>${s.qtd}x</strong> ${s.tipo.toUpperCase()} (${s.reps} reps)</span>
                            <span style="color:#ff4242; cursor:pointer;" onclick="removeSet(${index})"><i class="fa-solid fa-times"></i></span>
                        </div>
                    `;
                });
            }
            jsonInput.value = JSON.stringify(seriesArray);
        }

        function removeSet(index) {
            seriesArray.splice(index, 1);
            renderSetsList();
        }

        // --- MODAL PERIODIZAÇÃO (Semana) ---
        function openMicroModal(micro, treinoId) {
        document.getElementById('micro_id').value = micro.id;
        document.getElementById('micro_treino_id').value = treinoId;
        
        document.getElementById('micro_fase').value = micro.nome_fase;
        document.getElementById('micro_foco').value = micro.foco_comentario;
        
        // Novos campos
        document.getElementById('micro_reps_comp').value = micro.reps_compostos;
        document.getElementById('micro_desc_comp').value = micro.descanso_compostos; 
        
        document.getElementById('micro_reps_iso').value = micro.reps_isoladores;
        document.getElementById('micro_desc_iso').value = micro.descanso_isoladores; 
        
        document.getElementById('span_semana_num').innerText = micro.semana_numero;
        document.getElementById('modalMicro').style.display = 'flex';
        }
        
        function closeMicroModal() {
            document.getElementById('modalMicro').style.display = 'none';
        }

        // Fechar qualquer modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = "none";
            }
        }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>