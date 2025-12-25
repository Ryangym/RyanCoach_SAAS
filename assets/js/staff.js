/* ==========================================================================
   MAIN.JS - SCRIPT COMPARTILHADO ENTRE COACH E ADMIN
   ========================================================================== 
   
   ÍNDICE:
   1. MÓDULO: AVALIAÇÃO FÍSICA
   2. MÓDULO: SELETOR DE ALUNOS (Dropdown de Pesquisa)
   3. MÓDULO: UTILITÁRIOS ADMINISTRATIVOS
   4. MÓDULO: HISTÓRICO DE TREINOS (ADMIN & COACH)
   ========================================================================== */


/* ==========================================================================
   1. MÓDULO: AVALIAÇÃO FÍSICA
   Lógica para abrir e gerenciar o modal de nova avaliação
   ========================================================================== */

function abrirModalAvaliacao(idAluno) {
    if (!idAluno) {
        alert("Erro: ID do aluno não identificado.");
        return;
    }

    const modal = document.getElementById('modalNovaAvaliacao');
    const inputId = document.getElementById('av_aluno_id');

    if (modal && inputId) {
        inputId.value = idAluno; // Preenche o ID oculto para o PHP saber quem é o aluno
        modal.style.display = 'flex';
    } else {
        console.error("Erro: Modal de avaliação ou input ID não encontrados no DOM.");
    }
}

function fecharModalAvaliacao() {
    const modal = document.getElementById('modalNovaAvaliacao');
    if (modal) {
        modal.style.display = 'none';
        
        // Limpeza do formulário para evitar dados antigos
        const form = document.getElementById('formAvaliacao');
        if(form) form.reset();

        // Limpa preview de fotos se houver
        const previewArea = document.getElementById('preview-area');
        if(previewArea) previewArea.innerHTML = "";
    }
}

/* ==========================================================================
   2. MÓDULO: SELETOR DE ALUNOS (Dropdown de Pesquisa)
   Usado nas telas de Criar Treino e Vincular Aluno
   ========================================================================== */

// Filtra a lista enquanto digita
function filtrarAlunosTreino() {
    const input = document.getElementById("busca-aluno-treino");
    const dropdown = document.getElementById("dropdown-alunos-treino");
    
    if (!input || !dropdown) return;

    const filter = input.value.toUpperCase();
    const items = dropdown.getElementsByClassName("dropdown-item");

    // Se o campo estiver vazio, esconde o dropdown
    if (filter === "") {
        dropdown.style.display = "none";
        return;
    }

    dropdown.style.display = "block";
    let encontrou = false;

    for (let i = 0; i < items.length; i++) {
        const span = items[i].getElementsByTagName("span")[0];
        if (span) {
            const txtValue = span.textContent || span.innerText;
            
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                items[i].style.display = ""; // Mostra
                encontrou = true;
            } else {
                items[i].style.display = "none"; // Esconde
            }
        }
    }

    // Se não achou nada, esconde o menu para não ficar um quadrado vazio
    if (!encontrou) dropdown.style.display = "none";
}

// Ação ao clicar em um aluno da lista
function selecionarAlunoTreino(id, nome) {
    const inputVisual = document.getElementById("busca-aluno-treino");
    const inputOculto = document.getElementById("id-aluno-treino-selecionado");
    const dropdown = document.getElementById("dropdown-alunos-treino");

    if (inputVisual) inputVisual.value = nome; // Mostra o nome para o usuário conferir
    if (inputOculto) inputOculto.value = id;   // Salva o ID para enviar no POST
    if (dropdown) dropdown.style.display = "none"; // Fecha a lista
}

// Fecha o dropdown se clicar fora dele (UX)
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById("dropdown-alunos-treino");
    const input = document.getElementById("busca-aluno-treino");
    
    if (dropdown && input) {
        // Se o clique NÃO foi no input NEM no dropdown, fecha.
        if (!input.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = "none";
        }
    }
});

