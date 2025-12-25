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

// Abre o modal de configuração antes de gerar
function abrirModalPDFCompleto() {
    const modal = document.getElementById('modalPDFConfig');
    if (modal) modal.style.display = 'flex';
}

// Seleção visual de cores no modal
function selectPdfColor(el, color) {
    document.querySelectorAll('.color-pick').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    
    const inputColor = document.getElementById('pdf_selected_color');
    if(inputColor) inputColor.value = color;
    
    // Atualiza inputs hidden de configuração se existirem
    // (Opcional: lógica para preencher theme/bg/border baseado no preset)
}

/* ==========================================================================
   2. RENDERIZAÇÃO DE TEMPLATES (HTML BUILDER)
   ========================================================================== */

/**
 * Constrói o HTML da Ficha de Treino dentro do template oculto
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

    // Limpa e Preenche Container de Treinos
    const container = document.getElementById('pdf-container-treinos');
    container.innerHTML = ''; 

    for (const [letra, conteudo] of Object.entries(dados)) {
        const exercicios = conteudo.exercicios;
        
        // Dados do Dia
        let nomeDia = conteudo.dia_real; 
        let nomeTreinoBD = conteudo.nome ? conteudo.nome.trim() : "";
        let subtitulo = (nomeTreinoBD && nomeTreinoBD !== "") ? nomeTreinoBD : `TREINO ${letra}`;

        // Constrói Bloco do Dia
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

        // Loop Exercícios
        exercicios.forEach((ex) => {
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

            htmlBlock += `</div></div>`; // Fecha ex-row
        });

        htmlBlock += `</div></div>`; // Fecha day-block
        container.innerHTML += htmlBlock;
    }
    
    return template;
}

/* ==========================================================================
   3. AÇÃO: GERAR ARQUIVO (DOWNLOAD)
   ========================================================================== */

function gerarFichaCompleta() {
    // 1. Coleta Dados
    const nomeAluno = document.getElementById('pdf_aluno_nome').value;
    const nomePlano = document.getElementById('plano-nome-atual').value;
    
    const configCores = {
        tema: document.getElementById('pdf_theme_color').value,
        fundo: document.getElementById('pdf_bg_color').value,
        borda: document.getElementById('pdf_border_color').value
    };

    const jsonRaw = document.getElementById('json-dados-treinos').value;
    let dados;
    try { dados = JSON.parse(jsonRaw); } catch(e) { alert("Erro nos dados do treino."); return; }

    // 2. Renderiza
    const template = renderizarTemplateTreino(dados, nomeAluno, nomePlano, configCores);
    if (!template) return;

    // 3. Feedback Visual (Loading)
    const btn = document.querySelector('#modalPDFConfig .btn-gold');
    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando...';
    btn.disabled = true;

    // 4. Configuração html2pdf
    const opt = {
        margin: 0,
        filename: `Ficha_${nomeAluno}.pdf`,
        image: { type: 'jpeg', quality: 1 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    template.style.display = 'block';

    // 5. Gera e Salva
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
    });
}

/* ==========================================================================
   4. AÇÃO: PREVIEW COM ZOOM (DEBUG/VISUALIZAÇÃO)
   ========================================================================== */

function debugPreviewPDF() {
    // 1. Coleta Dados
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

    // 3. Limpeza de Overlays Antigos
    const oldOverlay = document.getElementById('pdf-viewer-overlay');
    if (oldOverlay) oldOverlay.remove();

    // 4. Esconde o Modal de Config
    document.getElementById('modalPDFConfig').style.display = 'none';

    // 5. Cria o Overlay com Toolbar
    const overlay = document.createElement('div');
    overlay.id = 'pdf-viewer-overlay';
    overlay.className = 'pdf-viewer-overlay';
    
    overlay.innerHTML = `
        <div class="pdf-toolbar">
            <div class="pdf-toolbar-title">
                <i class="fa-solid fa-file-pdf"></i> Visualização
            </div>
            <div class="pdf-toolbar-actions">
                <button class="btn-preview-action" id="btn-toggle-zoom" onclick="togglePdfZoom()">
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

    // 6. Move o Template para o Viewer
    const wrapper = document.getElementById('pdf-content-wrapper');
    template.style.display = 'block';
    template.classList.add('preview-mode-active');
    wrapper.appendChild(template);

    // 7. Lógica de Zoom Inteligente
    let isZoomed = false;
    const a4Width = 794; // Largura padrão A4 em px (aprox)
    const screenWidth = window.innerWidth;
    
    // Calcula escala "Fit Screen" (com margem de 40px)
    let fitScale = (screenWidth - 40) / a4Width;
    if (fitScale > 1) fitScale = 1;

    // Aplica Zoom Inicial
    template.style.transform = `scale(${fitScale})`;
    
    // Ajusta altura do wrapper para o scroll funcionar
    const updateWrapperHeight = (scale) => {
        const originalHeight = template.offsetHeight;
        const scaledHeight = originalHeight * scale;
        wrapper.style.height = `${scaledHeight}px`;
        wrapper.style.marginBottom = '50px'; 
    };
    
    setTimeout(() => updateWrapperHeight(fitScale), 100);

    // --- Controles Internos ---
    
    // Função Zoom
    window.togglePdfZoom = function() {
        isZoomed = !isZoomed;
        const btnIcon = document.querySelector('#btn-toggle-zoom i');
        
        if (isZoomed) {
            // Modo 100%
            template.style.transform = `scale(1)`;
            btnIcon.className = 'fa-solid fa-compress';
            updateWrapperHeight(1);
        } else {
            // Modo Fit
            template.style.transform = `scale(${fitScale})`;
            btnIcon.className = 'fa-solid fa-magnifying-glass-plus';
            updateWrapperHeight(fitScale);
            overlay.scrollTop = 0;
        }
    };

    // Função Fechar
    document.getElementById('btn-close-final').onclick = function() {
        // Devolve o template para o body (escondido)
        document.body.appendChild(template);
        template.style.display = 'none';
        template.style.transform = 'none';
        template.classList.remove('preview-mode-active');
        
        // Destrói Viewer
        overlay.remove();
        
        // Reabre Config
        document.getElementById('modalPDFConfig').style.display = 'flex';
    };
}