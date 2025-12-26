<?php
session_start();
require_once 'config/db_connect.php';

// Verifica se está logado e se é ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_conta'] !== 'admin') {
    // Se tentar entrar e não for admin, manda pro login ou pra área de usuário
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Administrador - Ryan Coach</title>
    
    <link rel="stylesheet" href="assets/css/user.css"> 
    <link rel="stylesheet" href="assets/css/staff.css">
    <link rel="stylesheet" href="assets/css/pdf.css">

    <?php include 'includes/head_main.php'; ?>
</head>
<body>
    
    <div class="background-overlay"></div>

    <?php include 'includes/sidebar_admin.php'; ?>

    <main id="conteudo"></main>


<!------------------------- HTML MODAL DE NOVA AVALIAÇÃO ---------------------------->
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
   ADMIN.JS - SCRIPT EXCLUSIVO DO PAINEL ADMINISTRATIVO
   ========================================================================== 
   ÍNDICE:
   1. NAVEGAÇÃO & CARREGAMENTO (AJAX)
   2. GESTÃO DE ALUNOS (Hub, Filtros e Modais)
   3. GESTÃO FINANCEIRA (Modais e Buscas)
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
        const response = await fetch(`ajax/get_admin_conteudo.php?pagina=${pagina}`);
        
        if (!response.ok) throw new Error('Erro na requisição');
        
        const html = await response.text();
        areaConteudo.innerHTML = html;
        areaConteudo.classList.remove('loading');

        // --- NOVO: LÓGICA DE RESTAURAÇÃO DE ABA ---
        // Verifica se temos uma aba salva na memória
        const lastTab = localStorage.getItem('lastActiveTab');
        
        // Se temos uma aba salva E ela existe no novo HTML carregado
        if (lastTab && document.getElementById(lastTab)) {
            // Chama a função openTab simulada (sem evento de clique)
            // Certifique-se de usar a versão nova do openTab que te mandei antes
            openTab(null, lastTab);
        }
        // ------------------------------------------

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

        // Re-executa scripts que vieram no HTML (Gráficos, etc)
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

let alunoSelecionadoAtual = null;

// Abre o "Hub" de opções ao clicar no aluno
function abrirPainelAluno(aluno) {
    alunoSelecionadoAtual = aluno;

    // Preenche cabeçalho do Hub
    const nomeEl = document.getElementById('hub-nome');
    const emailEl = document.getElementById('hub-email');
    const fotoEl = document.getElementById('hub-foto');

    if(nomeEl) nomeEl.innerText = aluno.nome;
    if(emailEl) emailEl.innerText = aluno.email;
    if(fotoEl) fotoEl.src = aluno.foto ? aluno.foto : 'assets/img/user-default.png';

    const modal = document.getElementById('modalGerenciarAluno');
    if(modal) modal.style.display = 'flex';
}

function fecharPainelAluno() {
    const modal = document.getElementById('modalGerenciarAluno');
    if(modal) modal.style.display = 'none';
}

// Roteador de ações do Hub
function hubAcao(acao) {
    if (!alunoSelecionadoAtual) {
        alert("Erro: Nenhum aluno selecionado.");
        return;
    }

    fecharPainelAluno();

    switch (acao) {
        case 'editar':
            openEditModal(alunoSelecionadoAtual);
            break;

        case 'historico':
            carregarConteudo('aluno_historico&id=' + alunoSelecionadoAtual.id);
            break;

        case 'avaliacao_lista':
            carregarConteudo('aluno_avaliacoes&id=' + alunoSelecionadoAtual.id);
            break;

        case 'dieta_editor':
            carregarConteudo('dieta_editor&id=' + alunoSelecionadoAtual.id);
            break;
            
        case 'excluir':
            if(confirm("Tem certeza que deseja excluir " + alunoSelecionadoAtual.nome + "?\nEssa ação não pode ser desfeita.")) {
                window.location.href = 'actions/admin_aluno.php?acao=excluir&id=' + alunoSelecionadoAtual.id;
            }
            break;
    }
}

