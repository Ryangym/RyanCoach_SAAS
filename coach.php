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


    <!-- --------------------------------------------------->
    <!------- HTML DOS MODAIS DE AVALIAÇÃO FÍSICA ---------->
    <!-- --------------------------------------------------->
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


     
        <!--------- HTML DO MODAL DE NOVA AVALIAÇÃO ------------>
        <div id="modalNovaAvaliacao" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            
            <div class="modal-header-av">
                <button class="modal-close" onclick="fecharModalAvaliacao()">&times;</button>
                <h3><i class="fa-solid fa-ruler-combined"></i> NOVA AVALIAÇÃO</h3>
            </div>
            
            <form action="actions/avaliacao_add.php" method="POST" enctype="multipart/form-data">
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
    <!-- ---------------------------------------------------------------
        FIM DOS MODAIS DE AVALIAÇÃO FÍSICA
        --------------------------------------------------------------- -->


    <script>
        // ---------------------------------------------------------------
        // 1. NAVEGAÇÃO E SISTEMA (GLOBAL)
        // ---------------------------------------------------------------
        
        async function carregarConteudo(pagina) {
            const areaConteudo = document.getElementById('conteudo');

            // Logout direto
            if (pagina === 'logout') {
                window.location.href = 'actions/logout.php';
                return;
            }

            // Feedback visual
            areaConteudo.innerHTML = '<div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';
            areaConteudo.classList.add('loading');

            try {
                const response = await fetch(`ajax/get_coach_conteudo.php?pagina=${pagina}`);
                
                if (!response.ok) throw new Error('Erro na requisição');
                
                const html = await response.text();
                areaConteudo.innerHTML = html;
                areaConteudo.classList.remove('loading');

                // Atualiza botão ativo na sidebar
                // (Pega só a parte antes do '&' caso tenha parâmetros)
                const paginaBase = pagina.split('&')[0]; 
                const botoes = document.querySelectorAll('#main-aside button[data-pagina]');

                botoes.forEach(btn => {
                    if (btn.dataset.pagina === paginaBase) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

            } catch (error) {
                console.error(error);
                areaConteudo.innerHTML = '<p class="error">Erro ao carregar painel.</p>';
            }
        }

        // Inicialização ao carregar a página
        // Inicialização ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            const aside = document.getElementById('main-aside');
            
            // Verifica se voltou de um salvamento
            const params = new URLSearchParams(window.location.search);
            
            const pageParam = params.get('pagina'); 
            const idParam = params.get('id');
            const msgParam = params.get('msg'); // Opcional: Para mostrar alertas de sucesso

            let paginaInicial = 'dashboard'; // Padrão

            if (pageParam) {
                paginaInicial = pageParam;
                
                // Se tiver ID, adiciona na string para o carregarConteudo usar
                if (idParam) {
                    paginaInicial += '&id=' + idParam;
                }
                
                // Limpa a URL visualmente para não ficar suja (opcional)
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            // Se tiver mensagem de sucesso, pode exibir um alerta (Opcional)
            if (msgParam === 'sucesso') {
                // alert("Salvo com sucesso!"); // Descomente se quiser um feedback
            }

            // Evento de clique na Sidebar
            aside.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (btn && btn.dataset.pagina) {
                    carregarConteudo(btn.dataset.pagina);
                }
            });

            // Carrega a página correta (agora vai ler 'dieta_editor' em vez de cair no dashboard)
            carregarConteudo(paginaInicial);
        });

        // Preview de Foto (Perfil)
        function previewImageAdmin(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.getElementById("admin-preview");
                    if(img) img.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ---------------------------------------------------------------
        // 1. Gerenciamento DE ALUNOS 
        // ---------------------------------------------------------------
 // Variável global para guardar o aluno selecionado atualmente
                let alunoAtual = null;

                function abrirPainelAluno(aluno) {
                    alunoAtual = aluno; // Salva o obj aluno para usar nos botões
                    
                    // Preenche o Hub
                    document.getElementById("hub-nome").innerText = aluno.nome;
                    document.getElementById("hub-email").innerText = aluno.email;
                    document.getElementById("hub-foto").src = aluno.foto || "assets/img/user-default.png";
                    
                    document.getElementById("modalGerenciarAluno").style.display = "flex";
                }

                function fecharPainelAluno() {
                    document.getElementById("modalGerenciarAluno").style.display = "none";
                }

                // Roteador de Ações do Hub
                function hubAcao(acao) {
                    if(!alunoAtual) return;

                    if (acao === "historico") {
                        // Fecha modal e vai pro histórico
                        fecharPainelAluno();
                        carregarConteudo("aluno_historico&id=" + alunoAtual.id);
                    }
                    else if (acao === "avaliacao_lista") { 
                        // VAI PARA A LISTA DE AVALIAÇÕES (Essa parte faltava)
                        fecharPainelAluno();
                        carregarConteudo("aluno_avaliacoes&id=" + alunoAtual.id);
                    }
                    else if (acao === "avaliacao_nova") {
                        // Abre direto o modal de criar (atalho)
                        fecharPainelAluno();
                        abrirModalAvaliacao(alunoAtual.id);
                    }
                    else if (acao === "dieta_editor") {
                        // 1. Fecha o Hub (Modal do Aluno)
                        fecharPainelAluno();
                        
                        // 2. Carrega a tela do Editor de Dieta passando o ID do aluno atual
                        carregarConteudo("dieta_editor&id=" + alunoAtual.id);
                    }
                    else if (acao === "editar") {
                        // Fecha Hub e abre Editar (Preenche os dados)
                        fecharPainelAluno();
                        preencherModalEditar(alunoAtual);
                    }
                    else if (acao === "excluir") {
                        if(confirm("Tem certeza que deseja apagar o usuário " + alunoAtual.nome + "?")) {
                            window.location.href = "actions/admin_aluno.php?id=" + alunoAtual.id + "&acao=excluir";
                        }
                    }
                }

                // Função auxiliar para preencher o modal de edição
                function preencherModalEditar(aluno) {
                    document.getElementById("edit_id").value = aluno.id;
                    document.getElementById("edit_nome").value = aluno.nome;
                    document.getElementById("edit_email").value = aluno.email;
                    document.getElementById("edit_telefone").value = aluno.telefone;
                    document.getElementById("edit_expiracao").value = aluno.data_expiracao || "";
                    document.getElementById("edit_nivel").value = aluno.nivel || "aluno"; // Preenche o Select
                    
                    document.getElementById("modalEditarAluno").style.display = "flex";
                }

                function closeEditModal() {
                    document.getElementById("modalEditarAluno").style.display = "none";
                    // Reabre o Hub para não perder o fluxo? Opcional.
                    // abrirPainelAluno(alunoAtual); 
                }       



        // ---------------------------------------------------------------
        // 2. FINANCEIRO (MODAL)
        // ---------------------------------------------------------------

        // 1. Filtrar Alunos
        window.filtrarAlunosFinanceiro = function() {
            // Atenção aos IDs: devem ser iguais aos do PHP ("-fin")
            let input = document.getElementById("busca-aluno-fin");
            let dropdown = document.getElementById("dropdown-alunos-fin");
            
            // Proteção caso o modal ainda não tenha carregado
            if (!input || !dropdown) return;

            let filter = input.value.toUpperCase();
            let items = dropdown.getElementsByClassName("dropdown-item");

            // Se o campo estiver vazio, esconde a lista
            if (filter === "") {
                dropdown.style.display = "none";
                return;
            }

            // Mostra a lista e filtra
            dropdown.style.display = "block";
            let encontrou = false;

            for (let i = 0; i < items.length; i++) {
                let span = items[i].getElementsByTagName("span")[0];
                let txtValue = span.textContent || span.innerText;
                
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    items[i].style.display = "flex"; // Flex para manter alinhamento
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
            document.getElementById("id-aluno-fin").value = id; // Valor Real (Hidden)
            document.getElementById("dropdown-alunos-fin").style.display = "none"; // Fecha lista
        };

        // 3. Fechar ao clicar fora (Genérico)
        window.addEventListener('click', function(e) {
            let dropFin = document.getElementById("dropdown-alunos-fin");
            let inputFin = document.getElementById("busca-aluno-fin");
            
            // Se clicou fora do input e fora do dropdown
            if (dropFin && inputFin && e.target !== inputFin && !dropFin.contains(e.target)) {
                dropFin.style.display = 'none';
            }
        });

        // 4. Abrir e Fechar Modal
        window.openModal = function() {
            let modal = document.getElementById('modalLancamento');
            if(modal) modal.style.display = 'flex';
        };

        window.closeModal = function() {
            let modal = document.getElementById('modalLancamento');
            if(modal) modal.style.display = 'none';
        };


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
        // --- LÓGICA DO SELETOR DE ALUNOS (CRIAR TREINO) ---

        // Filtra a lista
        function filtrarAlunosTreino() {
            let input = document.getElementById("busca-aluno-treino");
            let filter = input.value.toUpperCase();
            let dropdown = document.getElementById("dropdown-alunos-treino");
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
                    items[i].style.display = ""; // Mostra
                    encontrou = true;
                } else {
                    items[i].style.display = "none"; // Esconde
                }
            }

            if (!encontrou) dropdown.style.display = "none";
        }

        // Seleciona o aluno
        function selecionarAlunoTreino(id, nome) {
            document.getElementById("busca-aluno-treino").value = nome; // Mostra nome visual
            document.getElementById("id-aluno-treino-selecionado").value = id; // Preenche ID oculto
            document.getElementById("dropdown-alunos-treino").style.display = "none"; // Fecha lista
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

        // FILTRO DE ALUNOS (Busca)
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

        // EDITAR ALUNO (Preencher Modal)
        function openEditModal(aluno) {
            document.getElementById("edit_id").value = aluno.id;
            document.getElementById("edit_nome").value = aluno.nome;
            document.getElementById("edit_email").value = aluno.email;
            document.getElementById("edit_telefone").value = aluno.telefone;
            document.getElementById("edit_expiracao").value = aluno.data_expiracao || "";
            
            document.getElementById("modalEditarAluno").style.display = "flex";
        }

        function closeEditModal() {
            document.getElementById("modalEditarAluno").style.display = "none";
        }

        
        function renomearDivisao(idDivisao, letra, nomeAtual) {
    // Pergunta o novo nome (Pode usar SweetAlert se tiver, aqui uso prompt nativo pra ser simples)
    const novoNome = prompt(`Qual o foco do TREINO ${letra}? (Ex: Peito e Tríceps)`, nomeAtual);

    if (novoNome !== null) { // Se não cancelou
        // Mostra carregando (opcional)
        const label = document.getElementById(`label_nome_div_${idDivisao}`);
        const textoOriginal = label.innerText;
        label.innerText = "Salvando...";

        fetch('actions/treino_rename_divisao.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: idDivisao, nome: novoNome })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualiza na tela imediatamente
                label.innerText = novoNome ? novoNome.toUpperCase() : "SEM NOME DEFINIDO";
                
                // Feedback visual rápido
                label.style.color = "#28a745"; // Verde
                setTimeout(() => label.style.color = "#666", 1000);
            } else {
                alert("Erro ao salvar: " + (data.message || "Erro desconhecido"));
                label.innerText = textoOriginal;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert("Erro de conexão.");
            label.innerText = textoOriginal;
        });
    }
}


        // ---------------------------------------------------------------
        // 5. MODAL DE NOVA AVALIAÇÃO FÍSICA
        // ---------------------------------------------------------------

        // --- LÓGICA DE AVALIAÇÃO FÍSICA (ADMIN) ---

    // 1. Abrir Modal
    function abrirModalAvaliacao(idAluno) {
        if (!idAluno) {
            alert("Erro: ID do aluno não identificado.");
            return;
        }
        // Preenche o input oculto com o ID do aluno que o admin clicou
        document.getElementById('av_aluno_id').value = idAluno;
        document.getElementById('modalNovaAvaliacao').style.display = 'flex';
    }

    function fecharModalAvaliacao() {
        document.getElementById('modalNovaAvaliacao').style.display = 'none';
        // Limpa o formulário para não ficar dados do aluno anterior
        document.getElementById('formAvaliacao').reset();
        document.getElementById('preview-area').innerHTML = "";
    }

    // 2. Lógica de Upload e Compressão (Mesma do Usuário)
    const MAX_WIDTH = 1600; 
    const QUALITY = 0.7;
    const TARGET_SIZE = 2 * 1024 * 1024;
    const HARD_LIMIT = 15 * 1024 * 1024;

    function previewFiles() {
        const fileInput = document.getElementById('foto_input');
        const previewArea = document.getElementById('preview-area');
        const files = fileInput.files;
        
        if (!files || files.length === 0) return;

        // Feedback
        const label = document.querySelector('.upload-zone .upload-text');
        label.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processando...';
        
        previewArea.innerHTML = ""; // Limpa anterior
        
        const dataTransfer = new DataTransfer();

        Array.from(files).forEach(async (file) => {
            // Validações
            if (!/\.(jpe?g|png|webp)$/i.test(file.name)) return;
            if (file.size > HARD_LIMIT) {
                alert(`Imagem "${file.name}" muito grande! Limite 15MB.`);
                return;
            }

            let finalFile = file;

            // Comprime se necessário
            if (file.size > TARGET_SIZE) {
                try {
                    finalFile = await compressImage(file);
                } catch (err) {
                    console.warn("Erro ao comprimir, usando original.", err);
                }
            }

            dataTransfer.items.add(finalFile);

            // Mostra Preview
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'thumb-preview';
                previewArea.appendChild(img);
            }
            reader.readAsDataURL(finalFile);
        });

        // Atualiza input com arquivos processados
        setTimeout(() => {
            fileInput.files = dataTransfer.files;
            label.innerText = fileInput.files.length + ' foto(s) pronta(s)';
        }, 500); // Pequeno delay para garantir o loop
    }

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
                        if (!blob) return reject(new Error('Erro Blob'));
                        resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', QUALITY);
                };
                img.onerror = reject;
            };
            reader.onerror = reject;
        });
    }

    // Feedback no Submit
    document.getElementById('formAvaliacao').onsubmit = function() {
        const btn = document.querySelector('.btn-save-modal');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> SALVANDO...';
        btn.disabled = true;
        btn.style.opacity = "0.7";
        return true;
    };

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

    //---------------------------------------------------------------
    // 6. MODAIS DE DIETA (REFEIÇÕES E ALIMENTOS)
    //---------------------------------------------------------------
    function abrirModalRefeicao(id) {
        document.getElementById("modal_dieta_id").value = id;
        document.getElementById("modalNovaRefeicao").style.display = "flex";
    }
    function fecharModalRefeicao() {
        document.getElementById("modalNovaRefeicao").style.display = "none";
    }

    function abrirModalAlimento(id) {
        document.getElementById("modal_refeicao_id").value = id;
        document.getElementById("modalNovoAlimento").style.display = "flex";
    }
    function fecharModalAlimento() {
        document.getElementById("modalNovoAlimento").style.display = "none";
    }

    function abrirModalImportar() {
        document.getElementById("modalImportar").style.display = "flex";
    }
    function fecharModalImportar() {
        document.getElementById("modalImportar").style.display = "none";
    }
    
    </script>


    <script>
        // --- LÓGICA DO MODAL DE AVALIAÇÃO ---

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

    // Preview simples das fotos selecionadas
    function previewFiles() {
        const preview = document.getElementById('preview-area');
        const fileInput = document.getElementById('foto_input');
        const files = fileInput.files;
        
        preview.innerHTML = ""; // Limpa anterior

        if (files) {
            [].forEach.call(files, function(file) {
                if (/\.(jpe?g|png|gif)$/i.test(file.name)) {
                    const reader = new FileReader();
                    reader.addEventListener("load", function() {
                        const img = document.createElement('img');
                        img.src = this.result;
                        img.className = 'thumb-preview';
                        preview.appendChild(img);
                    });
                    reader.readAsDataURL(file);
                }
            });
        }
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>