/* ==========================================================================
   3. MÓDULO: UTILITÁRIOS ADMINISTRATIVOS
   Preview de imagem para perfil e outros uploads
   ========================================================================== */

function previewImageAdmin(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const img = document.getElementById("admin-preview");
            if (img) {
                img.src = e.target.result;
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
/* ==========================================================================
   4. MÓDULO: HISTÓRICO DE TREINOS (ADMIN & COACH)
   Permite editar e excluir registros dos alunos
   ========================================================================== */

// --- EXCLUIR HISTÓRICO ---
function deletarHistoricoAdm(dataRef, alunoId) {
    if(confirm("Tem certeza que deseja apagar este registro do histórico do aluno?\nIsso não pode ser desfeito.")) {
        
        fetch('actions/treino_historico_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            // Importante: Enviamos o ID do aluno alvo
            body: JSON.stringify({ data: dataRef, aluno_id: alunoId }) 
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Recarrega o conteúdo passando o ID do aluno
                carregarConteudo('aluno_historico&id=' + alunoId);
            } else {
                alert('Erro: ' + (data.message || 'Falha ao apagar'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão ao tentar apagar.');
        });
    }
}

// --- EDIÇÃO DE HISTÓRICO ---
let isEditingHistoryAdm = false;

function alternarEdicaoHistoricoAdm(alunoId) {
    isEditingHistoryAdm = !isEditingHistoryAdm;
    const btn = document.getElementById('btn-editar-hist-adm');
    
    // Busca o container específico do admin
    const container = document.querySelector('.history-details-list-adm'); 
    
    if (!container) return;

    const viewEls = container.querySelectorAll('.view-val');
    const inputEls = container.querySelectorAll('.edit-input');

    if (isEditingHistoryAdm) {
        // ATIVAR MODO EDIÇÃO
        viewEls.forEach(el => el.style.display = 'none');
        inputEls.forEach(el => el.style.display = 'block');
        
        // Estilo do botão: Verde (Confirmar)
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.style.background = 'rgba(0, 230, 118, 0.2)';
        btn.style.color = '#00e676';
        btn.style.borderColor = '#00e676';
        
    } else {
        // SALVAR
        salvarEdicaoHistoricoAdm(inputEls, btn, viewEls, alunoId);
    }
}

function salvarEdicaoHistoricoAdm(inputs, btn, viewEls, alunoId) {
    // Feedback visual (Loading)
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
    
    const updates = {};

    // Coleta dados dos inputs
    inputs.forEach(input => {
        const cell = input.closest('.editable-cell-adm');
        const id = cell.dataset.id;
        const type = cell.dataset.type; // 'carga' ou 'reps'
        
        if (!updates[id]) updates[id] = {};
        updates[id][type] = input.value;
    });

    fetch('actions/treino_historico_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        // Envia updates e o ID do aluno
        body: JSON.stringify({ updates: updates, aluno_id: alunoId }) 
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualiza os valores visuais (texto) com o que foi digitado
            inputs.forEach(input => {
                const cell = input.closest('.editable-cell-adm');
                const span = cell.querySelector('.view-val');
                span.innerText = input.value;
            });
            
            // Volta ao estado normal (Visualização)
            inputs.forEach(el => el.style.display = 'none');
            viewEls.forEach(el => el.style.display = 'block');
            
            // Reseta botão
            btn.innerHTML = '<i class="fa-solid fa-pen"></i>';
            btn.style.background = 'rgba(255, 186, 66, 0.1)';
            btn.style.color = 'var(--gold)';
            btn.style.borderColor = 'var(--gold)';
            
        } else {
            alert("Erro ao salvar: " + data.message);
            isEditingHistoryAdm = true; // Mantém modo edição ativo se der erro
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erro de conexão.");
        // Reseta botão em caso de erro fatal
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    });
}