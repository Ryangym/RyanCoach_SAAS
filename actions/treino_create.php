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
        $divisao = strtoupper($_POST['divisao'] ?? 'A');
        $data_inicio = $_POST['data_inicio'];
        $dias_selecionados = $_POST['dias_semana'] ?? []; 
        $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);

        if (!$aluno_id || !$nome_treino || !$data_inicio) {
            throw new Exception("Preencha todos os campos obrigatórios.");
        }

        if (empty($dias_selecionados)) {
            throw new Exception("Selecione pelo menos um dia de treino.");
        }
        
        sort($dias_selecionados); 
        $dias_json = json_encode($dias_selecionados);

        // --- LÓGICA DE DATA FINAL ---
        $objData = new DateTime($data_inicio);
        $objData->modify('+11 weeks');
        $data_fim_calculada = $objData->format('Y-m-d');
        
        // Ajuste fino para terminar no último dia de treino real da semana
        $tempData = clone $objData;
        for ($d = 0; $d < 7; $d++) {
            if (in_array($tempData->format('N'), $dias_selecionados)) {
                $data_fim_calculada = $tempData->format('Y-m-d');
            }
            $tempData->modify('+1 day');
        }

        // 3. Inserir Treino (CORRIGIDO: Agora usa 'criador_id')
        $sql = "INSERT INTO treinos 
                (aluno_id, criador_id, nome, nivel_plano, data_inicio, data_fim, dias_semana, divisao_nome, observacoes, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $aluno_id, 
            $criador_id, // Passando o ID do usuário logado
            $nome_treino, 
            $nivel, 
            $data_inicio, 
            $data_fim_calculada, 
            $dias_json, 
            $divisao,
            $observacoes
        ]);
        
        $treino_id = $pdo->lastInsertId();

        // 4. Periodização (Se aplicável)
        if ($nivel !== 'basico') {
            $stmt = $pdo->prepare("INSERT INTO periodizacoes (treino_id, data_inicio_macro, data_fim_macro, objetivo_macro) VALUES (?, ?, ?, ?)");
            $stmt->execute([$treino_id, $data_inicio, $data_fim_calculada, 'Hipertrofia']);
            $periodizacao_id = $pdo->lastInsertId();

            $data_atual = new DateTime($data_inicio);
            
            for ($i = 1; $i <= 12; $i++) {
                $semana_start = clone $data_atual;
                $semana_end = clone $data_atual;
                $semana_end->modify('+6 days');

                $stmt = $pdo->prepare("INSERT INTO microciclos (periodizacao_id, semana_numero, nome_fase, data_inicio_semana, data_fim_semana) VALUES (?, ?, ?, ?, ?)");
                
                $fase = ($i <= 4) ? 'Base' : (($i <= 8) ? 'Intensificação' : 'Polimento');
                $stmt->execute([$periodizacao_id, $i, $fase, $semana_start->format('Y-m-d'), $semana_end->format('Y-m-d')]);

                $data_atual->modify('+1 week');
            }
        }

        // 5. Divisões
        $letras = str_split(preg_replace('/[^A-Z]/', '', $divisao));
        if(empty($letras)) $letras = ['A'];

        foreach ($letras as $letra) {
            $pdo->prepare("INSERT INTO treino_divisoes (treino_id, letra, nome) VALUES (?, ?, ?)")
                ->execute([$treino_id, $letra, "Treino $letra"]);
        }

        $pdo->commit();
        
        // SUCESSO: Retorna o ID do treino criado para o JS redirecionar
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