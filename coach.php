<?php
session_start();
require_once 'config/db_connect.php';

// 1. VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['tipo_conta'] !== 'personal' && $_SESSION['tipo_conta'] !== 'coach' && $_SESSION['tipo_conta'] !== 'admin') {
    header("Location: usuario.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Área do Coach</title>
    
    <link rel="stylesheet" href="assets/css/user.css"> 
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/pdf.css">

    <?php include 'includes/head_main.php'; ?>
</head>
<body>
    <div class="background-overlay"></div>

    <?php include 'includes/sidebar_admin.php'; ?>

    <main id="conteudo"></main>


<div id="modalNovaAvaliacao" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        
        <div class="modal-header-av">
            <button class="modal-close" onclick="fecharModalAvaliacao()">&times;</button>
            <h3><i class="fa-solid fa-ruler-combined"></i> NOVA AVALIAÇÃO</h3>
        </div>
        
        <form action="actions/avaliacao_add.php" method="POST" enctype="multipart/form-data" id="formAvaliacao">
            <input type="hidden" name="aluno_id" id="av_aluno_id" required>

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
   COACH.JS - SCRIPT EXCLUSIVO DO PAINEL DO TREINADOR
   ========================================================================== 
   ÍNDICE:
   1. NAVEGAÇÃO & CARREGAMENTO (AJAX)
   2. GESTÃO DE ALUNOS (Hub, Ações e Filtros)
   3. GESTÃO FINANCEIRA (Seletor de Alunos e Modal)
   ========================================================================== */


/* ==========================================================================
   1. NAVEGAÇÃO & CARREGAMENTO
   ========================================================================== */

async function carregarConteudo(pagina) {
    const areaConteudo = document.getElementById('conteudo');

    // Logout direto via JS
    if (pagina === 'logout') {
        window.location.href = 'actions/logout.php';
        return;
    }

    // Feedback visual (Loading)
    areaConteudo.innerHTML = '<div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';
    areaConteudo.classList.add('loading');

    try {
        const response = await fetch(`ajax/get_coach_conteudo.php?pagina=${pagina}`);
        
        if (!response.ok) throw new Error('Erro na requisição');
        
        const html = await response.text();
        areaConteudo.innerHTML = html;
        areaConteudo.classList.remove('loading');

        // Atualiza botão ativo na sidebar
        const paginaBase = pagina.split('&')[0]; 
        const botoes = document.querySelectorAll('#main-aside button[data-pagina]');

        botoes.forEach(btn => {
            if (btn.dataset.pagina === paginaBase) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Re-executa scripts que vieram no HTML (Gráficos, Tabelas)
        const scripts = areaConteudo.querySelectorAll("script");
        scripts.forEach(s => {
            const newScript = document.createElement("script");
            if (s.src) newScript.src = s.src;
            else newScript.textContent = s.textContent;
            document.body.appendChild(newScript);
        });

    } catch (error) {
        console.error(error);
        areaConteudo.innerHTML = '<p class="error">Erro ao carregar painel.</p>';
    }
}

// Inicialização ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    const aside = document.getElementById('main-aside');
    
    // Verifica parâmetros de URL (ex: retorno de save)
    const params = new URLSearchParams(window.location.search);
    const pageParam = params.get('pagina'); 
    const idParam = params.get('id');
    const msgParam = params.get('msg'); 

    let paginaInicial = 'dashboard'; // Padrão

    if (pageParam) {
        paginaInicial = pageParam;
        if (idParam) paginaInicial += '&id=' + idParam;
        
        // Limpa URL visualmente
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Feedback opcional de sucesso
    if (msgParam === 'sucesso') {
        // console.log("Operação realizada com sucesso.");
    }

    // Evento de clique na Sidebar
    if (aside) {
        aside.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (btn && btn.dataset.pagina) {
                carregarConteudo(btn.dataset.pagina);
            }
        });
    }

    // Carrega a página inicial
    carregarConteudo(paginaInicial);
});

/* ==========================================================================
   2. GESTÃO DE ALUNOS (HUB & AÇÕES)
   ========================================================================== */

let alunoAtual = null; // Variável global para guardar o aluno selecionado