// Modal de Edição Rápida
function openEditModal(aluno) {
    document.getElementById("edit_id").value = aluno.id;
    document.getElementById("edit_nome").value = aluno.nome;
    document.getElementById("edit_email").value = aluno.email;
    document.getElementById("edit_telefone").value = aluno.telefone;
    
    // Verifica campos opcionais
    const expiracao = document.getElementById("edit_expiracao");
    if(expiracao) expiracao.value = aluno.data_expiracao || "";
    
    const nivel = document.getElementById("edit_nivel");
    if(nivel) nivel.value = aluno.nivel || "aluno";
    
    document.getElementById("modalEditarAluno").style.display = "flex";
}

function closeEditModal() {
    document.getElementById("modalEditarAluno").style.display = "none";
}

// Filtro da Tabela de Alunos (Pesquisa visual)
function filtrarAlunos() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchAluno");
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
   3. GESTÃO FINANCEIRA
   ========================================================================== */

function alternarVisaoFinanceiro(visao) {
    const btnAdmin = document.getElementById("btn-tab-admin");
    const btnGlobal = document.getElementById("btn-tab-global");
    const tbodyAdmin = document.getElementById("tbody-admin");
    const tbodyGlobal = document.getElementById("tbody-global");

    if (visao === "admin") {
        tbodyAdmin.style.display = "table-row-group";
        tbodyGlobal.style.display = "none";
                    
        btnAdmin.style.background = "var(--gold)";
        btnAdmin.style.color = "#000";
        btnGlobal.style.background = "transparent";
        btnGlobal.style.color = "#888";
    } else {
        tbodyAdmin.style.display = "none";
        tbodyGlobal.style.display = "table-row-group";
                    
        btnGlobal.style.background = "#fff";
        btnGlobal.style.color = "#000";
        btnAdmin.style.background = "transparent";
        btnAdmin.style.color = "#888";
    }
    }

// Modal de Lançamento Financeiro
function openModal() {
    document.getElementById('modalLancamento').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modalLancamento').style.display = 'none';
}

// --- Busca de Usuário para Lançamento ---

// Filtra lista dropdown
window.filtrarUsuariosAdm = function() {
    let input = document.getElementById("busca-user-adm");
    let dropdown = document.getElementById("dropdown-users-adm");
    
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
        let texto = items[i].innerText || items[i].textContent;
        if (texto.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "flex";
            encontrou = true;
        } else {
            items[i].style.display = "none";
        }
    }
    if (!encontrou) dropdown.style.display = "none";
};

// Seleciona usuário da lista
window.selecionarUsuarioAdm = function(id, nome) {
    document.getElementById("busca-user-adm").value = nome;
    document.getElementById("id-user-adm").value = id;
    document.getElementById("dropdown-users-adm").style.display = "none";
};

// Fecha dropdown ao clicar fora
window.addEventListener('click', function(e) {
    let drop = document.getElementById("dropdown-users-adm");
    let input = document.getElementById("busca-user-adm");
    if (drop && input && e.target !== input && !drop.contains(e.target)) {
        drop.style.display = 'none';
    }
});


    // Preview simples das fotos selecionadas
    // function previewFiles() {
    //     const preview = document.getElementById('preview-area');
    //     const fileInput = document.getElementById('foto_input');
    //     const files = fileInput.files;
        
    //     preview.innerHTML = ""; // Limpa anterior

    //     if (files) {
    //         [].forEach.call(files, function(file) {
    //             if (/\.(jpe?g|png|gif)$/i.test(file.name)) {
    //                 const reader = new FileReader();
    //                 reader.addEventListener("load", function() {
    //                     const img = document.createElement('img');
    //                     img.src = this.result;
    //                     img.className = 'thumb-preview';
    //                     preview.appendChild(img);
    //                 });
    //                 reader.readAsDataURL(file);
    //             }
    //         });
    //     }
    // }        
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/staff.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>