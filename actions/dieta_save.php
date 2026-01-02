<?php
session_start();
require_once '../config/db_connect.php';

// Captura dados básicos
$acao = $_REQUEST['acao'] ?? '';
$aluno_id = $_REQUEST['aluno_id'] ?? 0;

// Captura a ORIGEM para saber se volta pro Admin, Coach ou Usuário
$origem = $_REQUEST['origem'] ?? 'admin'; 

try {
    // 1. CRIAR DIETA (CABEÇALHO)
    if ($acao === 'criar_dieta') {
        $titulo = $_POST['titulo'];
        $objetivo = $_POST['objetivo'];

        // Desativa dietas anteriores
        $pdo->prepare("UPDATE dietas SET ativo = 0 WHERE aluno_id = ?")->execute([$aluno_id]);

        $stmt = $pdo->prepare("INSERT INTO dietas (aluno_id, titulo, objetivo, ativo) VALUES (?, ?, ?, 1)");
        $stmt->execute([$aluno_id, $titulo, $objetivo]);
    }

    // 2. EXCLUIR DIETA INTEIRA
    elseif ($acao === 'excluir_dieta') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM dietas WHERE id = ?")->execute([$id]);
    }

    // 3. ADICIONAR REFEIÇÃO
    elseif ($acao === 'add_refeicao') {
        $dieta_id = $_POST['dieta_id'];
        $nome = $_POST['nome'];
        $horario = $_POST['horario'];
        $ordem = $_POST['ordem'];

        $stmt = $pdo->prepare("INSERT INTO refeicoes (dieta_id, nome, horario, ordem) VALUES (?, ?, ?, ?)");
        $stmt->execute([$dieta_id, $nome, $horario, $ordem]);
    }

    // 4. EXCLUIR REFEIÇÃO
    elseif ($acao === 'excluir_refeicao') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM refeicoes WHERE id = ?")->execute([$id]);
    }

    // 5. ADICIONAR ITEM (ALIMENTO)
    elseif ($acao === 'add_item') {
        $refeicao_id = $_POST['refeicao_id'];
        $opcao = $_POST['opcao_numero'];
        $desc = $_POST['descricao'];
        $obs = $_POST['observacao'];

        $stmt = $pdo->prepare("INSERT INTO itens_dieta (refeicao_id, opcao_numero, descricao, observacao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$refeicao_id, $opcao, $desc, $obs]);
    }

    // 6. EXCLUIR ITEM
    elseif ($acao === 'excluir_item') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM itens_dieta WHERE id = ?")->execute([$id]);
    }

    // 7. IMPORTAR DIETA
    elseif ($acao === 'importar_dieta') {
        $origem_id = $_POST['aluno_origem_id'];
        $destino_id = $_POST['aluno_destino_id']; // Aluno atual

        $stmt = $pdo->prepare("SELECT * FROM dietas WHERE aluno_id = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$origem_id]);
        $dietaOrigem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dietaOrigem) {
            $pdo->prepare("DELETE FROM dietas WHERE aluno_id = ?")->execute([$destino_id]);

            $stmt = $pdo->prepare("INSERT INTO dietas (aluno_id, titulo, objetivo, ativo) VALUES (?, ?, ?, 1)");
            $stmt->execute([$destino_id, $dietaOrigem['titulo'], $dietaOrigem['objetivo']]);
            $novaDietaId = $pdo->lastInsertId();

            $stmtRef = $pdo->prepare("SELECT * FROM refeicoes WHERE dieta_id = ?");
            $stmtRef->execute([$dietaOrigem['id']]);
            $refeicoes = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

            foreach($refeicoes as $ref) {
                $stmtInsRef = $pdo->prepare("INSERT INTO refeicoes (dieta_id, nome, horario, ordem) VALUES (?, ?, ?, ?)");
                $stmtInsRef->execute([$novaDietaId, $ref['nome'], $ref['horario'], $ref['ordem']]);
                $novaRefId = $pdo->lastInsertId();

                $stmtItens = $pdo->prepare("SELECT * FROM itens_dieta WHERE refeicao_id = ?");
                $stmtItens->execute([$ref['id']]);
                $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

                foreach($itens as $item) {
                    $stmtInsItem = $pdo->prepare("INSERT INTO itens_dieta (refeicao_id, opcao_numero, descricao, observacao) VALUES (?, ?, ?, ?)");
                    $stmtInsItem->execute([$novaRefId, $item['opcao_numero'], $item['descricao'], $item['observacao']]);
                }
            }
        }
        $aluno_id = $destino_id;
    }

    // --- REDIRECIONAMENTO FINAL ATUALIZADO ---
    
    if ($origem === 'usuario') {
        // Redireciona o Atleta (Usuario)
        header("Location: ../usuario.php?page=dieta_editor&msg=sucesso");
    } 
    elseif ($origem === 'coach') {
        // Redireciona o Coach (NOVO)
        header("Location: ../coach.php?pagina=dieta_editor&id=$aluno_id&msg=sucesso");
    } 
    else {
        // Redireciona o Admin (Padrão)
        header("Location: ../admin.php?pagina=dieta_editor&id=$aluno_id&msg=sucesso");
    }
    exit;

} catch (PDOException $e) {
    echo "Erro ao salvar: " . $e->getMessage();
    exit;
}
?>