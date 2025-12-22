<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Dados do Formulário
        $aluno_id = $_POST['aluno_id'];
        $nome_treino = $_POST['nome'];
        $nivel = $_POST['nivel'];
        $divisao = strtoupper($_POST['divisao']);
        $data_inicio = $_POST['data_inicio'];
        $dias_selecionados = $_POST['dias_semana'] ?? []; // Array [1, 3, 5]

        if (empty($dias_selecionados)) {
            throw new Exception("Selecione pelo menos um dia de treino.");
        }
        
        sort($dias_selecionados); 
        $dias_json = json_encode($dias_selecionados);

        // --- NOVA LÓGICA DE DATA FINAL PRECISA ---
        // Objetivo: Encontrar o último dia de treino da 12ª semana
        
        $objData = new DateTime($data_inicio);
        // Avança para o início da 12ª semana (Soma 11 semanas à data inicial)
        $objData->modify('+11 weeks');
        
        // Agora percorremos os 7 dias dessa última semana para achar o último dia de treino
        $data_fim_calculada = $objData->format('Y-m-d'); // Fallback
        
        // Cria um loop de 7 dias a partir do início da última semana
        for ($d = 0; $d < 7; $d++) {
            // N = 1(Seg) a 7(Dom)
            $dia_semana_atual = $objData->format('N');
            
            // Se este dia da semana está nos dias de treino, ele é um candidato a data final
            if (in_array($dia_semana_atual, $dias_selecionados)) {
                $data_fim_calculada = $objData->format('Y-m-d');
            }
            
            // Avança um dia para verificar o próximo
            $objData->modify('+1 day');
        }
        // ------------------------------------------

        // 2. Inserir na tabela `treinos`
        $stmt = $pdo->prepare("INSERT INTO treinos (aluno_id, admin_id, nome, nivel_plano, data_inicio, data_fim, dias_semana, divisao_nome) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$aluno_id, $_SESSION['user_id'], $nome_treino, $nivel, $data_inicio, $data_fim_calculada, $dias_json, $divisao]);
        $treino_id = $pdo->lastInsertId();

        // 3. Se for Periodizado, gerar Microciclos
        if ($nivel !== 'basico') {
            
            // Criar Macro
            $stmt = $pdo->prepare("INSERT INTO periodizacoes (treino_id, data_inicio_macro, data_fim_macro, objetivo_macro) VALUES (?, ?, ?, ?)");
            $stmt->execute([$treino_id, $data_inicio, $data_fim_calculada, 'Hipertrofia']);
            $periodizacao_id = $pdo->lastInsertId();

            // Calcular Datas das 12 Semanas (Loop)
            $data_atual = new DateTime($data_inicio);
            
            for ($i = 1; $i <= 12; $i++) {
                // Copia a data atual para não alterar a referência do loop principal
                $semana_start = clone $data_atual;
                
                // Define o fim desta semana (6 dias depois do início)
                $semana_end = clone $data_atual;
                $semana_end->modify('+6 days');

                // Encontra os dias reais de treino dentro dessa semana
                $datas_reais = [];
                $intervalo = new DatePeriod($semana_start, new DateInterval('P1D'), $semana_end->modify('+1 day'));

                foreach ($intervalo as $dt) {
                    if (in_array($dt->format('N'), $dias_selecionados)) {
                        $datas_reais[] = $dt->format('Y-m-d');
                    }
                }

                // Define início e fim do microciclo baseado nos treinos reais
                $inicio_micro = !empty($datas_reais) ? $datas_reais[0] : $semana_start->format('Y-m-d');
                $fim_micro = !empty($datas_reais) ? end($datas_reais) : $semana_end->format('Y-m-d');

                // Define nome da fase
                $fase = ($i <= 4) ? 'Base' : (($i <= 8) ? 'Intensificação' : 'Polimento');

                // Salva Microciclo
                $stmt = $pdo->prepare("INSERT INTO microciclos (periodizacao_id, semana_numero, nome_fase, data_inicio_semana, data_fim_semana, descanso_compostos, descanso_isoladores) VALUES (?, ?, ?, ?, ?, 120, 90)");
                $stmt->execute([$periodizacao_id, $i, $fase, $inicio_micro, $fim_micro]);

                // Prepara data para a próxima semana (+1 semana a partir do início desta)
                $data_atual->modify('+1 week');
            }
        }

        // 4. Criar Divisões (A, B, C...)
        $letras = str_split(preg_replace('/[^A-Z]/', '', $divisao));
        if(empty($letras)) $letras = ['A'];

        foreach ($letras as $letra) {
            $pdo->prepare("INSERT INTO treino_divisoes (treino_id, letra, nome) VALUES (?, ?, ?)")
                ->execute([$treino_id, $letra, "Treino $letra"]);
        }

        $pdo->commit();
        echo "<script>alert('Treino estruturado com sucesso!'); window.location.href='../admin.php?pagina=treinos_editor';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Erro: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>