function abrirPainelAluno(aluno) {
    alunoAtual = aluno; 
    
    // Preenche o Hub
    const nomeEl = document.getElementById("hub-nome");
    const emailEl = document.getElementById("hub-email");
    const fotoEl = document.getElementById("hub-foto");

    if (nomeEl) nomeEl.innerText = aluno.nome;
    if (emailEl) emailEl.innerText = aluno.email;
    if (fotoEl) fotoEl.src = aluno.foto || "assets/img/user-default.png";
    
    const modal = document.getElementById("modalGerenciarAluno");
    if (modal) modal.style.display = "flex";
}

function fecharPainelAluno() {
    const modal = document.getElementById("modalGerenciarAluno");
    if (modal) modal.style.display = "none";
}

// Roteador de Ações do Hub
function hubAcao(acao) {
    if(!alunoAtual) return;

    // Fecha o modal antes de navegar
    fecharPainelAluno();

    switch (acao) {
        case "historico":
            carregarConteudo("aluno_historico&id=" + alunoAtual.id);
            break;

        case "avaliacao_lista": 
            carregarConteudo("aluno_avaliacoes&id=" + alunoAtual.id);
            break;

        case "avaliacao_nova":
            // Requer staff.js carregado
            if (typeof abrirModalAvaliacao === "function") {
                abrirModalAvaliacao(alunoAtual.id);
            } else {
                console.error("Função abrirModalAvaliacao não encontrada. Verifique se staff.js está carregado.");
            }
            break;

        case "dieta_editor":
            carregarConteudo("dieta_editor&id=" + alunoAtual.id);
            break;
            
        case "treino_editor":
             // Se tiver essa opção no futuro
             // carregarConteudo("treino_editor&id=" + alunoAtual.id);
             break;
    }
}

// Filtro da Tabela de Alunos (Pesquisa visual)
function filtrarAlunos() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchAluno");
    
    if(!input) return;

    filter = input.value.toUpperCase();
    table = document.getElementById("tabelaAlunos");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        var tdNome = tr[i].getElementsByTagName("td")[0];
        if (tdNome) {
            txtValue = tdNome.textContent || tdNome.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
}

/* ==========================================================================
   3. GESTÃO FINANCEIRA (SELETOR & MODAL)
   ========================================================================== */

// 1. Filtrar Alunos no Dropdown Financeiro
window.filtrarAlunosFinanceiro = function() {
    let input = document.getElementById("busca-aluno-fin");
    let dropdown = document.getElementById("dropdown-alunos-fin");
    
    if (!input || !dropdown) return;

    let filter = input.value.toUpperCase();
    let items = dropdown.getElementsByClassName("dropdown-item");

    if (filter === "") {
        dropdown.style.display = "none";
        return;
    }

    dropdown.style.display = "block";
    let encontrou = false;

    for (let i = 0; i < items.length; i++) {
        let span = items[i].getElementsByTagName("span")[0];
        let txtValue = span.textContent || span.innerText;
        
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "flex"; 
            encontrou = true;
        } else {
            items[i].style.display = "none";
        }
    }

    if (!encontrou) dropdown.style.display = "none";
};

// 2. Selecionar Aluno
window.selecionarAlunoFinanceiro = function(id, nome) {
    document.getElementById("busca-aluno-fin").value = nome; // Visual
    document.getElementById("id-aluno-fin").value = id; // Hidden
    document.getElementById("dropdown-alunos-fin").style.display = "none"; 
};

// 3. Fechar ao clicar fora (Dropdown Financeiro)
window.addEventListener('click', function(e) {
    let dropFin = document.getElementById("dropdown-alunos-fin");
    let inputFin = document.getElementById("busca-aluno-fin");
    
    if (dropFin && inputFin && e.target !== inputFin && !dropFin.contains(e.target)) {
        dropFin.style.display = 'none';
    }
});

// 4. Modal de Lançamento
window.openModal = function() {
    let modal = document.getElementById('modalLancamento');
    if(modal) modal.style.display = 'flex';
};

window.closeModal = function() {
    let modal = document.getElementById('modalLancamento');
    if(modal) modal.style.display = 'none';
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/staff.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>