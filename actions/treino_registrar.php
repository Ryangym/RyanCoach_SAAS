<?php
// actions/treino_registrar.php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$treino_id = filter_input(INPUT_POST, 'treino_id', FILTER_SANITIZE_NUMBER_INT);
$divisao_id = filter_input(INPUT_POST, 'divisao_id', FILTER_SANITIZE_NUMBER_INT);
$data_treino = date('Y-m-d H:i:s');

$cargas = $_POST['carga'] ?? [];
$reps_feitas = $_POST['reps'] ?? [];

if (!$treino_id) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'ID do treino inválido.']);
        exit;
    }
    header("Location: ../usuario.php?msg=erro_id");
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO treino_historico 
            (aluno_id, treino_id, divisao_id, exercicio_id, serie_id, serie_numero, carga_kg, reps_realizadas, data_treino, numero_serie, dados_tecnicos) 
            VALUES 
            (:aluno, :treino, :divisao, :exercicio, :serie, :serie_num_legacy, :carga, :reps, :data, :ordem, :dados_tecnicos)";
    
    $stmt_insert = $pdo->prepare($sql);
    
    // MUDANÇA: Agora buscamos também a 'tecnica' para saber se é Cluster ou Rest Pause
    $stmt_find_ex = $pdo->prepare("SELECT exercicio_id, tecnica FROM series WHERE id = ?");

    foreach ($cargas as $serie_id_form => $series_data) {
        
        $stmt_find_ex->execute([$serie_id_form]);
        $dados_serie = $stmt_find_ex->fetch(PDO::FETCH_ASSOC);
        
        if (!$dados_serie) continue;
        
        $exercicio_id = $dados_serie['exercicio_id'];
        $tecnica_original = strtolower(trim($dados_serie['tecnica'] ?? 'normal'));

        foreach ($series_data as $chave_input => $carga_valor) {
            
            $numero_serie_real = (int)$chave_input;
            $json_info = [];

            // A. Drop Set
            if (strpos($chave_input, '_drop_') !== false) {
                $parts = explode('_drop_', $chave_input);
                $json_info['tipo'] = 'dropset';
                $json_info['drop_index'] = (int)$parts[1];
            }

            // B. Cluster ou Rest Pause (String "10+5+3")
            $reps_input = $reps_feitas[$serie_id_form][$chave_input] ?? 0;
            $reps_int = 0;

            if (is_numeric($reps_input)) {
                // Número simples
                $reps_int = $reps_input;
            } else {
                // String de soma (ex: 4+4+4+3)
                $partes_reps = explode('+', $reps_input);
                foreach($partes_reps as $pr) {
                    $reps_int += (int)trim($pr);
                }
                
                $json_info['reps_string'] = $reps_input; 
                
                // MUDANÇA: Verifica no banco qual é a técnica correta para salvar o tipo certo
                if ($tecnica_original === 'clusterset') {
                    $json_info['tipo'] = 'clusterset';
                } else {
                    $json_info['tipo'] = 'restpause'; 
                }
            }

            $carga_valor = $carga_valor !== '' ? str_replace(',', '.', $carga_valor) : 0;
            
            if ($carga_valor > 0 || $reps_int > 0) {
                $dados_tecnicos_json = !empty($json_info) ? json_encode($json_info) : null;

                $stmt_insert->execute([
                    'aluno'      => $aluno_id,
                    'treino'     => $treino_id,
                    'divisao'    => $divisao_id,
                    'exercicio'  => $exercicio_id,
                    'serie'      => $serie_id_form, 
                    'serie_num_legacy' => $serie_id_form, 
                    'carga'      => $carga_valor,
                    'reps'       => $reps_int, 
                    'data'       => $data_treino,
                    'ordem'      => $numero_serie_real,
                    'dados_tecnicos' => $dados_tecnicos_json 
                ]);
            }
        }
    }

    $pdo->commit();
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit;
    }

    header("Location: ../usuario.php?msg=treino_concluido");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Erro Banco: ' . $e->getMessage()]);
        exit;
    }
    die("Erro ao salvar treino: " . $e->getMessage());
}
?>