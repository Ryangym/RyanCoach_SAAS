/* ==========================================================================
   MÓDULO DE GERAÇÃO DE PDF (RYAN COACH SAAS)
   Dependências: html2pdf.js, FontAwesome
   Funcionalidades: 
   1. Configuração de Cores (Modal)
   2. Renderização de Templates (Treino, em breve Avaliação/Dieta)
   3. Geração do Arquivo (Download)
   4. Preview com Zoom (Overlay)
   ========================================================================== */

/* ==========================================================================
   1. CONFIGURAÇÃO E MODAIS
   ========================================================================== */

// Função para abrir o modal garantindo que ele fique por cima de tudo
function abrirModalPDF() {
    const modal = document.getElementById('modalPDFConfig');
    
    if (modal) {
        if (modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
        
        modal.style.display = 'flex';
    } else {
        console.error("Erro: Modal #modalPDFConfig não encontrado!");
    }
}

// Seleção visual de cores no modal
function selectPdfColor(el, color) {
    document.querySelectorAll('.color-pick').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    
    const inputColor = document.getElementById('pdf_selected_color');
    if(inputColor) inputColor.value = color;
}

/* ==========================================================================
   2. RENDERIZAÇÃO DE TEMPLATES (HTML BUILDER)
   ========================================================================== */

/**
 * Constrói o HTML da Ficha de Treino dentro do template oculto
 * (LÓGICA ORIGINAL MANTIDA)
 */
/**
 * Constrói o HTML da Ficha de Treino (Com Suporte a Bi-sets)
 */
function renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores) {
    const { tema, fundo, borda } = configCores;
    const template = document.getElementById('template-impressao-full');
    
    if (!template) {
        console.error("Erro: Template de impressão não encontrado.");
        return null;
    }

    // Configura Cores Globais
    template.querySelector('.pdf-sheet').style.backgroundColor = fundo;
    
    // Preenche Cabeçalho
    template.querySelector('#render-aluno-nome').innerText = nomeAluno;
    if(template.querySelector('#render-plano-nome')) {
        template.querySelector('#render-plano-nome').innerText = nomePlano.toUpperCase();
    }
    
    const headerMain = template.querySelector('#pdf-header-main');
    if(headerMain) headerMain.style.borderBottom = `4px solid ${tema}`;

    // Limpa Container
    const container = document.getElementById('pdf-container-treinos');
    container.innerHTML = ''; 

    for (const [letra, conteudo] of Object.entries(dados)) {
        const exerciciosRaw = conteudo.exercicios;
        
        // --- 1. PRÉ-PROCESSAMENTO: AGRUPAR BI-SETS ---
        const listaProcessada = [];
        let grupoAtual = null;

        exerciciosRaw.forEach((ex) => {
            // Verifica se o exercício tem hash de agrupamento
            const hash = ex.agrupamento_hash; 

            if (hash && hash !== "") {
                // Se já existe um grupo aberto com esse mesmo hash, adiciona nele
                if (grupoAtual && grupoAtual.hash === hash) {
                    grupoAtual.itens.push(ex);
                } else {
                    // Se tinha um grupo aberto de outro hash, salva ele
                    if (grupoAtual) { listaProcessada.push(grupoAtual); }
                    
                    // Abre novo grupo
                    grupoAtual = { type: 'grupo', hash: hash, itens: [ex] };
                }
            } else {
                // Se tinha grupo aberto, fecha e salva
                if (grupoAtual) { 
                    listaProcessada.push(grupoAtual); 
                    grupoAtual = null; 
                }
                // Adiciona exercício solto
                listaProcessada.push({ type: 'single', item: ex });
            }
        });
        // Salva o último grupo se sobrou
        if (grupoAtual) { listaProcessada.push(grupoAtual); }
        // ---------------------------------------------

        // Dados do Dia
        let nomeDia = conteudo.dia_real; 
        let nomeTreinoBD = conteudo.nome ? conteudo.nome.trim() : "";
        let subtitulo = (nomeTreinoBD && nomeTreinoBD !== "") ? nomeTreinoBD : `TREINO ${letra}`;

        // Início do Bloco HTML do Dia
        let htmlBlock = `
            <div class="day-block" style="page-break-inside: avoid; background: transparent; margin-bottom: 20px;">
                
                <div class="day-header" style="border-top: 2px solid ${borda}; border-right: 2px solid ${borda}; border-left: 2px solid ${borda}; background: ${tema}; text-align: center;">
                    <span class="day-title" style="color: #fff; font-weight:800;">${nomeDia}</span>
                </div>

                <div class="day-subheader" style="border-bottom: 1px solid ${borda}; margin-bottom: 10px; padding: 5px 10px;">
                    <span class="day-subtitle">${subtitulo}</span>
                </div>
                
                <div class="exercises-list">
        `;

        // --- 2. RENDERIZAÇÃO DA LISTA PROCESSADA ---
        listaProcessada.forEach((bloco) => {
            
            // Define se é grupo ou single
            const isGroup = (bloco.type === 'grupo');
            const exerciciosParaRenderizar = isGroup ? bloco.itens : [bloco.item];
            
            // Estilos do Agrupamento (Bi-set)
            let wrapperStyle = "";
            let conectorVisual = "";
            
            if (isGroup) {
                // Cria a caixa visual do Bi-set
                const qtd = exerciciosParaRenderizar.length;
                const nomeTecnica = qtd === 2 ? "BI-SET" : (qtd === 3 ? "TRI-SET" : "GIANT-SET");
                
                // Borda lateral conectora e um fundo leve
                wrapperStyle = `
                    margin-bottom: 12px; 
                    padding: 5px 0 5px 8px; 
                    border-left: 3px solid ${tema}; 
                    position: relative;
                    background: rgba(0,0,0,0.02); /* Fundo super sutil */
                    border-radius: 0 6px 6px 0;
                `;

                // Badge escrito BI-SET
                conectorVisual = `
                    <div style="
                        position: absolute; 
                        left: -3px; 
                        top: -8px; 
                        background: ${tema}; 
                        color: #fff; 
                        font-size: 8px; 
                        font-weight: bold; 
                        padding: 1px 4px; 
                        border-radius: 0 4px 4px 0;
                        z-index: 10;
                    ">
                        ${nomeTecnica} <i class="fa-solid fa-link"></i>
                    </div>
                `;
            }

            // Abre Wrapper (se for grupo, aplica estilo, se não, div vazia)
            htmlBlock += `<div style="${wrapperStyle}"> ${conectorVisual}`;

            // Loop Interno (Renderiza os cards)
            exerciciosParaRenderizar.forEach((ex, index) => {
                let nomeEx = ex.nome_exercicio.toLowerCase();
                nomeEx = nomeEx.charAt(0).toUpperCase() + nomeEx.slice(1);

                // Se for item de grupo, remove a margem bottom do último para ficar compacto
                const marginRow = (isGroup && index < exerciciosParaRenderizar.length - 1) ? '6px' : '8px';
                
                htmlBlock += `
                    <div class="ex-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: ${marginRow};">
                        
                        <div class="ex-info-side" style="border-top: 1px solid ${borda}; border-right: 1px solid ${borda}; border-left: 1px solid ${borda}; background: ${tema};">
                            <span class="ex-text" style="font-family: 'Times New Roman', Times, serif; font-size: 13px;">
                                ${nomeEx}
                            </span>
                        </div>

                        <div class="ex-sets-side" style="width: 50%; display: flex; justify-content: flex-end; gap: 4px; flex-wrap: wrap;">
                `;

                if (ex.lista_series && ex.lista_series.length > 0) {
                    ex.lista_series.forEach(serie => {
                        const cat = serie.categoria ? serie.categoria.toLowerCase() : 'work';
                        
                        // Lógica de Técnicas
                        const tecnica = serie.tecnica ? serie.tecnica.toLowerCase() : 'normal';
                        const valor = serie.tecnica_valor;
                        let label = "";

                        if (tecnica === 'dropset') {
                            label = "Drop Set - " + (valor || '1') + " drops";
                        }
                        else if (tecnica === 'restpause') {
                            label = "Rest Pause";
                        }
                        else if (tecnica === 'clusterset') {
                            let parts = valor ? valor.split('|') : [];
                            label = (parts.length >= 2) ? `Cluster Set - ${parts[0]}x${parts[1]}` : "Cluster Set";
                        }
                        else {
                            if (cat === 'warmup') { label = "Warm up"; } 
                            else if (cat === 'backoff') { label = "Back off"; }
                            else if (cat === 'feeder') { label = "Feeder"; }
                            else if (cat === 'top') { label = "Top set"; } 
                            else { label = "Work set"; } 
                        }

                        const qtd = serie.quantidade ? serie.quantidade : 1;
                        htmlBlock += `<span class="set-box type-${serie.categoria}" style="border: none;">${qtd}x ${label}</span>`;
                    });
                } else {
                    htmlBlock += `<span style="font-size:10px; color:#ccc;">-</span>`;
                }

                htmlBlock += `</div></div>`; // Fecha ex-row
            });

            htmlBlock += `</div>`; // Fecha Wrapper do Grupo/Single
        });

        htmlBlock += `</div></div>`; // Fecha day-block
        container.innerHTML += htmlBlock;
    }
    
    return template;
}

