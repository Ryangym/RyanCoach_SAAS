<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Dados do Exercício
        $divisao_id = $_POST['divisao_id'];
        $treino_id = $_POST['treino_id']; // Para redirecionar de volta
        $nome = $_POST['nome_exercicio'];
        $mecanica = $_POST['tipo_mecanica'];
        $video = $_POST['video_url'];
        $obs = $_POST['observacao'];
        
        // Ordem: Pega o último número de ordem + 1
        $stmtOrder = $pdo->prepare("SELECT MAX(ordem) FROM exercicios WHERE divisao_id = ?");
        $stmtOrder->execute([$divisao_id]);
        $nova_ordem = $stmtOrder->fetchColumn() + 1;

        // Salva Exercício
        $sqlEx = "INSERT INTO exercicios (divisao_id, nome_exercicio, tipo_mecanica, video_url, observacao_exercicio, ordem) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmtEx = $pdo->prepare($sqlEx);
        $stmtEx->execute([$divisao_id, $nome, $mecanica, $video, $obs, $nova_ordem]);
        $exercicio_id = $pdo->lastInsertId();

        // 2. Dados das Séries (Recebido como JSON String do Front-end)
        $series_json = $_POST['series_data'];
        $series_lista = json_decode($series_json, true);

        if (!empty($series_lista)) {
            $sqlSerie = "INSERT INTO series (exercicio_id, categoria, quantidade, reps_fixas, descanso_fixo, rpe_previsto) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $stmtSerie = $pdo->prepare($sqlSerie);

            foreach ($series_lista as $s) {
                $stmtSerie->execute([
                    $exercicio_id,
                    $s['tipo'],
                    $s['qtd'],
                    $s['reps'],     
                    $s['desc'],
                    $s['rpe']
                ]);
            }
        }

        $pdo->commit();
        
        // --- CORREÇÃO AQUI ---
        header("Location: ../admin.php?pagina=treino_painel&id=" . $treino_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erro: " . $e->getMessage();
    }
}
?>