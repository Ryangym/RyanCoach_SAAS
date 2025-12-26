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

// Gerenciamento de Abas (A, B, C) com memória
function openTab(evt, divName) {
    var i, content, tablinks;

    // 1. Esconde todos os conteúdos
    content = document.getElementsByClassName("division-content");
    for (i = 0; i < content.length; i++) {
        content[i].className = content[i].className.replace(" active", "");
    }

    // 2. Remove classe 'active' de todos os botões
    tablinks = document.getElementsByClassName("div-tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // 3. Mostra o conteúdo atual
    var targetDiv = document.getElementById(divName);
    if(targetDiv) {
        targetDiv.className += " active";
    }

    // 4. Ativa o botão clicado
    // Se a função foi chamada por clique (evt existe)
    if (evt && evt.currentTarget) {
        evt.currentTarget.className += " active";
    } else {
        // Se foi chamada via código (restauração), precisamos achar o botão manualmente
        // Procura o botão que tem o onclick correspondente
        for (i = 0; i < tablinks.length; i++) {
            if(tablinks[i].getAttribute('onclick').includes(divName)) {
                tablinks[i].className += " active";
                break;
            }
        }
    }

    // 5. SALVA NO LOCALSTORAGE (O Pulo do Gato)
    // Salva apenas se for um clique real do usuário
    if(evt) {
        localStorage.setItem('lastActiveTab', divName);
    }
}

/* ==========================================================================
   GESTÃO DE EXERCÍCIOS (SINGLE / BI-SET / TRI-SET) - VERSÃO FINAL OTIMIZADA
   ========================================================================== */

// Estado Global
let exerciseState = []; // Array que guarda os objetos {nome, mecanica, series: []}
let activeTabIndex = 0; // Qual aba está visível (0, 1 ou 2)
let currentMode = 'single'; // single, biset, triset

// --- 1. INICIALIZAÇÃO ---

function openExercicioModal(divId, treinoId) {
    document.getElementById("formExercicio").reset();
    document.getElementById("modal_divisao_id").value = divId;
    document.getElementById("modal_treino_id").value = treinoId;
    document.getElementById("modal_exercicio_id").value = ""; 
    
    document.querySelector("#modalExercicio .section-title").innerText = "Novo Exercício";
    
    // GARANTE QUE O BOTÃO VOLTE AO ORIGINAL
    const btnSave = document.getElementById("btn-save-modal");
    if(btnSave) {
        btnSave.innerText = "SALVAR TUDO";
    }

    initBlockState('single');
    document.getElementById("modalExercicio").style.display = "flex";
}

function initBlockState(mode) {
    currentMode = mode;
    activeTabIndex = 0;
    
    // Define quantidade baseada no modo
    const count = (mode === 'triset') ? 3 : (mode === 'biset') ? 2 : 1;
    
    // Cria estrutura vazia
    exerciseState = [];
    for(let i=0; i<count; i++) {
        exerciseState.push({
            nome: '', mecanica: 'livre', video: '', obs: '', series: []
        });
    }
    
    // Atualiza UI
    updateTabButtons();
    loadDataToInputs(0); // Carrega o primeiro
}

// --- 2. NAVEGAÇÃO DE ABAS ---

function switchTab(newIndex) {
    if (newIndex === activeTabIndex) return;
    
    // 1. Salva o que está na tela no array antes de sair
    saveInputsToData(activeTabIndex);
    
    // 2. Carrega os dados da nova aba
    activeTabIndex = newIndex;
    loadDataToInputs(newIndex);
    
    // 3. Atualiza botões visuais
    updateTabButtons();
}

function updateTabButtons() {
    const container = document.getElementById("exercise-tabs-container");
    const modeBtns = document.querySelectorAll(".btn-type-select");
    
    // 1. Remove classe ativa de todos
    modeBtns.forEach(b => b.classList.remove('active'));

    // 2. Define qual botão iluminar
    // Se for 'edit_single', iluminamos o botão 'single'. Se não, usa o próprio nome.
    const targetMode = (currentMode === 'edit_single') ? 'single' : currentMode;
    
    // 3. Tenta encontrar o botão
    const activeBtn = document.getElementById(`btn-mode-${targetMode}`);
    
    // 4. Adiciona classe ativa (COM SEGURANÇA)
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // 5. Lógica de mostrar/esconder abas
    // Se for single ou edit_single, esconde as abas
    if (currentMode === 'single' || currentMode === 'edit_single') {
        if(container) container.style.display = 'none';
        return;
    }

    // Renderiza abas A / B / C
    if(container) {
        container.style.display = 'flex';
        container.innerHTML = '';
        const labels = ['Exercício A', 'Exercício B', 'Exercício C'];
        
        exerciseState.forEach((_, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerText = labels[idx];
            btn.style.cssText = `flex:1; padding:8px; border:1px solid #333; cursor:pointer; background: ${idx === activeTabIndex ? 'rgba(255, 186, 66, 0.1)' : 'transparent'}; color: ${idx === activeTabIndex ? 'var(--gold)' : '#888'}; border-bottom: ${idx === activeTabIndex ? '2px solid var(--gold)' : '1px solid #333'}`;
            
            btn.onclick = () => switchTab(idx);
            container.appendChild(btn);
        });
    }
}

// --- 3. MANIPULAÇÃO DE DADOS (DOM <-> MEMÓRIA) ---

function saveInputsToData(index) {
    if (!exerciseState[index]) return;

    // Pega valores dos inputs
    exerciseState[index].nome = document.getElementById("inp_nome").value;
    exerciseState[index].mecanica = document.getElementById("inp_mecanica").value;
    exerciseState[index].video = document.getElementById("inp_video").value;
    exerciseState[index].obs = document.getElementById("inp_obs").value;
    
    // Nota: As séries já são salvas diretamente no exerciseState[index].series pelas funções addSetToList/removeSet
}

function loadDataToInputs(index) {
    const data = exerciseState[index];
    if (!data) return;

    // Joga valores nos inputs
    document.getElementById("inp_nome").value = data.nome;
    document.getElementById("inp_mecanica").value = data.mecanica;
    document.getElementById("inp_video").value = data.video;
    document.getElementById("inp_obs").value = data.obs;
    
    // Atualiza a lista visual de séries
    renderSetsList(index);
    
    // Reseta inputs de adição de série
    document.getElementById("set_tipo").value = 'work';
    toggleTechniqueFields();
}

// --- 4. LÓGICA DE SÉRIES ---

function toggleTechniqueFields() {
    const tipo = document.getElementById("set_tipo").value;
    document.getElementById("div_drops").style.display = (tipo === 'dropset') ? 'block' : 'none';
    document.getElementById("div_pause").style.display = (tipo === 'restpause') ? 'block' : 'none';
    document.getElementById("div_cluster").style.display = (tipo === 'clusterset') ? 'block' : 'none';
}

// --- CORREÇÃO NA LÓGICA DE SÉRIES ---
function addSetToList() {
    // 1. Pega os valores crus dos inputs
    const qtd = document.getElementById("set_qtd").value;
    const tipoSelecionado = document.getElementById("set_tipo").value; // Ex: 'dropset'
    let reps = document.getElementById("set_reps").value;
    const desc = document.getElementById("set_desc").value;
    const rpe = document.getElementById("set_rpe").value;
    
    // 2. Variáveis Finais para o Banco de Dados
    let categoriaFinal = tipoSelecionado; // Por padrão, é o que foi selecionado
    let tecnicaFinal = 'normal';
    let valorTecnica = '';

    // 3. TRADUÇÃO: Se for técnica, define categoria como 'work' e preenche a técnica
    if (tipoSelecionado === 'dropset') {
        categoriaFinal = 'work'; // DB só aceita ENUM('work', etc)
        tecnicaFinal = 'dropset';
        valorTecnica = document.getElementById("set_drops_qtd").value;
    } 
    else if (tipoSelecionado === 'restpause') {
        categoriaFinal = 'work';
        tecnicaFinal = 'restpause';
        valorTecnica = document.getElementById("set_rest_time").value;
    } 
    else if (tipoSelecionado === 'clusterset') {
        categoriaFinal = 'work';
        tecnicaFinal = 'clusterset';
        const b = document.getElementById("set_cluster_blocos").value || 1;
        const r = document.getElementById("set_cluster_reps").value || 1;
        const p = document.getElementById("set_cluster_rest").value || 10;
        valorTecnica = `${b}|${r}|${p}`;
        
        // Formata visualmente as reps para cluster (ex: 4x3)
        // Mas mantém o campo reps original se o usuário digitou algo
        if(!reps) reps = `${b}x${r}`; 
    }

    // 4. Adiciona ao Array Global (Só se tiver quantidade)
    if (qtd > 0) {
        exerciseState[activeTabIndex].series.push({
            qtd: qtd, 
            tipo: categoriaFinal, // Vai enviar 'work' em vez de 'dropset'
            reps: reps, 
            desc: desc, 
            rpe: rpe, 
            tecnica: tecnicaFinal, // Vai enviar 'dropset' aqui
            tecnica_valor: valorTecnica 
        });
        
        renderSetsList(activeTabIndex);
    }
}

function renderSetsList(index) {
    const listDiv = document.getElementById("temp-sets-list");
    const series = exerciseState[index].series;
    
    listDiv.innerHTML = "";
    
    if (series.length === 0) {
        listDiv.innerHTML = "<div style='text-align:center; color:#666; padding:10px;'>Nenhuma série.</div>";
        return;
    }

    series.forEach((s, sIdx) => {
        let label = s.tipo.toUpperCase();
        if (s.tecnica !== 'normal') label += ` <span style="color:var(--gold)">(${s.tecnica})</span>`;
        
        listDiv.innerHTML += `
            <div style="display:flex; justify-content:space-between; padding:5px; border-bottom:1px solid #333;">
                <span><b>${s.qtd}x</b> ${label} <small>(${s.reps})</small></span>
                <span style="color:#ff4d4d; cursor:pointer;" onclick="removeSet(${index}, ${sIdx})">&times;</span>
            </div>
        `;
    });
}

function removeSet(exIndex, setIndex) {
    exerciseState[exIndex].series.splice(setIndex, 1);
    // Só renderiza se for a aba atual
    if (exIndex === activeTabIndex) renderSetsList(exIndex);
}

// --- 5. FUNÇÃO DE SALVAR (NOVO NOME, SEM CONFLITO) ---

function salvarBlocoExercicios() {
    // 1. Salva estado atual da tela para a memória
    saveInputsToData(activeTabIndex);
    
    // 2. Validação
    for(let i=0; i<exerciseState.length; i++) {
        if (!exerciseState[i].nome || exerciseState[i].nome.trim() === "") {
            alert(`O nome do Exercício ${i+1} é obrigatório.`);
            switchTab(i);
            return;
        }
    }

    // 3. Pega o ID (Importante para edição)
    const exId = document.getElementById("modal_exercicio_id").value;

    // 4. Monta Payload JSON (Adicionando o exercicio_id)
    const payload = {
        divisao_id: document.getElementById("modal_divisao_id").value,
        treino_id: document.getElementById("modal_treino_id").value,
        exercicio_id: exId, // <--- CRUCIAL: Envia o ID para o PHP saber quem atualizar
        mode: currentMode,
        exercises: exerciseState
    };

    console.log("Enviando:", payload);

    // 5. Define a URL dinamicamente
    const url = (currentMode === 'edit_single' && exId) 
        ? 'actions/treino_edit_exercicio.php' 
        : 'actions/treino_add_exercicio.php';

    const btn = document.querySelector("#modalExercicio .btn-gold:last-child");
    const originalText = btn.innerText;
    btn.innerText = "SALVANDO...";
    btn.disabled = true;

    // 6. Envia JSON puro
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            closeExercicioModal();
            if(typeof carregarConteudo === 'function') {
                carregarConteudo('treino_painel&id=' + payload.treino_id);
            } else {
                location.reload();
            }
        } else {
            alert("Erro: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erro de conexão.");
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
}

function closeExercicioModal() {
    document.getElementById("modalExercicio").style.display = "none";
}

// --- MODO EDIÇÃO (ADAPTADO PARA A NOVA ESTRUTURA) ---
function editarExercicio(exData, treinoId, divId) {
    // 1. Configura Estado Global
    currentMode = 'edit_single'; 
    activeTabIndex = 0;

    // 2. Preenche IDs Ocultos
    document.getElementById("modal_divisao_id").value = divId;
    document.getElementById("modal_treino_id").value = treinoId;
    document.getElementById("modal_exercicio_id").value = exData.id;
    
    // 3. Popula a Memória
    exerciseState = [{
        nome: exData.nome_exercicio,
        mecanica: exData.tipo_mecanica,
        video: exData.video_url || "",
        obs: exData.observacao_exercicio || "",
        series: []
    }];

    // 4. Popula Séries
    if (exData.series && exData.series.length > 0) {
        exData.series.forEach(s => {
            exerciseState[0].series.push({
                qtd: s.quantidade,
                tipo: s.categoria, 
                reps: s.reps_fixas || "",
                desc: s.descanso_fixo || "",
                rpe: s.rpe_previsto || "",
                tecnica: s.tecnica || "normal", 
                tecnica_valor: s.tecnica_valor || ""
            });
        });
    }
    
    // 5. Atualiza Interface
    updateTabButtons(); 
    loadDataToInputs(0); 

    // 6. Ajusta Títulos e Botão (AGORA COM SEGURANÇA PELO ID)
    document.querySelector("#modalExercicio .section-title").innerText = "Editar Exercício";
    
    const btnSave = document.getElementById("btn-save-modal");
    if (btnSave) {
        btnSave.innerText = "ATUALIZAR";
        // Não precisamos mudar o onclick, pois ambos usam salvarBlocoExercicios
        // O PHP saberá se é update pelo 'modal_exercicio_id' preenchido
    }

    document.getElementById("modalExercicio").style.display = "flex";
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