/**
 * Constrói o HTML da Periodização (NOVO)
 */
/**
 * RENDERIZA PERIODIZAÇÃO (LAYOUT 2 COLUNAS x 6 LINHAS - BLOCOS LARGOS)
 */
/**
 * RENDERIZA PERIODIZAÇÃO (LAYOUT 2 COLUNAS - ORDEM VERTICAL)
 */
/**
 * RENDERIZA PERIODIZAÇÃO (LAYOUT FINAL - 2 DIVS)
 */
function renderizarTemplatePeriodizacao(micros, nomeAluno, nomePlano, configCores) {
    const { tema, fundo, borda } = configCores;
    const template = document.getElementById('template-periodizacao-full');
    
    if (!template) {
        console.error("Erro: Template de periodização não encontrado.");
        return null;
    }

    // Configurações Básicas
    template.querySelector('.pdf-sheet').style.backgroundColor = fundo;
    template.querySelector('#render-aluno-nome-perio').innerText = nomeAluno;
    
    const header = template.querySelector('#pdf-header-perio');
    if(header) header.style.borderBottom = `4px solid ${tema}`;

    // --- BLOCO DE DATAS ---
    let divDatas = document.getElementById('pdf-periodizacao-dates');
    if (!divDatas) {
        divDatas = document.createElement('div');
        divDatas.id = 'pdf-periodizacao-dates';
        header.parentNode.insertBefore(divDatas, header.nextSibling);
    }

    // Calcula Datas
    let textoPeriodo = "Periodização sem datas definidas";
    if (micros && micros.length > 0) {
        const primeiro = micros[0];
        const ultimo = micros[micros.length - 1];

        if (primeiro && ultimo) {
            const dIni = new Date(primeiro.data_inicio_semana);
            const dFim = new Date(ultimo.data_fim_semana);
            const fmt = (d) => `${d.getDate().toString().padStart(2,'0')}/${(d.getMonth()+1).toString().padStart(2,'0')}/${d.getFullYear()}`;
            textoPeriodo = `Periodização: Início: ${fmt(dIni)} ; Fim: ${fmt(dFim)}`;
        }
    }

    divDatas.className = 'perio-dates-block'; 
    divDatas.style.border = `2px solid ${borda}`;
    divDatas.style.background = `${tema}`; 
    divDatas.style.color = '#fff';
    divDatas.innerText = textoPeriodo;
    
    // --- GERAÇÃO DOS CARDS ---
    const container = document.getElementById('pdf-container-micros');
    container.innerHTML = '';
    container.className = 'perio-container'; 

    const colEsq = document.createElement('div');
    colEsq.className = 'perio-column';

    const colDir = document.createElement('div');
    colDir.className = 'perio-column';

    const totalSlots = 12;
    
    for (let i = 0; i < totalSlots; i++) {
        const micro = micros[i] || null;
        let cardHTML = '';

        // --- LÓGICA ESTRITA DE DESIGN DE BORDAS ---
        let borderStyle = 'border: none;';
        
        // Variável para guardar o HTML das bordinhas parciais (se houver)
        let bordinhasExtras = ''; 

        // COLUNA ESQUERDA (0 a 5)
        if (i === 0) {
            // Topo Esquerda -> Top + "Abas laterais"
            borderStyle = `border-top: 2px solid ${borda};`;
            
            // AQUI ESTÁ A MÁGICA:
            // Criamos dois divs absolutos de 2px de largura e 35% de altura
            bordinhasExtras = `
                <div style="position: absolute; top: 0; left: 0; width: 2px; height: 35%; background: ${borda};"></div>
                <div style="position: absolute; top: 0; right: 0; width: 2px; height: 35%; background: ${borda};"></div>
            `;
        } else if (i > 0 && i < 5) {
            borderStyle = `border-left: 2px solid ${borda};`;
        } else if (i === 5) {
            borderStyle = `border-bottom: 2px solid ${borda};`;
        }
        
        // COLUNA DIREITA (6 a 11)
        else if (i === 6) {
            // Topo Direita -> Top + "Abas laterais"
            borderStyle = `border-top: 2px solid ${borda};`;
            
            // Mesma mágica para o topo da coluna direita
            bordinhasExtras = `
                <div style="position: absolute; top: 0; left: 0; width: 2px; height: 35%; background: ${borda};"></div>
                <div style="position: absolute; top: 0; right: 0; width: 2px; height: 35%; background: ${borda};"></div>
            `;
        } else if (i > 6 && i < 11) {
            borderStyle = `border-right: 2px solid ${borda};`;
        } else if (i === 11) {
            borderStyle = `border-bottom: 2px solid ${borda};`;
        }

        // IMPORTANTE: Adicionei 'position: relative' aqui.
        // Sem isso, as bordinhas iriam para o topo da página, não do card.
        const styleWrapper = `background: ${tema}; ${borderStyle} position: relative; overflow: hidden;`;

        if (micro) {
            let dIni = new Date(micro.data_inicio_semana);
            let dFim = new Date(micro.data_fim_semana);
            const fmt = (d) => `${d.getDate().toString().padStart(2,'0')}/${(d.getMonth()+1).toString().padStart(2,'0')}`;
            const textoData = `( ${fmt(dIni)} - ${fmt(dFim)} )`;

            const conteudoCard = `
                <div class="col-info">
                    <span style="font-size: 14px;">Semana ${micro.semana_numero}: ${textoData}</span>
                    <span style="margin-right: 3px;">Microciclo: ${micro.nome_fase}</span>
                    <div class="metrics-box">
                        <span class="m-label">Compostos: ${micro.reps_compostos || '-'} | ${micro.descanso_compostos ? micro.descanso_compostos+'s' : ''}
                            Isolados: ${micro.reps_isoladores || '-'} | ${micro.descanso_isoladores ? micro.descanso_isoladores+'s' : ''}</span>
                    </div>
                </div>
                <div class="col-obs">
                    <div class="obs-text">
                        ${micro.foco_comentario ? micro.foco_comentario : ''}
                    </div>
                </div>
            `;

            // Adicionei ${bordinhasExtras} dentro do card
            cardHTML = `
                <div class="micro-pdf-card" style="${styleWrapper}">
                    ${bordinhasExtras}
                    ${conteudoCard}
                </div>
            `;
        } else {
            cardHTML = `
                <div class="micro-pdf-card empty" style="${styleWrapper}">
                    ${bordinhasExtras}
                    <i class="fa-solid fa-lock"></i>
                </div>
            `;
        }

        if (i < 6) colEsq.innerHTML += cardHTML;
        else colDir.innerHTML += cardHTML;
    }

    container.appendChild(colEsq);
    container.appendChild(colDir);

    return template;
}

