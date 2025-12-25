/* ==========================================================================
   MAIN.JS - SCRIPT COMPARTILHADO ENTRE TODOS DIFERENTES TIPOS DE USUARIO (RYAN COACH SAAS)
   ========================================================================== 
   
   ÍNDICE:
   1. EDITOR DE TREINOS (Criação e Configuração)
   2. PAINEL DO TREINO (Abas, Exercícios e Periodização)
   3. MODAL DE AVALIAÇÃO FÍSICA (Upload e Gráficos)
   4. MODAIS DE DIETA (Refeições e Alimentos)
   ========================================================================== */

/* ==========================================================================
   1. EDITOR DE TREINOS (CRIAÇÃO)
   ========================================================================== */

// Abre/Fecha Modal de Novo Treino
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

// Mostra aviso de periodização conforme nível selecionado
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

/* ==========================================================================
   2. PAINEL DO TREINO (ABAS, EXERCÍCIOS E PERIODIZAÇÃO)
   ========================================================================== */

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

/* ==========================================================================
   3. MODAL DE NOVA AVALIAÇÃO FÍSICA
   ========================================================================== */

// Lógica de Upload e Compressão (Código geral)
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

/* ==========================================================================
   4. MODAIS DE DIETA (REFEIÇÕES E ALIMENTOS)
   ========================================================================== */

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
