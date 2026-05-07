<?php
// Limpa qualquer saída anterior para garantir JSON limpo
ob_start();

session_start();
require_once '../config/db_connect.php';

// Desativa erros na tela
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// 1. Verificação de Segurança
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$criador_id = $_SESSION['user_id'];
$tipo_conta = $_SESSION['tipo_conta'] ?? 'personal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 2. Dados do Formulário
        if ($tipo_conta === 'atleta') {
            $aluno_id = $criador_id; 
        } else {
            $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_SANITIZE_NUMBER_INT);
        }

        $nome_treino = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $nivel = $_POST['nivel'] ?? 'basico';
        $data_inicio = $_POST['data_inicio'];
        $dias_selecionados = $_POST['dias_semana'] ?? []; // Array
        $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);

        if (!$aluno_id || !$nome_treino || !$data_inicio) {
            throw new Exception("Preencha todos os campos obrigatórios.");
        }

        if (empty($dias_selecionados)) {
            throw new Exception("Selecione pelo menos um dia de treino.");
        }
        
        // --- CÁLCULO DA DIVISÃO AUTOMÁTICA (SUBSTITUI O INPUT DO FORM) ---
        $qtd_dias = count($dias_selecionados);
        $alfabeto = "ABCDEFG";
        $divisao = substr($alfabeto, 0, $qtd_dias); // Ex: Se escolheu 4 dias, fica "ABCD"

        // Ordena os dias (Ex: [0, 1, 5] -> Dom, Seg, Sex)
        sort($dias_selecionados); 
        $dias_json = json_encode($dias_selecionados);

        // --- LÓGICA DE DATA FINAL ---
        $objData = new DateTime($data_inicio);
        $objData->modify('+11 weeks');
        $data_fim_calculada = $objData->format('Y-m-d');
        
        // Ajuste fino para terminar no último dia de treino real da semana
        $tempData = clone $objData;
        for ($d = 0; $d < 7; $d++) {
            if (in_array($tempData->format('w'), $dias_selecionados)) {
                $data_fim_calculada = $tempData->format('Y-m-d');
            }
            $tempData->modify('+1 day');
        }

        // 3. Inserir Treino
        $sql = "INSERT INTO treinos 
                (aluno_id, criador_id, nome, nivel_plano, data_inicio, data_fim, dias_semana, divisao_nome, observacoes, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $aluno_id, 
            $criador_id, 
            $nome_treino, 
            $nivel, 
            $data_inicio, 
            $data_fim_calculada, 
            $dias_json, 
            $divisao,
            $observacoes
        ]);
        
        $treino_id = $pdo->lastInsertId();

        // 4. Periodização (Se não for básico)
        if ($nivel !== 'basico') {
            $stmt = $pdo->prepare("INSERT INTO periodizacoes (treino_id, data_inicio_macro, data_fim_macro, objetivo_macro) VALUES (?, ?, ?, ?)");
            $stmt->execute([$treino_id, $data_inicio, $data_fim_calculada, 'Hipertrofia']);
            $periodizacao_id = $pdo->lastInsertId();

            // --- CÁLCULO INTELIGENTE ---
            $dias_numericos = [];
            foreach($dias_selecionados as $d) {
                $dias_numericos[] = (int)$d; 
            }

            if(empty($dias_numericos)) {
                $primeiro_dia_semana = 1; // Seg
                $ultimo_dia_semana   = 5; // Sex
            } else {
                $primeiro_dia_semana = min($dias_numericos);
                $ultimo_dia_semana   = max($dias_numericos);
            }

            $dia_semana_atual_inicio = date('w', strtotime($data_inicio));
            $domingo_base = date('Y-m-d', strtotime($data_inicio . " - " . $dia_semana_atual_inicio . " days"));

            $sql_micro = "INSERT INTO microciclos (periodizacao_id, semana_numero, nome_fase, data_inicio_semana, data_fim_semana) VALUES (?, ?, ?, ?, ?)";
            $stmt_micro = $pdo->prepare($sql_micro);

            for ($i = 0; $i < 12; $i++) {
                $semana_num = $i + 1;
                
                $offset_inicio = $primeiro_dia_semana; 
                $dt_inicio = date('Y-m-d', strtotime($domingo_base . " + $i week + $offset_inicio days"));

                $offset_fim = $ultimo_dia_semana;
                $dt_fim = date('Y-m-d', strtotime($domingo_base . " + $i week + $offset_fim days"));

                // Define a Fase
                if ($semana_num <= 4) {
                    $nome_fase = 'Base';
                } elseif ($semana_num <= 8) {
                    $nome_fase = 'Intensificação';
                } else {
                    $nome_fase = 'Polimento';
                }

                $stmt_micro->execute([$periodizacao_id, $semana_num, $nome_fase, $dt_inicio, $dt_fim]);
            }
        }

        // 5. Divisões (A, B, C...)
        $letras = str_split(preg_replace('/[^A-Z]/', '', $divisao));
        if(empty($letras)) $letras = ['A'];

        foreach ($letras as $letra) {
            $pdo->prepare("INSERT INTO treino_divisoes (treino_id, letra, nome) VALUES (?, ?, ?)")
                ->execute([$treino_id, $letra, "Treino $letra"]);
        }

        $pdo->commit();
        
        ob_clean();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Treino criado com sucesso!',
            'treino_id' => $treino_id
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        exit;
    }
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}
?>