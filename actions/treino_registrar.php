<?php
// actions/treino_registrar.php
session_start();
require_once '../config/db_connect.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    // Se for requisição AJAX, retorna JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
        exit;
    }
    // Se for formulário normal, redireciona
    header("Location: ../login.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$treino_id = filter_input(INPUT_POST, 'treino_id', FILTER_SANITIZE_NUMBER_INT);
$divisao_id = filter_input(INPUT_POST, 'divisao_id', FILTER_SANITIZE_NUMBER_INT);
$data_treino = date('Y-m-d H:i:s');

// Dados vindos do formulário
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

    // SQL CORRETO:
    // Preenchemos 'serie_numero' (coluna antiga NOT NULL) com o mesmo valor de 'serie_id'
    // Isso evita o erro 1364 e mantém a integridade dos dados.
    $sql = "INSERT INTO treino_historico 
            (aluno_id, treino_id, divisao_id, exercicio_id, serie_id, serie_numero, carga_kg, reps_realizadas, data_treino, numero_serie) 
            VALUES 
            (:aluno, :treino, :divisao, :exercicio, :serie, :serie_num_legacy, :carga, :reps, :data, :ordem)";
    
    $stmt_insert = $pdo->prepare($sql);
    $stmt_find_ex = $pdo->prepare("SELECT exercicio_id FROM series WHERE id = ?");

    // Itera sobre as séries enviadas (serie_id_form é o ID da configuração da série, ex: 540)
    foreach ($cargas as $serie_id_form => $series_data) {
        
        // 1. Descobre o exercício dono da série
        $stmt_find_ex->execute([$serie_id_form]);
        $exercicio_id = $stmt_find_ex->fetchColumn();

        if (!$exercicio_id) continue;

        // 2. Itera sobre as repetições (ordem_execucao é 1, 2, 3...)
        foreach ($series_data as $ordem_execucao => $carga_valor) {
            
            $reps_valor = $reps_feitas[$serie_id_form][$ordem_execucao] ?? 0;

            // Formatação de valores (troca vírgula por ponto)
            $carga_valor = $carga_valor !== '' ? str_replace(',', '.', $carga_valor) : 0;
            $reps_valor = $reps_valor !== '' ? $reps_valor : 0;

            // Só salva se houver dados preenchidos
            if ($carga_valor > 0 || $reps_valor > 0) {
                
                $stmt_insert->execute([
                    'aluno'     => $aluno_id,
                    'treino'    => $treino_id,
                    'divisao'   => $divisao_id,
                    'exercicio' => $exercicio_id,
                    
                    'serie'     => $serie_id_form, // Coluna NOVA (serie_id)
                    'serie_num_legacy' => $serie_id_form, // Coluna ANTIGA (serie_numero) recebe o ID para compatibilidade
                    
                    'carga'     => $carga_valor,
                    'reps'      => $reps_valor,
                    'data'      => $data_treino,
                    'ordem'     => $ordem_execucao // Coluna de ordem (1, 2, 3...)
                ]);
            }
        }
    }

    $pdo->commit();
    
    // Resposta para AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit;
    }

    // Resposta para Formulário Comum
    header("Location: ../usuario.php?msg=treino_concluido");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    
    // Resposta de Erro
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Erro Banco: ' . $e->getMessage()]);
        exit;
    }
    
    die("Erro ao salvar treino: " . $e->getMessage());
}
?>