// ==========================================
// RENDERIZAÇÃO: AVALIAÇÃO FÍSICA
// ==========================================

function renderizarTemplateAvaliacao(avaliacoes, nomeAluno, configCores) {
    const { tema, fundo, borda } = configCores;
    const template = document.getElementById('template-avaliacao-full');
    
    if (!template || !avaliacoes || avaliacoes.length === 0) {
        alert("Nenhuma avaliação encontrada.");
        return null;
    }

    const atual = avaliacoes[0]; 
    
    // --- 1. CONFIGURAÇÕES VISUAIS ---
    template.querySelector('.pdf-sheet').style.backgroundColor = fundo;
    template.querySelector('#render-aluno-nome-aval').innerText = nomeAluno;
    
    // Formata Data
    let dataFmt = atual.data_avaliacao;
    if(dataFmt.includes('-')) {
        const p = dataFmt.split('-');
        dataFmt = `${p[2]}/${p[1]}/${p[0]}`;
    }
    template.querySelector('#aval-data-ref').innerText = dataFmt;
    
    const header = template.querySelector('#pdf-header-aval');
    if(header) header.style.borderBottom = `4px solid ${tema}`;

    // Estilos Dinâmicos
    const stBorder = `border-color: ${borda};`;
    const stTitle = `border-bottom: 2px solid ${tema}; color: ${tema};`;

    const stText = `color: inherit;`;

    // --- 2. LÓGICA DE DADOS & IMC ---
    const peso = parseFloat(atual.peso_kg || 0);
    const altura = parseFloat(atual.altura_cm || 0); // Precisa vir do banco se tiver, ou calcular se tiver salvo
    const bf = parseFloat(atual.percentual_gordura || 0);
    const mm = parseFloat(atual.massa_magra_kg || 0);
    const mg = parseFloat(atual.massa_gorda_kg || 0);
    let imc = parseFloat(atual.imc || 0);

    // Se IMC não vier pronto mas tiver peso e altura
    if (imc === 0 && peso > 0 && altura > 0) {
        imc = peso / ((altura/100) * (altura/100));
    }

    // Função Interna de Classificação IMC
    function getClassificacaoIMC(v) {
        if (v < 18.5) return { label: 'Abaixo do Peso', color: '#ffb74d' }; // Laranja claro
        if (v < 24.9) return { label: 'Saudável', color: '#00c853' };      // Verde forte
        if (v < 29.9) return { label: 'Sobrepeso', color: '#ff9100' };     // Laranja
        if (v < 34.9) return { label: 'Obesidade I', color: '#ff5252' };   // Vermelho claro
        if (v < 39.9) return { label: 'Obesidade II', color: '#d32f2f' };  // Vermelho
        return { label: 'Obesidade III', color: '#b71c1c' };               // Vermelho escuro
    }

    const imcStatus = getClassificacaoIMC(imc);

    // --- 3. LÓGICA DO GRÁFICO (Histórico) ---
    // Pegamos até 6 últimas avaliações e invertemos para ficar cronológico (Esq -> Dir)
    const historicoChart = avaliacoes.slice(0, 6).reverse();
    
    // Acha o maior peso para usar como 100% da altura do gráfico
    let maxPeso = 0;
    historicoChart.forEach(h => { if(parseFloat(h.peso_kg) > maxPeso) maxPeso = parseFloat(h.peso_kg); });
    // Margem de segurança visual (+10%)
    const tetoGrafico = maxPeso * 1.1; 

    // --- 4. CONSTRUÇÃO DO HTML ---
    let html = `
    <div class="aval-landscape-layout">
        
        <div class="aval-col-left">
            
            <div class="aval-summary-grid landscape-mode" style="margin-bottom: 20px;">
                
                <div class="metric-card-premium" style="border-color: ${borda};">
                    <i class="fa-solid fa-weight-scale mcp-icon" style="color: ${tema};"></i>
                    <div>
                        <span class="mcp-title">Peso Corporal</span>
                        <div class="mcp-value" style="color: ${tema};">${peso} <small>kg</small></div>
                    </div>
                </div>

                <div class="metric-card-premium" style="border-color: ${borda};">
                    <i class="fa-solid fa-heart-pulse mcp-icon" style="color: ${imcStatus.color};"></i>
                    <div>
                        <span class="mcp-title">IMC</span>
                        <div class="mcp-value" style="color: ${tema};">${imc.toFixed(1)}</div>
                        <span class="aval-status-badge" style="background-color: ${imcStatus.color};">
                            ${imcStatus.label}
                        </span>
                    </div>
                </div>

                <div class="metric-card-premium" style="border-color: ${borda};">
                    <i class="fa-solid fa-droplet mcp-icon" style="color: #ff4444;"></i>
                    <div>
                        <span class="mcp-title">% Gordura</span>
                        <div class="mcp-value" style="color: #ff4444;">${bf} <small>%</small></div>
                        <div class="mcp-sub">${mg.toFixed(1)} kg de gordura</div>
                    </div>
                </div>

                <div class="metric-card-premium" style="border-color: ${borda};">
                    <i class="fa-solid fa-dumbbell mcp-icon" style="color: ${tema};"></i>
                    <div>
                        <span class="mcp-title">Massa Magra</span>
                        <div class="mcp-value" style="color: ${tema};">${mm} <small>kg</small></div>
                        <div class="mcp-sub">Músculos, ossos, órgãos</div>
                    </div>
                </div>
            </div>

            <div class="aval-comp-box" style="border-color: ${borda};">
                <div class="aval-section-title" style="${stTitle} margin-top:0; font-size:12px;">Composição Corporal</div>
                
                <div class="aval-comp-row">
                    <span class="aval-comp-label">Massa Magra</span>
                    <div class="aval-comp-track">
                        <div class="aval-comp-fill" style="width: ${(mm/(mm+mg)*100)}%; background-color: ${tema};"></div>
                    </div>
                    <span class="aval-comp-val">${mm} kg</span>
                </div>
                
                <div class="aval-comp-row">
                    <span class="aval-comp-label">Massa Gorda</span>
                    <div class="aval-comp-track">
                        <div class="aval-comp-fill" style="width: ${(mg/(mm+mg)*100)}%; background-color: #ff4444;"></div>
                    </div>
                    <span class="aval-comp-val">${mg} kg</span>
                </div>
            </div>

            <div class="aval-history-box" style="margin-top: 20px;">
                <div class="aval-section-title" style="${stTitle} margin-top:0;">Evolução de Peso (Últimas Avaliações)</div>
                
                <div class="chart-container" style="border-bottom-color: ${borda};">
                    ${historicoChart.map(h => {
                        const val = parseFloat(h.peso_kg);
                        const alturaBarra = (val / tetoGrafico) * 100; // % da altura total
                        let dt = h.data_avaliacao.split('-');
                        let dtShow = `${dt[2]}/${dt[1]}`;
                        
                        return `
                        <div class="chart-bar-group">
                            <span class="chart-value-top">${val}</span>
                            <div class="chart-bar" style="height: ${alturaBarra}%; background-color: ${tema}; opacity: 0.8;"></div>
                            <span class="chart-date-bottom">${dtShow}</span>
                        </div>
                        `;
                    }).join('')}
                </div>
            </div>

            ${atual.observacoes ? `
            <div class="aval-obs-box" style="border-color: ${borda}; margin-top: 20px;">
                <strong><i class="fa-solid fa-comment-dots"></i> Observações Técnicas:</strong><br>
                ${atual.observacoes}
            </div>` : ''}

        </div> 
        <div class="aval-col-right">
            
            <div class="aval-measures-container" style="border: 1px solid ${borda}; border-radius: 8px; padding: 20px;">
                
                <div class="aval-grid-measures">
                    <div>
                        <div class="aval-section-title" style="${stTitle}"><i class="fa-solid fa-ruler-vertical"></i> Perímetros (Tronco)</div>
                        <table class="aval-table measures-modern-table">
                            <tr><td>Pescoço</td><td style="${stText}">${atual.pescoco||'-'} <small>cm</small></td></tr>
                            <tr><td>Ombros</td><td style="${stText}">${atual.ombro||'-'} <small>cm</small></td></tr>
                            <tr><td>Tórax (Relaxado)</td><td style="${stText}">${atual.torax_relaxado||'-'} <small>cm</small></td></tr>
                            <tr><td>Tórax (Inspirado)</td><td style="${stText}">${atual.torax_inspirado||'-'} <small>cm</small></td></tr>
                            <tr><td>Cintura</td><td style="${stText}">${atual.cintura||'-'} <small>cm</small></td></tr>
                            <tr><td>Abdômen</td><td style="${stText}">${atual.abdomen||'-'} <small>cm</small></td></tr>
                            <tr><td>Quadril</td><td style="${stText}">${atual.quadril||'-'} <small>cm</small></td></tr>
                        </table>
                    </div>

                    <div>
                        <div class="aval-section-title" style="${stTitle}"><i class="fa-solid fa-ruler-horizontal"></i> Membros (Dir / Esq)</div>
                        <table class="aval-table measures-modern-table">
                            <tr><td>Braço Relaxado</td><td style="${stText}">${atual.braco_dir_relaxado||'-'} / ${atual.braco_esq_relaxado||'-'}</td></tr>
                            <tr><td>Braço Contraído</td><td style="${stText}">${atual.braco_dir_contraido||'-'} / ${atual.braco_esq_contraido||'-'}</td></tr>
                            <tr><td>Antebraço</td><td style="${stText}">${atual.antebraco_dir||'-'} / ${atual.antebraco_esq||'-'}</td></tr>
                            <tr><td colspan="2" style="height:10px; border:none;"></td></tr> <tr><td>Coxa Medial</td><td style="${stText}">${atual.coxa_dir||'-'} / ${atual.coxa_esq||'-'}</td></tr>
                            <tr><td>Panturrilha</td><td style="${stText}">${atual.panturrilha_dir||'-'} / ${atual.panturrilha_esq||'-'}</td></tr>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <div class="aval-section-title" style="${stTitle} border-bottom-width: 1px; font-size: 12px;">Histórico Detalhado</div>
                    <table class="aval-history-table" style="border-color: ${borda};">
                        <thead>
                            <tr class="aval-history-header" style="background-color: ${tema}; color: #fff;">
                                <th>DATA</th><th>PESO</th><th>BF %</th><th>CINTURA</th><th>ABD</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${avaliacoes.slice(0, 8).map(av => {
                                let d = av.data_avaliacao.split('-');
                                return `<tr>
                                    <td>${d[2]}/${d[1]}/${d[0]}</td>
                                    <td>${parseFloat(av.peso_kg||0)}</td>
                                    <td>${parseFloat(av.percentual_gordura||0)}</td>
                                    <td>${parseFloat(av.cintura||0)}</td>
                                    <td>${parseFloat(av.abdomen||0)}</td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>

            </div>
        </div> 
        </div>`;

    document.getElementById('pdf-container-avaliacao').innerHTML = html;
    return template;
}
/* ==========================================================================
   3. AÇÃO: GERAR ARQUIVO (DOWNLOAD)
   ========================================================================== */

function getPdfData() {
    return {
        nomeAluno: document.getElementById('pdf_aluno_nome').value,
        nomePlano: document.getElementById('plano-nome-atual').value,
        configCores: {
            tema: document.getElementById('pdf_theme_color').value,
            fundo: document.getElementById('pdf_bg_color').value,
            borda: document.getElementById('pdf_border_color').value
        }
    };
}

// Função Principal (Controlada pelo Select)
// Função Principal (Controlada pelo Select)
function gerarPDFSelecionado() {
    const tipo = document.getElementById('pdf_tipo_arquivo') ? document.getElementById('pdf_tipo_arquivo').value : 'treino';
    
    if (tipo === 'periodizacao') {
        gerarPeriodizacaoPDF();
    } else if (tipo === 'avaliacao') {
        gerarAvaliacaoPDF(); 
    } else {
        gerarFichaCompleta();
    }
}

function gerarFichaCompleta() {
    const { nomeAluno, nomePlano, configCores } = getPdfData();
    const jsonRaw = document.getElementById('json-dados-treinos').value;
    let dados;
    try { dados = JSON.parse(jsonRaw); } catch(e) { alert("Erro nos dados do treino."); return; }

    const template = renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores);
    if (!template) return;

    processarDownload(template, `Ficha_${nomeAluno}.pdf`, 'portrait');
}

function gerarPeriodizacaoPDF() {
    const { nomeAluno, nomePlano, configCores } = getPdfData();
    const jsonRaw = document.getElementById('json-dados-micros') ? document.getElementById('json-dados-micros').value : '[]';
    let micros;
    try { micros = JSON.parse(jsonRaw); } catch(e) { micros = []; }

    const template = renderizarTemplatePeriodizacao(micros, nomeAluno, nomePlano, configCores);
    if (!template) return;

    processarDownload(template, `Periodizacao_${nomeAluno}.pdf`, 'landscape');
}

function gerarAvaliacaoPDF() {
    const { nomeAluno, configCores } = getPdfData();
    const jsonRaw = document.getElementById('json-dados-avaliacoes') ? document.getElementById('json-dados-avaliacoes').value : '[]';
    let avaliacoes;
    try { avaliacoes = JSON.parse(jsonRaw); } catch(e) { avaliacoes = []; }

    const template = renderizarTemplateAvaliacao(avaliacoes, nomeAluno, configCores);
    if (!template) return;

    // IMPORTANTE: Agora usa 'landscape'
    processarDownload(template, `Avaliacao_${nomeAluno}.pdf`, 'landscape');
}

function processarDownload(template, filename, orientation) {
    const btn = document.querySelector('#modalPDFConfig .btn-gold');
    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando em HD...';
    btn.disabled = true;

    const opt = {
        margin: 0,
        filename: filename,
        image: { 
            type: 'png',  // MUDANÇA 1: PNG é "Lossless" (sem perda de qualidade)
            quality: 1    // Qualidade máxima
        },
        html2canvas: { 
            scale: 4,     // Escala 4x (equivalente a tela Retina/4K)
            useCORS: true, 
            scrollY: 0,
            letterRendering: true,
            // Dica: Tenta melhorar a renderização de fontes
            onclone: (clonedDoc) => {
                clonedDoc.body.style.fontSmooth = 'always';
                clonedDoc.body.style.webkitFontSmoothing = 'antialiased';
            }
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: orientation,
            compress: false // MUDANÇA 2: Desliga a compressão para não borrar nada
        }
    };

    // --- AJUSTES DE TAMANHO (MANTIDOS) ---
    if (orientation === 'landscape') {
        template.style.width = '297.5mm'; 
        template.style.height = '209.8mm'; 
        template.style.overflow = 'hidden'; 
        template.style.margin = '0';
        template.style.padding = '0';
    } else {
        template.style.width = '210mm';
        template.style.minHeight = '297mm';
        template.style.height = 'auto';
    }

    template.style.display = 'block';

    html2pdf().set(opt).from(template).save().then(() => {
        template.style.display = 'none';
        btn.innerHTML = oldText;
        btn.disabled = false;
        document.getElementById('modalPDFConfig').style.display = 'none';
    }).catch(err => {
        console.error(err);
        alert("Erro ao gerar PDF.");
        btn.innerHTML = oldText;
        btn.disabled = false;
        template.style.display = 'none';
    });
}

/* ==========================================================================
   4. AÇÃO: PREVIEW COM ZOOM (DEBUG/VISUALIZAÇÃO)
   ========================================================================== */

function debugPreviewPDF() {
    const tipo = document.getElementById('pdf_tipo_arquivo') ? document.getElementById('pdf_tipo_arquivo').value : 'treino';
    const { nomeAluno, nomePlano, configCores } = getPdfData();
    
    let template;
    let isLandscape = false;

    if (tipo === 'periodizacao') {
        const jsonRaw = document.getElementById('json-dados-micros').value;
        const micros = JSON.parse(jsonRaw);
        template = renderizarTemplatePeriodizacao(micros, nomeAluno, nomePlano, configCores);
        isLandscape = true;
    } else if (tipo === 'avaliacao') {
        const jsonRaw = document.getElementById('json-dados-avaliacoes').value;
        const avaliacoes = JSON.parse(jsonRaw);
        template = renderizarTemplateAvaliacao(avaliacoes, nomeAluno, configCores);
        isLandscape = true; // AGORA É TRUE
    } else {
        const jsonRaw = document.getElementById('json-dados-treinos').value;
        const dados = JSON.parse(jsonRaw);
        template = renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores);
        isLandscape = false; // Treino continua Portrait
    }

    // Limpa Overlays Antigos
    const oldOverlay = document.getElementById('pdf-viewer-overlay');
    if (oldOverlay) oldOverlay.remove();

    // Esconde Modal
    document.getElementById('modalPDFConfig').style.display = 'none';

    // Cria Overlay
    const overlay = document.createElement('div');
    overlay.id = 'pdf-viewer-overlay';
    overlay.className = 'pdf-viewer-overlay';
    
    overlay.innerHTML = `
        <div class="pdf-toolbar">
            <div class="pdf-toolbar-title">
                <i class="fa-solid fa-file-pdf"></i> Visualização (${tipo.toUpperCase()})
            </div>
            <div class="pdf-toolbar-actions">
                <button class="btn-preview-action" id="btn-toggle-zoom">
                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                </button>
                <button class="btn-preview-action btn-preview-close" id="btn-close-final">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div id="pdf-content-wrapper" style="transition: all 0.3s ease;"></div>
    `;
    
    document.body.appendChild(overlay);

    const wrapper = document.getElementById('pdf-content-wrapper');
    template.style.display = 'block';
    template.classList.add('preview-mode-active');
    wrapper.appendChild(template);

    // Zoom Inteligente
    let isZoomed = false;
    const pageWidth = isLandscape ? 1123 : 794; 
    const screenWidth = window.innerWidth;
    
    let fitScale = (screenWidth - 40) / pageWidth;
    if (fitScale > 1) fitScale = 1;

    // Ajustes de Largura/Altura baseados na orientação
    if (isLandscape) {
        template.style.width = '330mm';
        // No preview, não limitamos height fixo para não cortar se der scroll, 
        // mas o aspect-ratio visual será mantido pelo CSS do conteúdo.
    } else {
        template.style.width = '210mm'; 
    }
    template.style.maxWidth = 'none';

    const updateZoom = (scale) => {
        template.style.transform = `scale(${scale})`;
        template.style.transformOrigin = 'top center';
        
        // Altura do container
        const scaledHeight = (isLandscape ? 794 : 1123) * scale; 
        wrapper.style.height = `${scaledHeight + 50}px`;
        wrapper.style.display = 'flex';
        wrapper.style.justifyContent = 'center';
    };

    updateZoom(fitScale);

    // Eventos
    document.getElementById('btn-toggle-zoom').onclick = function() {
        isZoomed = !isZoomed;
        const icon = this.querySelector('i');
        if (isZoomed) {
            updateZoom(1); 
            icon.className = 'fa-solid fa-compress';
        } else {
            updateZoom(fitScale); 
            icon.className = 'fa-solid fa-magnifying-glass-plus';
        }
    };

    document.getElementById('btn-close-final').onclick = function() {
        document.body.appendChild(template);
        template.style.display = 'none';
        template.style.transform = 'none';
        template.style.width = ''; 
        template.classList.remove('preview-mode-active');
        overlay.remove();
        document.getElementById('modalPDFConfig').style.display = 'flex';
    };
}