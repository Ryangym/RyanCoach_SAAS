<?php
// actions/treino_add_exercicio.php

// 1. Configurações e Limpeza
while (ob_get_level()) ob_end_clean(); // Remove qualquer lixo anterior
header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/db_connect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método inválido.");
    }

    // 2. Lê o JSON
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        throw new Exception("Dados inválidos ou corrompidos.");
    }

    $pdo->beginTransaction();

    // 3. Dados Gerais
    $divisao_id = filter_var($data['divisao_id'], FILTER_SANITIZE_NUMBER_INT);
    $exercicios = $data['exercises'] ?? [];

    if (!$divisao_id || empty($exercicios)) {
        throw new Exception("Faltam dados obrigatórios (Divisão ou Exercícios).");
    }

    // 4. Prepara Ordem e Hash de Agrupamento
    $stmtOrder = $pdo->prepare("SELECT MAX(ordem) FROM exercicios WHERE divisao_id = ?");
    $stmtOrder->execute([$divisao_id]);
    $ordem_atual = ($stmtOrder->fetchColumn() ?: 0) + 1;

    // Gera Hash apenas se for Bi-set (mais de 1 exercício)
    $hash_grupo = (count($exercicios) > 1) ? uniqid('grp_') : null;

    // 5. Loop de Salvamento
    $sqlEx = "INSERT INTO exercicios (divisao_id, nome_exercicio, tipo_mecanica, video_url, observacao_exercicio, ordem, agrupamento_hash) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtEx = $pdo->prepare($sqlEx);

    $sqlSerie = "INSERT INTO series (exercicio_id, categoria, quantidade, reps_fixas, descanso_fixo, rpe_previsto, tecnica, tecnica_valor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtSerie = $pdo->prepare($sqlSerie);

    foreach ($exercicios as $ex) {
        // Validação básica
        if (empty(trim($ex['nome']))) continue;

        // Salva Exercício
        $stmtEx->execute([
            $divisao_id,
            trim($ex['nome']),
            $ex['mecanica'] ?? 'livre',
            $ex['video'] ?? '',
            $ex['obs'] ?? '',
            $ordem_atual++, // Incrementa ordem
            $hash_grupo
        ]);
        
        $exercicio_id = $pdo->lastInsertId();

        // Salva Séries
        if (!empty($ex['series']) && is_array($ex['series'])) {
            foreach ($ex['series'] as $s) {
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
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Treino salvo com sucesso!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>