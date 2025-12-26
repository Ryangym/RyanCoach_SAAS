<?php
// actions/treino_edit_exercicio.php

// 1. Limpeza
while (ob_get_level()) ob_end_clean();
header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/db_connect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método inválido.");
    }

    // 2. Lê o JSON (IMPORTANTE: agora lemos do input raw, não do $_POST)
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        throw new Exception("Dados inválidos recebidos.");
    }

    $pdo->beginTransaction();

    // 3. Recebe os IDs do JSON
    $treino_id    = filter_var($data['treino_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
    $divisao_id   = filter_var($data['divisao_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
    $exercicio_id = filter_var($data['exercicio_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

    if (!$exercicio_id) {
        throw new Exception("ID do exercício não fornecido para edição.");
    }

    // Pega o exercício do array (Single mode = índice 0)
    $exercicios = $data['exercises'] ?? [];
    if (empty($exercicios) || !isset($exercicios[0])) {
        throw new Exception("Nenhum dado de alteração recebido.");
    }
    $exData = $exercicios[0];

    // Validação
    $nome = trim($exData['nome'] ?? '');
    if (empty($nome)) throw new Exception("O nome do exercício é obrigatório.");

    // 4. UPDATE no Exercício
    $sqlUpdate = "UPDATE exercicios 
                  SET nome_exercicio = ?, 
                      tipo_mecanica = ?, 
                      video_url = ?, 
                      observacao_exercicio = ? 
                  WHERE id = ?";
    
    $stmtUp = $pdo->prepare($sqlUpdate);
    $stmtUp->execute([
        $nome,
        $exData['mecanica'] ?? 'livre',
        $exData['video'] ?? '',
        $exData['obs'] ?? '',
        $exercicio_id
    ]);

    // 5. UPDATE nas Séries (Apaga antigas e recria novas)
    $stmtDel = $pdo->prepare("DELETE FROM series WHERE exercicio_id = ?");
    $stmtDel->execute([$exercicio_id]);

    $series = $exData['series'] ?? [];
    
    if (!empty($series) && is_array($series)) {
        $sqlSerie = "INSERT INTO series (exercicio_id, categoria, quantidade, reps_fixas, descanso_fixo, rpe_previsto, tecnica, tecnica_valor) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtSerie = $pdo->prepare($sqlSerie);

        foreach ($series as $s) {
            $stmtSerie->execute([
                $exercicio_id,
                $s['tipo'] ?? 'work',
                $s['qtd'] ?? 1,
                $s['reps'] ?? '',
                $s['desc'] ?? '',
                !empty($s['rpe']) ? $s['rpe'] : null,
                $s['tecnica'] ?? 'normal',
                $s['tecnica_valor'] ?? ''
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Exercício atualizado!']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>