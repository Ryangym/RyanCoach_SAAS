function salvarMicro(event) {
    event.preventDefault(); // Impede a página de recarregar

    const form = document.getElementById('formMicro');
    const formData = new FormData(form);
    
    // Pega o ID do treino para recarregar a tela depois
    const treinoId = document.getElementById('micro_treino_id').value;
    const btnSubmit = form.querySelector('button[type="submit"]');
    
    // Efeito visual de carregamento no botão
    const textoOriginal = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';
    btnSubmit.disabled = true;

    fetch('actions/treino_edit_micro.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Fecha o modal
            closeMicroModal();
            
            // Recarrega o painel do treino para atualizar as informações na tela
            // Assumindo que sua função carregarConteudo aceita a string de URL
            carregarConteudo('treino_painel&id=' + treinoId);
            
            // Opcional: Mostrar um alerta bonito (se tiver SweetAlert ou Toastify)
            // alert('Salvo com sucesso!'); 
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao processar a requisição.');
    })
    .finally(() => {
        // Restaura o botão
        btnSubmit.innerHTML = textoOriginal;
        btnSubmit.disabled = false;
    });
}


function deletarExercicio(idExercicio, idTreino) {
    if (confirm('Tem certeza que deseja excluir este exercício?')) {
        
        fetch('actions/treino_delete_exercicio.php?id=' + idExercicio)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Sucesso: Recarrega o painel do treino
                carregarConteudo('treino_painel&id=' + idTreino);
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao tentar excluir.');
        });
    }
}

function deletarTreino(idTreino) {
    const mensagem = 'Tem certeza que deseja EXCLUIR este planejamento?\n\nIsso apagará permanentemente:\n- Todas as divisões\n- Exercícios e Séries\n- Histórico de periodização vinculados a ele.';

    if (confirm(mensagem)) {
        
        document.body.style.cursor = 'wait';

        fetch('actions/treino_delete.php?id=' + idTreino)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Recarrega a lista de treinos
                carregarConteudo('treinos_editor');
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao tentar excluir o treino.');
        })
        .finally(() => {
            document.body.style.cursor = 'default';
        });
    }
}

function criarTreino(event) {
    event.preventDefault();

    const form = document.getElementById('formNovoTreino');
    const formData = new FormData(form);
    const btnSubmit = form.querySelector('button[type="submit"]');
    
    // Feedback visual
    const textoOriginal = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Criando...';
    btnSubmit.disabled = true;

    fetch('actions/treino_create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        const cleanText = text.trim();
        try {
            const data = JSON.parse(cleanText);

            if (data.status === 'success') {
                // SUCESSO: Redireciona para o painel de edição do treino criado
                // Aqui a mágica acontece: chamamos o editor passando o ID novo
                carregarConteudo('treino_painel&id=' + data.treino_id);
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (e) {
            console.error("Erro JSON:", e);
            alert("Erro inesperado ao criar treino.");
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão.');
    })
    .finally(() => {
        btnSubmit.innerHTML = textoOriginal;
        btnSubmit.disabled = false;
    });
}

function copiarLinkIndicacao(link) {
    navigator.clipboard.writeText(link).then(() => {
        alert("Link copiado! Envie para seu aluno.");
    })
    .catch(err => {
        console.error("Erro ao copiar: ", err);
        alert("Não foi possível copiar automaticamente. Seu código é: " + link.split("ref=")[1]);
    });
}

// ------------------FINANCEIRO ------------------
document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'formLancamentoFinanceiro') {
        e.preventDefault();
        salvarLancamentoAjax(e);
    }
});
async function salvarLancamentoAjax(event) {
    const form = event.target.closest('form');
    if (!form) return;

    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');

    const textoOriginal = btn.innerText;
    btn.innerText = "Salvando...";
    btn.disabled = true;

    try {
        const response = await fetch('actions/financeiro_add.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Resposta inválida do servidor');
        }

        const data = await response.json();

        if (data.success) {
            fecharModalFinanceiro();
            setTimeout(() => carregarConteudo('financeiro'), 80);
        } else {
            alert("Erro: " + (data.error || "Falha ao salvar"));
        }

    } catch (error) {
        console.error(error);
        alert("Erro de comunicação com o servidor.");
    } finally {
        btn.innerText = textoOriginal;
        btn.disabled = false;
    }
}
function fecharModalFinanceiro() {
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.style.display = 'none';
    });

    document.body.style.overflow = '';
}
async function atualizarStatusFinanceiro(id, acao) {
    let mensagem = "Confirmar ação?";
    if (acao === 'excluir') mensagem = "Tem certeza que deseja excluir permanentemente?";
    if (acao === 'estornar') mensagem = "Deseja estornar e voltar para pendente?";

    if (!confirm(mensagem)) return;

    try {
        const response = await fetch(
            `actions/financeiro_status.php?id=${id}&acao=${acao}`,
            { headers: { 'Accept': 'application/json' } }
        );

        if (!response.ok) {
            throw new Error('Falha no servidor');
        }

        const data = await response.json();

        if (data.success) {
            carregarConteudo('financeiro');
        } else {
            alert("Erro: " + (data.error || "Falha na operação"));
        }

    } catch (error) {
        console.error(error);
        alert("Erro ao processar a ação.");
    }
}
// ------------------ FIM FINANCEIRO ------------------
