<?php
// Inicia o buffer de saída (segura qualquer echo acidental)
ob_start();

session_start();
require_once '../config/db_connect.php';

// Desativa exibição de erros na tela (eles quebram o JSON)
error_reporting(0);
ini_set('display_errors', 0);

// Define que a resposta será JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Recebe Dados
        $divisao_id = filter_input(INPUT_POST, 'divisao_id', FILTER_SANITIZE_NUMBER_INT);
        $nome       = filter_input(INPUT_POST, 'nome_exercicio', FILTER_SANITIZE_STRING);
        $mecanica   = filter_input(INPUT_POST, 'tipo_mecanica', FILTER_SANITIZE_STRING);
        $video      = filter_input(INPUT_POST, 'video_url', FILTER_SANITIZE_URL);
        $obs        = filter_input(INPUT_POST, 'observacao', FILTER_SANITIZE_STRING);
        
        // 2. Validação
        if (!$divisao_id || !$nome) {
            throw new Exception("Nome do exercício é obrigatório.");
        }

        // 3. Ordem
        $stmtOrder = $pdo->prepare("SELECT MAX(ordem) FROM exercicios WHERE divisao_id = ?");
        $stmtOrder->execute([$divisao_id]);
        $nova_ordem = $stmtOrder->fetchColumn() + 1;

        // 4. Salva Exercício
        $sqlEx = "INSERT INTO exercicios (divisao_id, nome_exercicio, tipo_mecanica, video_url, observacao_exercicio, ordem) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmtEx = $pdo->prepare($sqlEx);
        $stmtEx->execute([$divisao_id, $nome, $mecanica, $video, $obs, $nova_ordem]);
        
        $exercicio_id = $pdo->lastInsertId();

        // 5. Salva Séries
        $series_json = $_POST['series_data'] ?? '[]';
        // Remove barras invertidas que as vezes vem no POST
        $series_json = stripslashes($series_json);
        $series_lista = json_decode($series_json, true);

        // Se o JSON for inválido, json_decode retorna null, então verificamos se é array
        if (is_array($series_lista)) {
            $sqlSerie = "INSERT INTO series (exercicio_id, categoria, quantidade, reps_fixas, descanso_fixo, rpe_previsto) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $stmtSerie = $pdo->prepare($sqlSerie);

            foreach ($series_lista as $s) {
                // Garante valores padrão caso venha vazio
                $tipo = $s['tipo'] ?? 'work';
                $qtd  = $s['qtd'] ?? 1;
                $reps = $s['reps'] ?? '';
                $desc = $s['desc'] ?? '';
                $rpe  = !empty($s['rpe']) ? $s['rpe'] : null;

                $stmtSerie->execute([
                    $exercicio_id, $tipo, $qtd, $reps, $desc, $rpe
                ]);
            }
        }

        $pdo->commit();

        // --- O SEGREDO ESTÁ AQUI EMBAIXO ---
        ob_clean(); // Limpa qualquer lixo (espaços, notices, html) que tenha ocorrido antes
        echo json_encode(['status' => 'success', 'message' => 'Exercício adicionado com sucesso!']);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        ob_clean(); // Limpa buffer antes do erro também
        echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        exit;
    }
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}
?>