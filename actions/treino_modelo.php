<?php
session_start();
require_once '../config/db_connect.php';

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não logado']));
}

$aluno_id = $_SESSION['user_id'];
$modelo   = $_POST['modelo'] ?? '';

// --- CONFIGURAÇÃO DOS MODELOS (TODOS NIVEL BÁSICO) ---
$templates = [
    
    // 1. PPL (PUSH PULL LEGS) - 6x
    'hipertrofia_abc' => [
        'nome' => 'Protocolo PPL (Push/Pull/Legs)',
        'nivel' => 'basico', // Forçado Básico
        'divisao_nome' => 'ABC',
        'dias_semana' => [1, 2, 3, 4, 5, 6], 
        'divisoes' => [
            'A' => [
                'nome' => 'Push (Peito, Ombro, Tríceps)',
                'exercicios' => [
                    ['nome' => 'Supino Reto com Barra', 'series' => 4, 'reps' => '6-8', 'desc' => '120s', 'obs' => 'Foco em Carga'],
                    ['nome' => 'Supino Inclinado Halteres', 'series' => 3, 'reps' => '8-12', 'desc' => '90s', 'obs' => 'Amplitude'],
                    ['nome' => 'Desenvolvimento Halteres', 'series' => 3, 'reps' => '8-12', 'desc' => '90s', 'obs' => 'Sem impulso'],
                    ['nome' => 'Elevação Lateral', 'series' => 4, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Controlada'],
                    ['nome' => 'Tríceps Testa', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Cotovelos fechados'],
                    ['nome' => 'Tríceps Corda', 'series' => 3, 'reps' => '12-15', 'desc' => '45s', 'obs' => 'Falha']
                ]
            ],
            'B' => [
                'nome' => 'Pull (Costas, Bíceps)',
                'exercicios' => [
                    ['nome' => 'Levantamento Terra', 'series' => 3, 'reps' => '5-8', 'desc' => '180s', 'obs' => 'Técnica perfeita'],
                    ['nome' => 'Puxada Alta', 'series' => 3, 'reps' => '8-12', 'desc' => '90s', 'obs' => 'Foco na dorsal'],
                    ['nome' => 'Remada Curvada', 'series' => 3, 'reps' => '8-10', 'desc' => '120s', 'obs' => 'Tronco estável'],
                    ['nome' => 'Crucifixo Inverso', 'series' => 3, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Posterior de ombro'],
                    ['nome' => 'Rosca Direta', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Sem roubar'],
                    ['nome' => 'Rosca Martelo', 'series' => 3, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Braquial']
                ]
            ],
            'C' => [
                'nome' => 'Legs (Pernas Completo)',
                'exercicios' => [
                    ['nome' => 'Agachamento Livre', 'series' => 4, 'reps' => '6-8', 'desc' => '180s', 'obs' => 'Amplitude máxima'],
                    ['nome' => 'Leg Press 45', 'series' => 3, 'reps' => '10-12', 'desc' => '120s', 'obs' => 'Não travar joelho'],
                    ['nome' => 'Stiff', 'series' => 3, 'reps' => '10-12', 'desc' => '90s', 'obs' => 'Posterior'],
                    ['nome' => 'Cadeira Extensora', 'series' => 3, 'reps' => '15', 'desc' => '60s', 'obs' => 'Isolador'],
                    ['nome' => 'Mesa Flexora', 'series' => 3, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Isolador'],
                    ['nome' => 'Panturrilha em Pé', 'series' => 4, 'reps' => '15-20', 'desc' => '45s', 'obs' => 'Lento']
                ]
            ]
        ]
    ],

    // 2. HÍBRIDO 5x (Upper/Lower + PPL)
    'hibrido_5x' => [
        'nome' => 'Híbrido Pro (Upper/Lower + PPL)',
        'nivel' => 'basico', // Forçado Básico
        'divisao_nome' => 'ULPPL',
        'dias_semana' => [1, 2, 3, 4, 5], 
        'divisoes' => [
            'A' => [
                'nome' => 'Upper Power (Força Superiores)',
                'exercicios' => [
                    ['nome' => 'Supino Reto (Barra)', 'series' => 4, 'reps' => '5-8', 'desc' => '120s', 'obs' => 'Carga Alta'],
                    ['nome' => 'Remada Curvada', 'series' => 4, 'reps' => '6-8', 'desc' => '120s', 'obs' => 'Carga Alta'],
                    ['nome' => 'Desenvolvimento Militar', 'series' => 3, 'reps' => '6-10', 'desc' => '90s', 'obs' => 'Ombros'],
                    ['nome' => 'Barra Fixa (ou Puxada)', 'series' => 3, 'reps' => 'Falha', 'desc' => '90s', 'obs' => 'Dorsal'],
                    ['nome' => 'Mergulho (Paralelas)', 'series' => 3, 'reps' => 'Falha', 'desc' => '60s', 'obs' => 'Peito/Tríceps']
                ]
            ],
            'B' => [
                'nome' => 'Lower Power (Força Inferiores)',
                'exercicios' => [
                    ['nome' => 'Agachamento Livre', 'series' => 4, 'reps' => '5-8', 'desc' => '180s', 'obs' => 'O Rei'],
                    ['nome' => 'Leg Press 45', 'series' => 3, 'reps' => '8-10', 'desc' => '120s', 'obs' => 'Amplitude'],
                    ['nome' => 'Stiff (Barra)', 'series' => 3, 'reps' => '8-10', 'desc' => '90s', 'obs' => 'Posterior'],
                    ['nome' => 'Panturrilha em Pé', 'series' => 4, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Pesado'],
                    ['nome' => 'Prancha Abdominal', 'series' => 3, 'reps' => '45s', 'desc' => '45s', 'obs' => 'Core']
                ]
            ],
            'C' => [
                'nome' => 'Push Hyper (Peito/Ombro/Tri)',
                'exercicios' => [
                    ['nome' => 'Supino Inclinado (Halter)', 'series' => 3, 'reps' => '10-12', 'desc' => '90s', 'obs' => 'Estética'],
                    ['nome' => 'Crucifixo Máquina', 'series' => 3, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Pump'],
                    ['nome' => 'Elevação Lateral', 'series' => 4, 'reps' => '12-15', 'desc' => '45s', 'obs' => 'Drop na última'],
                    ['nome' => 'Tríceps Corda', 'series' => 3, 'reps' => '12-15', 'desc' => '45s', 'obs' => 'Esmaga'],
                    ['nome' => 'Tríceps Testa', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Alongamento']
                ]
            ],
            'D' => [
                'nome' => 'Pull Hyper (Costas/Bíceps)',
                'exercicios' => [
                    ['nome' => 'Puxada Frente', 'series' => 3, 'reps' => '10-12', 'desc' => '90s', 'obs' => 'Controle'],
                    ['nome' => 'Remada Baixa (Triângulo)', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Miolo das costas'],
                    ['nome' => 'Crucifixo Inverso', 'series' => 3, 'reps' => '15', 'desc' => '45s', 'obs' => 'Ombro Post.'],
                    ['nome' => 'Rosca Scott', 'series' => 3, 'reps' => '12', 'desc' => '60s', 'obs' => 'Bíceps'],
                    ['nome' => 'Rosca Martelo', 'series' => 3, 'reps' => '12-15', 'desc' => '45s', 'obs' => 'Antebraço']
                ]
            ],
            'E' => [
                'nome' => 'Legs Hyper (Pernas Volume)',
                'exercicios' => [
                    ['nome' => 'Agachamento Globet (ou Hack)', 'series' => 3, 'reps' => '10-12', 'desc' => '90s', 'obs' => 'Controle'],
                    ['nome' => 'Afundo Caminhando', 'series' => 3, 'reps' => '12 cada', 'desc' => '60s', 'obs' => 'Glúteo/Quadrícep'],
                    ['nome' => 'Cadeira Extensora', 'series' => 3, 'reps' => '15', 'desc' => '45s', 'obs' => 'Isola'],
                    ['nome' => 'Mesa Flexora', 'series' => 4, 'reps' => '12-15', 'desc' => '45s', 'obs' => 'Posterior'],
                    ['nome' => 'Panturrilha Sentado', 'series' => 4, 'reps' => '15-20', 'desc' => '30s', 'obs' => 'Solear']
                ]
            ]
        ]
    ],

    // 3. UPPER / LOWER - 4x
    'upper_lower_ab' => [
        'nome' => 'Upper / Lower (Alta Frequência)',
        'nivel' => 'basico', // Forçado Básico
        'divisao_nome' => 'AB',
        'dias_semana' => [1, 2, 4, 5], 
        'divisoes' => [
            'A' => [
                'nome' => 'Upper (Membros Superiores)',
                'exercicios' => [
                    ['nome' => 'Supino Reto', 'series' => 3, 'reps' => '8-10', 'desc' => '90s', 'obs' => 'Composto'],
                    ['nome' => 'Remada Curvada', 'series' => 3, 'reps' => '8-10', 'desc' => '90s', 'obs' => 'Composto'],
                    ['nome' => 'Desenvolvimento', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Ombros'],
                    ['nome' => 'Puxada Frente', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Dorsal'],
                    ['nome' => 'Rosca Direta + Tríceps Polia', 'series' => 3, 'reps' => '12+12', 'desc' => '60s', 'obs' => 'Bi-set Braços']
                ]
            ],
            'B' => [
                'nome' => 'Lower (Membros Inferiores)',
                'exercicios' => [
                    ['nome' => 'Agachamento Livre', 'series' => 3, 'reps' => '8-10', 'desc' => '120s', 'obs' => 'Composto'],
                    ['nome' => 'RDL / Stiff', 'series' => 3, 'reps' => '8-10', 'desc' => '90s', 'obs' => 'Posterior'],
                    ['nome' => 'Afundo Caminhando', 'series' => 3, 'reps' => '12 cada', 'desc' => '60s', 'obs' => 'Unilateral'],
                    ['nome' => 'Leg Press', 'series' => 3, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Volume'],
                    ['nome' => 'Panturrilha Sentado', 'series' => 4, 'reps' => '15', 'desc' => '45s', 'obs' => 'Solear'],
                    ['nome' => 'Prancha Abdominal', 'series' => 3, 'reps' => '45s', 'desc' => '45s', 'obs' => 'Core']
                ]
            ]
        ]
    ],

    // 4. FULLBODY - 3x
    'fullbody_3x' => [
        'nome' => 'Fullbody (Corpo Inteiro)',
        'nivel' => 'basico', // Forçado Básico
        'divisao_nome' => 'FB',
        'dias_semana' => [1, 3, 5], 
        'divisoes' => [
            'A' => [
                'nome' => 'Fullbody - Foco A (Agachamento)',
                'exercicios' => [
                    ['nome' => 'Agachamento Globet (Halter)', 'series' => 3, 'reps' => '10-12', 'desc' => '90s', 'obs' => 'Base'],
                    ['nome' => 'Supino Reto (Máquina ou Halter)', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Empurrar'],
                    ['nome' => 'Remada Máquina', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Puxar'],
                    ['nome' => 'Desenvolvimento', 'series' => 3, 'reps' => '12', 'desc' => '60s', 'obs' => 'Ombros'],
                    ['nome' => 'Abdominal Supra', 'series' => 3, 'reps' => '15', 'desc' => '45s', 'obs' => 'Core']
                ]
            ],
            'B' => [
                'nome' => 'Fullbody - Foco B (Terra/Costas)',
                'exercicios' => [
                    ['nome' => 'Levantamento Terra (Halteres)', 'series' => 3, 'reps' => '10-12', 'desc' => '90s', 'obs' => 'Posterior'],
                    ['nome' => 'Puxada Frente', 'series' => 3, 'reps' => '10-12', 'desc' => '60s', 'obs' => 'Costas'],
                    ['nome' => 'Flexão de Braço (ou Supino)', 'series' => 3, 'reps' => 'Max', 'desc' => '60s', 'obs' => 'Peitoral'],
                    ['nome' => 'Leg Press Horizontal', 'series' => 3, 'reps' => '12-15', 'desc' => '60s', 'obs' => 'Pernas'],
                    ['nome' => 'Prancha', 'series' => 3, 'reps' => '30s', 'desc' => '45s', 'obs' => 'Core']
                ]
            ]
        ]
    ]
];

// Verifica se modelo existe
if (!array_key_exists($modelo, $templates)) {
    die(json_encode(['success' => false, 'message' => 'Modelo não encontrado']));
}

try {
    $pdo->beginTransaction();
    $t = $templates[$modelo];
    
    // 1. Criar Treino
    // Garantimos que 'aluno_id' e 'criador_id' sejam preenchidos
    // Garantimos que nivel_plano seja 'basico'
    $stmt = $pdo->prepare("INSERT INTO treinos (nome, aluno_id, criador_id, nivel_plano, data_inicio, data_fim, dias_semana, divisao_nome, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $dias_json = json_encode($t['dias_semana']);
    $data_fim = date('Y-m-d', strtotime('+60 days'));
    
    $stmt->execute([
        $t['nome'], 
        $aluno_id, 
        $aluno_id, // Criador é o próprio aluno
        'basico', // FORÇANDO BÁSICO
        date('Y-m-d'), 
        $data_fim, 
        $dias_json, 
        $t['divisao_nome']
    ]);
    $treino_id = $pdo->lastInsertId();

    // 2. Criar Divisões
    foreach ($t['divisoes'] as $letra => $div) {
        $stmt_d = $pdo->prepare("INSERT INTO treino_divisoes (treino_id, letra, nome) VALUES (?, ?, ?)");
        $stmt_d->execute([$treino_id, $letra, $div['nome']]);
        $divisao_id = $pdo->lastInsertId();

        // 3. Criar Exercícios
        $ordem = 1;
        foreach ($div['exercicios'] as $ex) {
            
            // Verifica observação
            $obs = $ex['obs'] ?? null;

            // Insere na tabela 'exercicios' (com a coluna correta 'observacao_exercicio')
            $stmt_e = $pdo->prepare("INSERT INTO exercicios (divisao_id, nome_exercicio, ordem, observacao_exercicio) VALUES (?, ?, ?, ?)");
            $stmt_e->execute([$divisao_id, $ex['nome'], $ordem, $obs]);
            $exercicio_id = $pdo->lastInsertId();

            // 4. Criar Séries
            // Insere na tabela 'series' usando categoria 'work' e quantidade correta
            $stmt_s = $pdo->prepare("INSERT INTO series (exercicio_id, categoria, quantidade, reps_fixas, descanso_fixo) VALUES (?, 'work', ?, ?, ?)");
            $stmt_s->execute([$exercicio_id, $ex['series'], $ex['reps'], $ex['desc']]);
            
            $ordem++;
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erro ao criar: ' . $e->getMessage()]);
}
?>