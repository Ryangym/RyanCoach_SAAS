<?php
session_start();
require_once '../config/db_connect.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Dados Básicos
        $exercicio_id = $_POST['exercicio_id'];
        $treino_id = $_POST['treino_id'];
        
        $nome = $_POST['nome_exercicio'];
        $mecanica = $_POST['tipo_mecanica'];
        $video = $_POST['video_url'];
        $obs = $_POST['observacao'];

        // Atualiza Exercício
        $sqlEx = "UPDATE exercicios SET nome_exercicio = ?, tipo_mecanica = ?, video_url = ?, observacao_exercicio = ? WHERE id = ?";
        $stmtEx = $pdo->prepare($sqlEx);
        $stmtEx->execute([$nome, $mecanica, $video, $obs, $exercicio_id]);

        // 2. Atualizar Séries (Apaga antigas e insere novas)
        $pdo->prepare("DELETE FROM series WHERE exercicio_id = ?")->execute([$exercicio_id]);

        $series_json = $_POST['series_data'];
        $series_lista = json_decode($series_json, true);

        if (!empty($series_lista)) {
            $sqlSerie = "INSERT INTO series (exercicio_id, categoria, quantidade, reps_fixas, descanso_fixo, rpe_previsto) VALUES (?, ?, ?, ?, ?, ?)";
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
        header("Location: ../admin.php?pagina=treino_painel&id=" . $treino_id . "&msg=exercicio_editado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erro: " . $e->getMessage();
    }
}
?>