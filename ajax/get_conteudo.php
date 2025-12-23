<?php
if(session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db_connect.php';

$aluno_id = $_SESSION['user_id'];
$pagina_raw = $_GET['pagina'] ?? 'dashboard';
$partes = explode('&', $pagina_raw);
$pagina = $partes[0];
$hoje = date('Y-m-d');
$divisao_req = $_GET['divisao_id'] ?? null; // Usado no Realizar Treino
$treino_req  = $_GET['treino_id'] ?? null;  // Usado no Visualizar Treino
$micro_req   = $_GET['micro_id'] ?? null;   // Usado no Visualizar Treino

// Nome do Usuário
$nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Atleta'));
$primeiro_nome = strtoupper($nome[0]);

switch ($pagina) {

    case 'listar_treinos_json':
        // Retorna JSON para o Modal montar os botões via JS
        header('Content-Type: application/json');
        require_once '../config/db_connect.php';
        
        $uid = $_SESSION['user_id'];
        $sql = "SELECT id, nome, nivel_plano, data_inicio FROM treinos WHERE aluno_id = :uid ORDER BY criado_em DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($result);
        exit; // Encerra aqui para não imprimir HTML extra
    break;

    case 'dashboard':
        require_once '../config/db_connect.php';
        
        // --- LÓGICA DE DADOS ---
        $start_week = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_week   = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        
        // 1. Frequência
        $stmt_w = $pdo->prepare("SELECT COUNT(DISTINCT data_treino) FROM treino_historico WHERE aluno_id = ? AND data_treino BETWEEN ? AND ?");
        $stmt_w->execute([$aluno_id, $start_week, $end_week]);
        $treinos_semana = $stmt_w->fetchColumn();

        // 2. Volume (Tonelagem)
        $stmt_vol = $pdo->prepare("SELECT SUM(carga_kg * reps_realizadas) FROM treino_historico WHERE aluno_id = ? AND data_treino BETWEEN ? AND ?");
        $stmt_vol->execute([$aluno_id, $start_week, $end_week]);
        $volume = $stmt_vol->fetchColumn() ?: 0;
        $vol_fmt = ($volume > 1000) ? number_format($volume/1000, 1).'k' : $volume;

        // 3. Streak (Ofensiva)
        $streak = 0;
        for ($i = 0; $i < 30; $i++) {
            $check = date('Y-m-d', strtotime("-$i days"));
            $stmt_chk = $pdo->prepare("SELECT id FROM treino_historico WHERE aluno_id = ? AND DATE(data_treino) = ? LIMIT 1");
            $stmt_chk->execute([$aluno_id, $check]);
            if ($stmt_chk->fetch()) $streak++;
            else if ($i > 0) break; 
        }

        // --- 4. LÓGICA DO "TREINO DE HOJE" (Igual ao realizar_treino) ---
        $hoje_dia_num = date('N'); // 1 (Seg) a 7 (Dom)
        $stmt_ativo = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY criado_em DESC LIMIT 1");
        $stmt_ativo->execute([$aluno_id]);
        $treino_ativo = $stmt_ativo->fetch(PDO::FETCH_ASSOC);

        $card_titulo = "SEM TREINO";
        $card_subtitulo = "Nenhum plano ativo";
        $card_badge = "Off";
        $card_letra = "-";
        $is_rest_day = false;
        $divisao_hoje_id = ''; // Se vazio, vai pra lista geral

        if ($treino_ativo) {
            $dias_treino = json_decode($treino_ativo['dias_semana']);
            if (!is_array($dias_treino)) $dias_treino = [];

            // Verifica se hoje é dia de treino
            if (in_array($hoje_dia_num, $dias_treino)) {
                // É dia de treino! Calcula qual divisão (A, B, C...)
                $stmt_divs = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
                $stmt_divs->execute([$treino_ativo['id']]);
                $divisoes = $stmt_divs->fetchAll(PDO::FETCH_ASSOC);

                if (count($divisoes) > 0) {
                    $indice_hoje = array_search($hoje_dia_num, $dias_treino);
                    $indice_divisao = $indice_hoje % count($divisoes);
                    $div_hoje = $divisoes[$indice_divisao];

                    $card_letra = $div_hoje['letra'];
                    $card_titulo = "Treino " . $div_hoje['letra'];
                    $card_subtitulo = $div_hoje['nome'] ? $div_hoje['nome'] : 'Toque para iniciar';
                    $divisao_hoje_id = '&divisao_id=' . $div_hoje['id']; // Parâmetro para abrir direto
                    
                    // Pega a fase da periodização se houver
                    if ($treino_ativo['nivel_plano'] !== 'basico') {
                        $stmt_per = $pdo->prepare("SELECT id FROM periodizacoes WHERE treino_id = ?");
                        $stmt_per->execute([$treino_ativo['id']]);
                        $pid = $stmt_per->fetchColumn();
                        if($pid) {
                            $hoje_date = date('Y-m-d');
                            $stmt_m = $pdo->prepare("SELECT nome_fase FROM microciclos WHERE periodizacao_id = ? AND data_inicio_semana <= ? AND data_fim_semana >= ? LIMIT 1");
                            $stmt_m->execute([$pid, $hoje_date, $hoje_date]);
                            $m = $stmt_m->fetch(PDO::FETCH_ASSOC);
                            if($m) $card_badge = $m['nome_fase'];
                            else $card_badge = "Periodizado";
                        } else {
                            $card_badge = "Geral";
                        }
                    } else {
                        $card_badge = "Básico";
                    }
                }
            } else {
                // Hoje NÃO é dia de treino (Descanso)
                $is_rest_day = true;
                $card_titulo = "Descanso";
                $card_subtitulo = "Recuperação ativa";
                $card_letra = "<i class='fa-solid fa-bed' style='font-size:0.6em; color:black;'></i>";
                $card_badge = "Off";
            }
        }

        // --- RENDERIZAÇÃO ---
        echo '<section id="dashboard" class="fade-in">
                
                <div class="clean-header-bg">
                    <div class="header-content-clean">
                        <div class="header-texts">
                            <span class="greeting-sub">Painel do Atleta</span>
                            <h1 class="greeting-main">Olá, <span style="color:var(--gold)">'.$primeiro_nome.'</span></h1>
                        </div>
                        <div class="header-avatar">
                            <img src="'.$_SESSION['user_foto'].'" onerror="this.src=\'assets/img/user-default.png\'">
                        </div>
                    </div>
                    
                    <div class="status-bar-float">
                        <div class="sb-item">
                            <i class="fa-solid fa-fire sb-icon fire"></i>
                            <div class="sb-info">
                                <strong>'.$streak.'</strong>
                                <span>Dias seguidos</span>
                            </div>
                        </div>
                        <div class="sb-divider"></div>
                        <div class="sb-item">
                            <i class="fa-solid fa-weight-hanging sb-icon"></i>
                            <div class="sb-info">
                                <strong>'.$vol_fmt.' kg</strong>
                                <span>Volume Semanal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-content-padded">
                    
                    <h3 class="section-label">HOJE</h3>
                    
                    <div class="today-card ' . ($is_rest_day ? 'rest-day-card' : '') . '" onclick="carregarConteudo(\'realizar_treino'.$divisao_hoje_id.'\')">
                        <div class="today-left">
                            <span class="today-letter" style="' . ($is_rest_day ? 'background:rgba(255,255,255,0.1); color:#888;' : '') . '">
                                '.$card_letra.'
                            </span>
                            <div class="today-info">
                                <span class="badge-phase" style="' . ($is_rest_day ? 'background:#444; color:#aaa;' : '') . '">'.$card_badge.'</span>
                                <h2 style="' . ($is_rest_day ? : '') . '">'.$card_titulo.'</h2>
                                <p>'.$card_subtitulo.'</p>
                            </div>
                        </div>
                        <div class="today-action">
                            <i class="fa-solid ' . ($is_rest_day ? 'fa-list-ul' : 'fa-play') . '"></i>
                        </div>
                    </div>';

                    if ($is_rest_day) {
                        echo '<p style="text-align:center; font-size:0.8rem; color:#666; margin-top:5px; margin-bottom:20px;">
                                <i class="fa-solid fa-info-circle"></i> Toque no card acima se quiser treinar mesmo assim.
                              </p>';
                    }

        echo '      <h3 class="section-label">ACESSO RÁPIDO</h3>
                    <div class="quick-grid">
                        <div class="quick-card" onclick="carregarConteudo(\'historico\')">
                            <div class="qc-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <span>Histórico</span>
                        </div>
                        <div class="quick-card" onclick="carregarConteudo(\'treinos\')">
                            <div class="qc-icon"><i class="fa-solid fa-dumbbell"></i></div>
                            <span>Minha Ficha</span>
                        </div>
                        <div class="quick-card" onclick="carregarConteudo(\'avaliacoes\')">
                            <div class="qc-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                            <span>Avaliação</span>
                        </div>
                        <div class="quick-card" onclick="carregarConteudo(\'perfil\')">
                            <div class="qc-icon"><i class="fa-solid fa-user-gear"></i></div>
                            <span>Perfil</span>
                        </div>
                    </div>

                    <h3 class="section-label">CONSTÂNCIA</h3>
                    <div class="frequency-strip">
                        <div class="freq-header">
                            <span>Esta Semana</span>
                            <strong>'.$treinos_semana.'/5</strong>
                        </div>
                        <div class="week-pills">';
                            $dias = ['S','T','Q','Q','S','S','D'];
                            $hoje_n = date('N');
                            
                            $stmt_d = $pdo->prepare("SELECT DATE(data_treino) FROM treino_historico WHERE aluno_id = ? AND data_treino BETWEEN ? AND ?");
                            $stmt_d->execute([$aluno_id, $start_week, $end_week]);
                            $dias_feitos = $stmt_d->fetchAll(PDO::FETCH_COLUMN);

                            for($i=1; $i<=7; $i++){
                                $dt = date('Y-m-d', strtotime('monday this week +'.($i-1).' days'));
                                $done = in_array($dt, $dias_feitos) ? 'done' : '';
                                $curr = ($i == $hoje_n) ? 'current' : '';
                                echo '<div class="day-pill '.$done.' '.$curr.'">'.$dias[$i-1].'</div>';
                            }
        echo '          </div>
                    </div>

                </div>
              </section>';
        break;

    case 'realizar_treino':
        // 1. Busca o Treino Ativo
        $hoje = date('Y-m-d');
        $sql = "SELECT * FROM treinos WHERE aluno_id = :uid ORDER BY criado_em DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $aluno_id]);
        $treino_ativo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$treino_ativo) {
            echo '<section class="empty-state"><h2>Sem treino ativo</h2></section>';
            break;
        }

        // 2. Lógica de Seleção Automática do Dia
        if (!$divisao_req) {
            $dia_semana_hoje = date('N'); 
            $dias_treino = json_decode($treino_ativo['dias_semana']); 
            if (!is_array($dias_treino)) $dias_treino = [];

            $stmt_div = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
            $stmt_div->execute([$treino_ativo['id']]);
            $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);
            
            $divisao_sugerida = null;
            $indice_hoje = array_search($dia_semana_hoje, $dias_treino);
            $qtd_divisoes = count($divisoes);
            
            if ($indice_hoje !== false && $qtd_divisoes > 0) {
                $indice_divisao = $indice_hoje % $qtd_divisoes;
                $divisao_sugerida = $divisoes[$indice_divisao];
                
                echo '<section class="fade-in" style="padding-top:20px;">
                        <h2 class="workout-title" style="text-align:center; font-size:1.2rem;">HOJE É DIA DE:</h2>
                        <div style="text-align:center; margin: 30px 0;">
                             <h1 style="font-size:5rem; color:var(--gold); margin:0;">'.$divisao_sugerida['letra'].'</h1>
                             <p style="color:#888;">'.$divisao_sugerida['nome'].'</p>
                        </div>
                        <button class="btn-start-workout" onclick="carregarConteudo(\'realizar_treino&divisao_id='.$divisao_sugerida['id'].'\')">
                            <i class="fa-solid fa-check"></i> CONFIRMAR
                        </button>
                        <p style="text-align:center; color:#666; margin-top:20px; font-size:0.9rem;">Ou escolha outro:</p>
                        <div class="workout-selection-grid">';
                            foreach($divisoes as $d) {
                                if($d['id'] != $divisao_sugerida['id']) {
                                    echo '<button class="select-workout-btn" onclick="carregarConteudo(\'realizar_treino&divisao_id='.$d['id'].'\')">'.$d['letra'].'</button>';
                                }
                            }
                echo   '</div></section>';
                break; 
            } else {
                echo '<section class="fade-in">
                        <h2 class="workout-title">QUAL O TREINO DE HOJE?</h2>
                        <div class="workout-selection-grid">';
                        if ($qtd_divisoes > 0) {
                            foreach($divisoes as $d) {
                                echo '<button class="select-workout-btn" onclick="carregarConteudo(\'realizar_treino&divisao_id='.$d['id'].'\')">'.$d['letra'].'</button>';
                            }
                        } else {
                            echo '<p style="color:#888;">Nenhuma divisão encontrada.</p>';
                        }
                echo   '</div></section>';
                break;
            }
        }

        // 3. EXIBIÇÃO DO TREINO
        $divisao_id = $divisao_req;
        
        $stmt_d = $pdo->prepare("SELECT * FROM treino_divisoes WHERE id = ?");
        $stmt_d->execute([$divisao_id]);
        $div_atual = $stmt_d->fetch(PDO::FETCH_ASSOC);

        if (!$div_atual) { echo '<p>Erro: Divisão não encontrada.</p>'; break; }

        $stmt_ex = $pdo->prepare("SELECT * FROM exercicios WHERE divisao_id = ? ORDER BY ordem ASC");
        $stmt_ex->execute([$divisao_id]);
        $exercicios = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);

        // Periodização
        $micro_atual = null;
        if ($treino_ativo['nivel_plano'] !== 'basico') {
             $stmt_per = $pdo->prepare("SELECT id FROM periodizacoes WHERE treino_id = ?");
             $stmt_per->execute([$treino_ativo['id']]);
             $pid = $stmt_per->fetchColumn();
             if($pid) {
                 $stmt_m = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? AND data_inicio_semana <= ? AND data_fim_semana >= ? LIMIT 1");
                 $stmt_m->execute([$pid, $hoje, $hoje]);
                 $micro_atual = $stmt_m->fetch(PDO::FETCH_ASSOC);
                 if(!$micro_atual) {
                     $stmt_m = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero DESC LIMIT 1");
                     $stmt_m->execute([$pid]);
                     $micro_atual = $stmt_m->fetch(PDO::FETCH_ASSOC);
                 }
             }
        }

        $nome_fase = $micro_atual ? 'Fase: '.$micro_atual['nome_fase'] : 'Treino Livre';

        echo '<form action="actions/treino_registrar.php" method="POST" id="form-execucao">
                <input type="hidden" name="treino_id" value="'.$treino_ativo['id'].'">
                <input type="hidden" name="divisao_id" value="'.$divisao_id.'">

                <div class="execution-header">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:0 15px;">
                        <h2 style="color:#fff; margin:0; font-size:1.2rem;">TREINO '.$div_atual['letra'].'</h2>
                        <button type="button" onclick="carregarConteudo(\'realizar_treino\')" style="background:none; border:none; color:#888;">Trocar</button>
                    </div>
                    <p style="padding:0 15px; color:#666; font-size:0.8rem; margin-top:5px;">'.$nome_fase.'</p>
                </div>

                <div style="text-align: center; margin-bottom: 20px;">
                    <button type="button" class="btn-gold" style="background: transparent; border: 1px solid var(--gold); color: var(--gold); padding: 8px 20px; font-size: 0.8rem; border-radius: 50px;" onclick="mostrarTimer()">
                        <i class="fa-solid fa-stopwatch"></i> ABRIR CRONÔMETRO
                    </button>
                </div>

                <div style="padding-bottom: 160px;">'; 

        if (count($exercicios) > 0) {
            foreach ($exercicios as $ex) {
                $stmt_s = $pdo->prepare("SELECT * FROM series WHERE exercicio_id = ?");
                $stmt_s->execute([$ex['id']]);
                $series = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

                // --- BUSCA HISTÓRICO AVANÇADA (POR SÉRIE E ORDEM) ---
                // 1. Descobre qual foi a ÚLTIMA data que esse exercício foi treinado
                $stmt_last_date = $pdo->prepare("SELECT MAX(data_treino) FROM treino_historico WHERE aluno_id = ? AND exercicio_id = ?");
                $stmt_last_date->execute([$aluno_id, $ex['id']]);
                $ultima_data = $stmt_last_date->fetchColumn();

                // 2. Se achou data, busca TODOS os registros desse dia para mapear
                $historico_map = []; // Vai guardar: [serie_id][numero_ordem] => dados
                if ($ultima_data) {
                    // Tenta buscar usando serie_id (novo padrão)
                    $stmt_h = $pdo->prepare("SELECT serie_id, numero_serie, serie_numero, carga_kg, reps_realizadas 
                                             FROM treino_historico 
                                             WHERE aluno_id = ? AND exercicio_id = ? AND data_treino = ?");
                    $stmt_h->execute([$aluno_id, $ex['id'], $ultima_data]);
                    $regs = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($regs as $r) {
                        // Se tiver serie_id (novo), usa ele. Se não, tenta usar serie_numero (legado) como ID
                        $s_key = $r['serie_id'] ? $r['serie_id'] : $r['serie_numero'];
                        $n_key = $r['numero_serie'] ? $r['numero_serie'] : 1;
                        
                        $historico_map[$s_key][$n_key] = $r;
                    }
                }

                $video_html = $ex['video_url'] ? '<a href="'.$ex['video_url'].'" target="_blank" class="exec-video"><i class="fa-solid fa-circle-play"></i></a>' : '';

                echo '
                <div class="exec-card">
                    <div class="exec-header">
                        <span class="exec-title">'.$ex['nome_exercicio'].'</span>
                        '.$video_html.'
                    </div>

                    <div class="set-row-header">
                        <span>SÉRIE</span>
                        <span>META</span>
                        <span>CARGA (KG)</span>
                        <span>REPS</span>
                    </div>';

                    if (count($series) > 0) {
                        foreach ($series as $s) {
                            $meta_reps = $s['reps_fixas'];
                            $meta_desc = $s['descanso_fixo'];

                            if ($s['categoria'] === 'warmup') { $meta_desc = '30s'; }
                            elseif ($s['categoria'] === 'feeder') { $meta_desc = '60s'; }
                            elseif ($micro_atual) {
                                if ($ex['tipo_mecanica'] == 'composto') {
                                    if($micro_atual['reps_compostos']) $meta_reps = $micro_atual['reps_compostos'];
                                    if($micro_atual['descanso_compostos']) $meta_desc = $micro_atual['descanso_compostos'].'s';
                                } else {
                                    if($micro_atual['reps_isoladores']) $meta_reps = $micro_atual['reps_isoladores'];
                                    if($micro_atual['descanso_isoladores']) $meta_desc = $micro_atual['descanso_isoladores'].'s';
                                }
                            }
                            if(!$meta_reps) $meta_reps = "-";

                            $qtd_series = (int)$s['quantidade'];
                            if ($qtd_series < 1) $qtd_series = 1;

                            // LOOP DE INPUTS (Gera 1 linha para cada repetição da série)
                            for ($i = 1; $i <= $qtd_series; $i++) {
                                
                                // Tenta encontrar o histórico específico desta repetição ($i) desta série ($s['id'])
                                $ph_carga = '-';
                                $ph_reps = '-';
                                
                                if (isset($historico_map[$s['id']][$i])) {
                                    $dado_ant = $historico_map[$s['id']][$i];
                                    $ph_carga = ($dado_ant['carga_kg'] * 1); // *1 remove zeros extras decimais
                                    $ph_reps  = $dado_ant['reps_realizadas'];
                                } elseif (isset($historico_map[$s['id']][1])) {
                                    // Fallback: Se não achou a repetição 2, tenta mostrar a 1 só pra ter referência
                                    $dado_ant = $historico_map[$s['id']][1];
                                    $ph_carga = ($dado_ant['carga_kg'] * 1);
                                    $ph_reps  = $dado_ant['reps_realizadas'];
                                }

                                $input_name_carga = "carga[".$s['id']."][".$i."]";
                                $input_name_reps = "reps[".$s['id']."][".$i."]";

                                $label_serie = strtoupper($s['categoria']);
                                $indicador_num = '1';
                                
                                if ($qtd_series > 1) {
                                    $indicador_num = '#'.$i;
                                    $label_serie .= " <small style='font-size:0.6rem; color:#888;'>(".$i."/".$qtd_series.")</small>";
                                }

                                echo '
                                <div class="set-row-input '.$s['categoria'].'" style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <div class="set-num">
                                        <span style="font-size:1.1rem;">'.$indicador_num.'</span>
                                        <span class="set-type-label">'.$label_serie.'</span>
                                        <div style="font-size:0.6rem; color:#666;">'.$meta_desc.'</div>
                                    </div>
                                    
                                    <div style="text-align:center;">
                                        <span style="color:#fff; font-size:0.9rem; font-weight:bold;">'.$meta_reps.'</span>
                                        <span style="display:block; font-size:0.6rem; color:#aaa;">ALVO</span>
                                    </div>

                                    <div>
                                        <input type="number" step="0.5" name="'.$input_name_carga.'" class="input-exec" placeholder="Ant: '.$ph_carga.'" inputmode="decimal">
                                    </div>

                                    <div style="display:flex; align-items:center; gap:5px;">
                                        <input type="number" name="'.$input_name_reps.'" class="input-exec" placeholder="Ant: '.$ph_reps.'" inputmode="numeric">
                                    </div>
                                </div>';
                            }
                        }
                    } else {
                        echo '<p style="color:#666; padding:10px;">Sem séries cadastradas.</p>';
                    }

                echo '</div>'; // Fim exec-card
            }
        } else {
            echo '<p style="text-align:center; margin-top:20px; color:#888;">Nenhum exercício encontrado nesta divisão.</p>';
        }

        echo '  </div> 

                <button type="submit" class="btn-finish">
                    <i class="fa-solid fa-check"></i> FINALIZAR TREINO
                </button>
              </form>';
        break;



        
    case 'treinos':
        require_once '../config/db_connect.php'; // Garante conexão se não houver
        $aluno_id = $_SESSION['user_id'];
        $hoje = date('Y-m-d');

        // A. BUSCA TODOS OS TREINOS (Para o Select)
        $sql = "SELECT id, nome, data_inicio, data_fim, nivel_plano FROM treinos WHERE aluno_id = :uid ORDER BY criado_em DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $aluno_id]);
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($lista)) {
            echo '<section class="empty-state">
                    <i class="fa-solid fa-dumbbell"></i>
                    <h2>Sem treinos ativos</h2>
                  </section>';
            break;
        }

        // B. DEFINE TREINO ATUAL
        $treino = $lista[0];
        if ($treino_req) {
            foreach($lista as $t) {
                if ($t['id'] == $treino_req) { $treino = $t; break; }
            }
        }

        // C. BUSCA DADOS DA PERIODIZAÇÃO
        $micro_atual = null;
        $micros = [];
        $meta_treino = "";

        if ($treino['nivel_plano'] !== 'basico') {
            $stmt = $pdo->prepare("SELECT id, objetivo_macro FROM periodizacoes WHERE treino_id = ?");
            $stmt->execute([$treino['id']]);
            $per = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($per) {
                $meta_treino = $per['objetivo_macro'];
                
                // Busca todos os campos, incluindo os novos descansos
                $stmt = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero ASC");
                $stmt->execute([$per['id']]);
                $micros = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Seleção do Microciclo (Clique > Data > Primeiro)
                if ($micro_req) {
                    foreach ($micros as $m) {
                        if ($m['id'] == $micro_req) { $micro_atual = $m; break; }
                    }
                }
                
                if (!$micro_atual) {
                    foreach ($micros as $m) {
                        if ($hoje >= $m['data_inicio_semana'] && $hoje <= $m['data_fim_semana']) {
                            $micro_atual = $m;
                            break;
                        }
                    }
                }

                if (!$micro_atual && !empty($micros)) $micro_atual = $micros[0];
            }
        }

        // D. BUSCA DIVISÕES
        $stmt = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
        $stmt->execute([$treino['id']]);
        $divisoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- RENDERIZAÇÃO ---
        echo '<section id="meu-treino" class="fade-in">';
        

        // 2. Header
        echo '<div class="workout-header-main">
                <h2 class="workout-title">'.$treino['nome'].'</h2>
                <div class="meta-tags">
                    <span class="tag">'.strtoupper($treino['nivel_plano']).'</span>
                    '.($meta_treino ? '<span class="tag outline">'.$meta_treino.'</span>' : '').'
                </div>
              </div>';

        // 3. Timeline
        if (!empty($micros)) {
            echo '<div class="timeline-container">';
            foreach ($micros as $m) {
                $active = ($micro_atual && $m['id'] == $micro_atual['id']) ? 'active' : '';
                $data = date('d/m', strtotime($m['data_inicio_semana']));
                
                echo '<div class="week-card '.$active.'" onclick="carregarConteudo(\'treinos&treino_id='.$treino['id'].'&micro_id='.$m['id'].'\')">
                        <span class="week-label">SEM '.$m['semana_numero'].'</span>
                        <span class="week-date">'.$data.'</span>
                      </div>';
            }
            echo '</div>';

            // VISUALIZAÇÃO DE FOCO ATUALIZADA (COMPOSTOS VS ISOLADORES)
            if ($micro_atual) {
                // Prepara valores para exibição (fallback para '-')
                $reps_comp = $micro_atual['reps_compostos'] ?: '-';
                $desc_comp = $micro_atual['descanso_compostos'] ? $micro_atual['descanso_compostos'].'s' : '-';
                
                $reps_iso = $micro_atual['reps_isoladores'] ?: '-';
                $desc_iso = $micro_atual['descanso_isoladores'] ? $micro_atual['descanso_isoladores'].'s' : '-';

                echo '<div class="week-focus-box">
                        <div class="focus-header">
                            <h4><i class="fa-solid fa-flag"></i> FASE: '.strtoupper($micro_atual['nome_fase']).'</h4>
                        </div>
                        
                        <div class="focus-grid">
                            <div class="focus-item">
                                <small style="color:var(--gold);">COMPOSTOS</small>
                                <strong>'.$reps_comp.'</strong>
                                <span style="display:block; font-size:0.75rem; color:#ccc; margin-top:4px;">
                                    <i class="fa-solid fa-clock"></i> '.$desc_comp.'
                                </span>
                            </div>
                            <div class="focus-item">
                                <small style="color:var(--gold);">ISOLADORES</small>
                                <strong>'.$reps_iso.'</strong>
                                <span style="display:block; font-size:0.75rem; color:#ccc; margin-top:4px;">
                                    <i class="fa-solid fa-clock"></i> '.$desc_iso.'
                                </span>
                            </div>
                        </div>
                        
                        '.($micro_atual['foco_comentario'] ? '<p class="focus-obs">"'.$micro_atual['foco_comentario'].'"</p>' : '').'
                      </div>';
            }
        }

        // 4. Abas e Exercícios
        echo '<div class="division-tabs">';
        $first = true;
        foreach ($divisoes as $d) {
            $act = $first ? 'active' : '';
            echo '<button class="tab-btn '.$act.'" onclick="abrirTreino(event, \'div_'.$d['id'].'\')">TREINO '.$d['letra'].'</button>';
            $first = false;
        }
        echo '</div>';

        $first = true;
        foreach ($divisoes as $d) {
            $display = $first ? 'block' : 'none';
            
            $stmt = $pdo->prepare("SELECT * FROM exercicios WHERE divisao_id = ? ORDER BY ordem ASC");
            $stmt->execute([$d['id']]);
            $exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<div id="div_'.$d['id'].'" class="treino-content" style="display:'.$display.'">';
            
            if ($exercicios) {
                foreach ($exercicios as $ex) {
                    $stmt = $pdo->prepare("SELECT * FROM series WHERE exercicio_id = ?");
                    $stmt->execute([$ex['id']]);
                    $series = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo '<div class="exercise-card">
                            <div class="ex-header">
                                <div>
                                    <span class="ex-name">'.$ex['nome_exercicio'].'</span>
                                    <span class="ex-type">'.strtoupper($ex['tipo_mecanica']).'</span>
                                </div>
                                '.($ex['video_url'] ? '<a href="'.$ex['video_url'].'" target="_blank" class="btn-video"><i class="fa-solid fa-play"></i></a>' : '').'
                            </div>
                            <div class="ex-body">
                                '.($ex['observacao_exercicio'] ? '<div class="ex-note"><i class="fa-solid fa-info-circle"></i> '.$ex['observacao_exercicio'].'</div>' : '').'
                                <div class="sets-grid">';
                                
                                foreach ($series as $s) {
                                    // 1. Valores Iniciais (Padrão cadastrado)
                                    $reps = $s['reps_fixas'];
                                    $desc = $s['descanso_fixo'];

                                    // 2. Lógica de Categorias Especiais (Override Fixo)
                                    if ($s['categoria'] === 'warmup') {
                                        $desc = '30s'; // Fixo para Aquecimento
                                    } 
                                    elseif ($s['categoria'] === 'feeder') {
                                        $desc = '60s'; // Fixo para Feeder
                                    } 
                                    else {
                                        // 3. Lógica de Periodização (Séries de Trabalho)
                                        // Só aplica se NÃO for Warmup/Feeder
                                        if ($micro_atual) {
                                            
                                            // Se for COMPOSTO
                                            if ($ex['tipo_mecanica'] == 'composto') {
                                                if (!empty($micro_atual['reps_compostos'])) {
                                                    $reps = $micro_atual['reps_compostos'];
                                                }
                                                // Usa o novo campo descanso_compostos
                                                if (!empty($micro_atual['descanso_compostos'])) {
                                                    $desc = $micro_atual['descanso_compostos'].'s';
                                                }
                                            } 
                                            // Se for ISOLADOR
                                            elseif ($ex['tipo_mecanica'] == 'isolador') {
                                                if (!empty($micro_atual['reps_isoladores'])) {
                                                    $reps = $micro_atual['reps_isoladores'];
                                                }
                                                // Usa o novo campo descanso_isoladores
                                                if (!empty($micro_atual['descanso_isoladores'])) {
                                                    $desc = $micro_atual['descanso_isoladores'].'s';
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Fallbacks visuais
                                    if(empty($reps)) $reps = "Falha";
                                    if(empty($desc)) $desc = "-";

                                    echo '<div class="set-item '.$s['categoria'].'">
                                            <div class="set-top">'.$s['quantidade'].'x '.$s['categoria'].'</div>
                                            <div class="set-bottom">
                                                <span>'.$reps.'</span>
                                                <small>'.$desc.'</small>
                                            </div>
                                          </div>';
                                }
                    echo       '</div>
                            </div>
                          </div>';
                }
            } else {
                echo '<div class="empty-day">Descanso</div>';
            }
            echo '</div>';
            $first = false;
        }

        echo '</section>';
        break;

    
    case 'historico':
        require_once '../config/db_connect.php';
        $aluno_id = $_SESSION['user_id'];
        
        // Verifica se foi pedido o detalhe de uma data específica
        $data_ref = $_GET['data_ref'] ?? null;

        // --- MODO 1: DETALHES DO TREINO (QUANDO CLICA) ---
        if ($data_ref) {
            // 1. Infos Gerais
            $sql_info = "SELECT DISTINCT t.nome as nome_treino, td.letra 
                         FROM treino_historico th
                         JOIN treinos t ON th.treino_id = t.id
                         JOIN treino_divisoes td ON th.divisao_id = td.id
                         WHERE th.aluno_id = :uid AND th.data_treino = :dt";
            $stmt = $pdo->prepare($sql_info);
            $stmt->execute(['uid' => $aluno_id, 'dt' => $data_ref]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Busca Detalhes Completos (CORREÇÃO AQUI)
            // Usamos COALESCE para priorizar th.serie_id. Se for nulo, tenta th.serie_numero
            $sql_detalhes = "SELECT th.*, e.nome_exercicio, s.categoria 
                             FROM treino_historico th
                             JOIN exercicios e ON th.exercicio_id = e.id
                             LEFT JOIN series s ON COALESCE(th.serie_id, th.serie_numero) = s.id 
                             WHERE th.aluno_id = :uid AND th.data_treino = :dt
                             ORDER BY e.ordem ASC, th.id ASC";
                             
            $stmt = $pdo->prepare($sql_detalhes);
            $stmt->execute(['uid' => $aluno_id, 'dt' => $data_ref]);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. AGRUPAMENTO
            $treino_agrupado = [];
            foreach ($registros as $reg) {
                $id_ex = $reg['exercicio_id'];
                
                if (!isset($treino_agrupado[$id_ex])) {
                    $treino_agrupado[$id_ex] = [
                        'nome' => $reg['nome_exercicio'],
                        'series' => []
                    ];
                }
                $treino_agrupado[$id_ex]['series'][] = $reg;
            }

            // --- RENDERIZAÇÃO ---
            echo '<section class="fade-in">
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                        <button onclick="carregarConteudo(\'historico\')" style="background:none; border:none; color:#fff; font-size:1.2rem;">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <div>
                            <span style="color:#888; font-size:0.8rem; text-transform:uppercase;">Visualizando</span>
                            <h2 style="margin:0; color:#fff; font-size:1.2rem;">TREINO '.($info['letra'] ?? '?').'</h2>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; padding:15px; background:rgba(255,186,66,0.1); border-radius:8px; border:1px solid var(--gold); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:var(--gold); display:block;">'.($info['nome_treino'] ?? 'Treino').'</strong>
                            <span style="color:#ccc; font-size:0.8rem;">'.date('d/m/Y \à\s H:i', strtotime($data_ref)).'</span>
                        </div>
                        <i class="fa-solid fa-calendar-check" style="color:var(--gold); font-size:1.5rem;"></i>
                    </div>

                    <div class="history-details-list">';
                    
                    if (empty($treino_agrupado)) {
                        echo '<p style="text-align:center; color:#666;">Nenhum dado detalhado encontrado.</p>';
                    }

                    foreach ($treino_agrupado as $ex_id => $dados) {
                        echo '<div class="hist-exercise-group">
                                <div class="hist-ex-header">
                                    <i class="fa-solid fa-dumbbell"></i>
                                    <span>'.$dados['nome'].'</span>
                                </div>
                                
                                <table class="hist-sets-table">
                                    <thead>
                                        <tr>
                                            <th width="15%">#</th>
                                            <th width="35%">TIPO</th>
                                            <th width="25%">KG</th>
                                            <th width="25%">REPS</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                                    
                                    foreach ($dados['series'] as $serie) {
                                        // Categoria
                                        $cat = $serie['categoria'] ? strtolower($serie['categoria']) : 'work';
                                        
                                        // Ordem (Usa numero_serie se existir, senão usa contador manual)
                                        $num_ordem = $serie['numero_serie'] > 0 ? $serie['numero_serie'] : '-';

                                        echo '<tr>
                                                <td style="color:#666; font-weight:bold;">#'.$num_ordem.'</td>
                                                <td><span class="badge-set-type '.$cat.'">'.strtoupper($cat).'</span></td>
                                                <td style="color:#fff; font-weight:bold;">'.($serie['carga_kg']*1).'</td>
                                                <td style="color:#fff;">'.$serie['reps_realizadas'].'</td>
                                              </tr>';
                                    }

                        echo '      </tbody>
                                </table>
                              </div>';
                    }

            echo '  </div>
                  </section>';
            
            break;
        }

        // --- MODO 2: LISTA (IGUAL AO ANTERIOR) ---
        $sql_lista = "SELECT th.data_treino, t.nome as nome_treino, td.letra
                      FROM treino_historico th
                      LEFT JOIN treinos t ON th.treino_id = t.id
                      LEFT JOIN treino_divisoes td ON th.divisao_id = td.id
                      WHERE th.aluno_id = :uid
                      GROUP BY th.data_treino
                      ORDER BY th.data_treino DESC";
                      
        $stmt = $pdo->prepare($sql_lista);
        $stmt->execute(['uid' => $aluno_id]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<section id="historico-lista" class="fade-in">
                <header class="dash-header">
                    <h1>MEU <span class="highlight-text">HISTÓRICO</span></h1>
                </header>';

        if (empty($historico)) {
            echo '<div class="empty-state">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <h2>Nenhum treino registrado</h2>
                    <p>Realize seu primeiro treino para ver o histórico.</p>
                  </div>';
        } else {
            echo '<div class="history-list">';
            foreach ($historico as $h) {
                $data_obj = new DateTime($h['data_treino']);
                $dia = $data_obj->format('d');
                $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                $mes_txt = $meses[(int)$data_obj->format('m') - 1];
                $hora = $data_obj->format('H:i');
                $treino_nome = $h['nome_treino'] ? $h['nome_treino'] : 'Treino Arquivado';
                $letra = $h['letra'] ? $h['letra'] : '?';

                echo '<div class="history-card" onclick="carregarConteudo(\'historico&data_ref='.$h['data_treino'].'\')">
                        <div class="hist-date-box">
                            <span class="hist-day">'.$dia.'</span>
                            <span class="hist-month">'.$mes_txt.'</span>
                        </div>
                        <div class="hist-info">
                            <span class="hist-title">Treino '.$letra.'</span>
                            <span class="hist-sub">'.$treino_nome.' • '.$hora.'</span>
                        </div>
                        <i class="fa-solid fa-chevron-right hist-arrow"></i>
                      </div>';
            }
            echo '</div>';
        }
        echo '</section>';
        break;





    case 'perfil':
        require_once '../config/db_connect.php';
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $foto = $user['foto'] ? $user['foto'] : 'assets/img/user-default.png';

        echo '
            <section id="perfil-section">
                <header class="dash-header">
                    <h1>MEU <span class="highlight-text">PERFIL</span></h1>
                </header>

                <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
                    <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data" class="form-profile">
                        
                        <div class="profile-photo-section">
                            <div class="photo-wrapper">
                                <img src="'.$foto.'" alt="Foto Perfil" id="preview-img">
                                <label for="foto-upload" class="upload-btn-float">
                                    <i class="fa-solid fa-camera"></i>
                                </label>
                                <input type="file" name="foto" id="foto-upload" style="display: none;" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <p class="photo-hint">Toque na câmera para alterar</p>
                        </div>

                        <div class="input-grid">
                            <div>
                                <label class="input-label">Nome Completo</label>
                                <input type="text" name="nome" value="'.$user['nome'].'" class="input-field" required>
                            </div>
                            <div>
                                <label class="input-label">Telefone (WhatsApp)</label>
                                <input type="text" name="telefone" value="'.$user['telefone'].'" class="input-field">
                            </div>
                        </div>

                        <div>
                            <label class="input-label">E-mail de Acesso</label>
                            <input type="email" name="email" value="'.$user['email'].'" class="input-field" required>
                        </div>

                        <hr class="form-divider">

                        <div>
                            <h3 class="password-section-title">Segurança</h3>
                            <p class="password-section-desc">Preencha apenas se quiser alterar sua senha.</p>
                        </div>

                        <div class="input-grid">
                            <div>
                                <label class="input-label">Nova Senha</label>
                                <input type="password" name="nova_senha" class="input-field" placeholder="********">
                            </div>
                            <div>
                                <label class="input-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirma_senha" class="input-field" placeholder="********">
                            </div>
                        </div>

                        <div style="text-align: center; margin-top: 10px; margin-bottom: 20px;">
                            <button type="submit" class="btn-gold">SALVAR ALTERAÇÕES</button>
                        </div>
                    </form>
                </div>
            </section>

        ';
        break;

    case 'avaliacoes':
        require_once '../config/db_connect.php';
        $aluno_id = $_SESSION['user_id'];

        // --- DEFINIÇÃO DAS FUNÇÕES AUXILIARES (Closures) ---
        // Definimos como variáveis para garantir que funcionem dentro do switch
        $renderMeasure = function($label, $val) {
            if(!$val) return '';
            return '<div class="m-box"><span>'.$label.'</span><strong>'.$val.'</strong></div>';
        };

        $renderMeasureDouble = function($label, $val1, $val2) {
            if(!$val1 && !$val2) return '';
            return '<div class="m-box-double">
                        <span>'.$label.'</span>
                        <div class="vals">
                            <strong>'.($val1?:'-').'</strong>
                            <small>/</small>
                            <strong>'.($val2?:'-').'</strong>
                        </div>
                    </div>';
        };
        // -------------------------------------------------------

        // 1. BUSCA DADOS
        $sql = "SELECT * FROM avaliacoes WHERE aluno_id = ? ORDER BY data_avaliacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aluno_id]);
        $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<section id="avaliacoes-section" class="fade-in">
                
                <header class="dash-header-clean">
                    <div>
                        <h1 class="greeting-clean">Avaliação <span class="text-gold">Física</span></h1>
                        <p class="date-clean">Histórico de composição e medidas</p>
                    </div>
                    <button class="btn-gold-icon" onclick="abrirModalAvaliacao('.$aluno_id.')">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </header>';

        if (empty($avaliacoes)) {
            echo '<div class="empty-state-modern">
                    <div class="icon-pulse"><i class="fa-solid fa-weight-scale"></i></div>
                    <h2>Comece sua Jornada</h2>
                    <p>Registre sua primeira avaliação para acompanhar sua evolução.</p>
                  </div>';
        } else {

            // --- LISTA ACCORDION ---
            echo '<div class="eval-timeline-wrapper">';

            foreach ($avaliacoes as $av) {
                // Busca Arquivos (Fotos/Vídeos)
                $stmt_arq = $pdo->prepare("SELECT * FROM avaliacoes_arquivos WHERE avaliacao_id = ?");
                $stmt_arq->execute([$av['id']]);
                $arquivos = $stmt_arq->fetchAll(PDO::FETCH_ASSOC);

                $dia = date('d', strtotime($av['data_avaliacao']));
                $mes = date('M', strtotime($av['data_avaliacao']));
                $card_id = 'eval_card_' . $av['id'];

                echo '<div class="accordion-card" id="'.$card_id.'">
                        
                        <div class="accordion-header" onclick="toggleAccordion(\''.$card_id.'\')">
                            <div class="date-badge">
                                <span class="d-day">'.$dia.'</span>
                                <span class="d-month">'.$mes.'</span>
                            </div>
                            <div class="header-info">
                                <div class="info-main">
                                    <span class="weight-display">'.($av['peso_kg'] * 1).' <small>kg</small></span>
                                    '.($av['percentual_gordura'] ? '<span class="bf-tag">BF '.($av['percentual_gordura']*1).'%</span>' : '').'
                                </div>
                                <span class="info-sub">'.count($arquivos).' mídias anexadas</span>
                            </div>

                            <div class="accordion-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>

                        <div class="accordion-body" style="display: none;">
                            <div class="body-padding">
                                
                                <div class="stats-tiles">
                                    <div class="tile">
                                        <small>IMC</small>
                                        <strong>'.($av['imc'] ?: '-').'</strong>
                                    </div>
                                    <div class="tile">
                                        <small>M. MAGRA</small>
                                        <strong>'.($av['massa_magra_kg'] ? $av['massa_magra_kg'].'kg' : '-').'</strong>
                                    </div>
                                    <div class="tile">
                                        <small>M. GORDA</small>
                                        <strong>'.($av['massa_gorda_kg'] ? $av['massa_gorda_kg'].'kg' : '-').'</strong>
                                    </div>
                                </div>

                                ';
                                if (!empty($arquivos)) {
                                    echo '<div class="gallery-strip">
                                            <span class="strip-title"><i class="fa-solid fa-camera"></i> Fotos do Dia</span>
                                            <div class="strip-scroll">';
                                    
                                    foreach ($arquivos as $arq) {
                                        if ($arq['tipo'] == 'foto') {
                                            echo '<div class="strip-item"><img src="assets/uploads/'.$arq['caminho_ou_url'].'" onclick="window.open(this.src)"></div>';
                                        } else {
                                            echo '<a href="'.$arq['caminho_ou_url'].'" target="_blank" class="strip-item video-item"><i class="fa-solid fa-play"></i></a>';
                                        }
                                    }
                                    echo '  </div>
                                          </div>';
                                }

                                // 3. MEDIDAS DETALHADAS (Usando as variáveis $renderMeasure)
                                echo '<div class="measures-container">
                                        
                                        <div class="m-group">
                                            <span class="mg-label">TRONCO</span>
                                            <div class="mg-grid">
                                                '.$renderMeasure('Ombros', $av['ombro']).'
                                                '.$renderMeasure('Tórax', $av['torax_relaxado']).'
                                                '.$renderMeasure('Cintura', $av['cintura']).'
                                                '.$renderMeasure('Abdômen', $av['abdomen']).'
                                                '.$renderMeasure('Quadril', $av['quadril']).'
                                            </div>
                                        </div>

                                        <div class="m-group">
                                            <span class="mg-label">MEMBROS (D / E)</span>
                                            <div class="mg-grid-wide">
                                                '.$renderMeasureDouble('Braço (Rel)', $av['braco_dir_relaxado'], $av['braco_esq_relaxado']).'
                                                '.$renderMeasureDouble('Braço (Con)', $av['braco_dir_contraido'], $av['braco_esq_contraido']).'
                                                '.$renderMeasureDouble('Coxa', $av['coxa_dir'], $av['coxa_esq']).'
                                                '.$renderMeasureDouble('Panturrilha', $av['panturrilha_dir'], $av['panturrilha_esq']).'
                                            </div>
                                        </div>

                                      </div>';
                                
                                if($av['observacoes']) {
                                    echo '<div class="obs-box"><i class="fa-solid fa-quote-left"></i> '.$av['observacoes'].'</div>';
                                }

                                // Botão de Excluir (Admin tem poder)
                                echo '<div class="card-footer-actions" style="margin-top:30px; text-align:center; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;">
                                        <a href="actions/avaliacao_delete.php?id='.$av['id'].'" class="btn-danger-outline" onclick="return confirm(\'Apagar avaliação permanentemente?\');">
                                            <i class="fa-solid fa-trash-can"></i> Excluir Avaliação
                                        </a>
                                      </div>';

                echo '      </div> </div> </div> ';
            }
            echo '</div>'; 
        }
        echo '</section>';
        break;


    // --- TELA 2: MEU PROGRESSO (COM DELTAS E GRÁFICO FIX) ---
    case 'progresso':
        require_once '../config/db_connect.php';
        $aluno_id = $_SESSION['user_id'];

        // 1. BUSCA DADOS CRONOLÓGICOS (Antigo -> Novo) para o Gráfico
        $sql = "SELECT * FROM avaliacoes WHERE aluno_id = ? ORDER BY data_avaliacao ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aluno_id]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. PREPARA ARRAYS PARA O GRÁFICO
        $json_data = [
            'labels' => [],
            'peso' => [],
            'bf' => [],
            'magra' => [],
            'gorda' => []
        ];

        foreach ($historico as $h) {
            $dt = date('d/m/y', strtotime($h['data_avaliacao']));
            // Adiciona apenas se tiver peso, para evitar pontos vazios
            if ($h['peso_kg'] > 0) {
                $json_data['labels'][] = $dt;
                $json_data['peso'][] = (float)$h['peso_kg'];
                $json_data['bf'][] = (float)$h['percentual_gordura'];
                $json_data['magra'][] = (float)$h['massa_magra_kg'];
            }
        }
        $chart_config = htmlspecialchars(json_encode($json_data), ENT_QUOTES, 'UTF-8');

        // 3. PREPARA LISTA INVERTIDA PARA A TABELA (Novo -> Antigo)
        // Usamos array_reverse para mostrar o mais recente em cima
        $historico_reverso = array_reverse($historico);

        // 4. FUNÇÃO HELPER PARA RENDERIZAR VALOR + DELTA
        // $val: Valor atual, $idx: Índice atual no loop reverso, $key: Nome da coluna (ex: 'braco_dir')
        // $inverse: Se true, diminuir é bom (ex: cintura). Se false, aumentar é bom (ex: braço).
        $renderVal = function($historico_reverso, $idx, $key, $inverse = false) {
            $val = $historico_reverso[$idx][$key] ?? null;
            if (!$val) return '-';

            // Pega o valor da avaliação ANTERIOR (que no array reverso é o índice + 1)
            $prev = $historico_reverso[$idx + 1][$key] ?? null;
            
            $html = '<strong>'.$val.'</strong>';

            if ($prev) {
                $diff = $val - $prev;
                if ($diff != 0) {
                    $sinal = $diff > 0 ? '+' : '';
                    // Define cor: 
                    // Se inverse (Cintura): Diminuir (diff < 0) é Green. Aumentar é Red.
                    // Se normal (Braço): Aumentar (diff > 0) é Green. Diminuir é Red.
                    $isGood = $inverse ? ($diff < 0) : ($diff > 0);
                    $color = $isGood ? '#00e676' : '#ff1744'; // Verde Neon / Vermelho Neon
                    
                    $html .= ' <small style="color:'.$color.'; font-size:0.7em; font-weight:bold;">'.$sinal.number_format($diff, 1).'</small>';
                } else {
                    $html .= ' <small style="color:#666; font-size:0.7em;">=</small>';
                }
            }
            return $html;
        };

        // Renderização
        echo '<section id="progresso-view" class="fade-in">
                <input type="hidden" id="chart-master-data" value="'.$chart_config.'">

                <header class="dash-header-clean">
                    <div>
                        <h1 class="greeting-clean">Performance <span class="text-gold">Analytics</span></h1>
                        <p class="date-clean">Análise detalhada da sua evolução</p>
                    </div>
                </header>';

        if (count($historico) < 2) {
            echo '<div class="empty-state-modern">
                    <div class="icon-pulse"><i class="fa-solid fa-chart-line"></i></div>
                    <h2>Dados Insuficientes</h2>
                    <p>Registre pelo menos 2 avaliações para desbloquear a análise comparativa.</p>
                    <button class="btn-gold" style="margin-top:20px;" onclick="carregarConteudo(\'avaliacoes\')">REGISTRAR AGORA</button>
                  </div>';
        } else {
            
            // --- GRÁFICO MASTER ---
            // Adicionei height explícito no canvas-wrapper-master para garantir renderização
            echo '<div class="chart-master-container mb-large">
                    <div class="chart-controls">
                        <button class="chart-btn active" onclick="switchChart(\'peso\', this)">Peso</button>
                        <button class="chart-btn" onclick="switchChart(\'bf\', this)">% Gordura</button>
                        <button class="chart-btn" onclick="switchChart(\'magra\', this)">M. Magra</button>
                    </div>
                    <div class="canvas-wrapper-master" style="position: relative; height: 300px; width: 100%;">
                        <canvas id="masterChart"></canvas>
                    </div>
                    <img src="" onerror="setTimeout(initMasterChart, 300)" style="display:none;">
                  </div>';

            // --- TABELAS COMPARATIVAS ---
            echo '<div class="comparison-section">
                    <h3 class="section-title"><i class="fa-solid fa-ruler-horizontal"></i> Comparativo de Medidas</h3>
                    
                    <div class="comp-tabs">
                        <button class="tab-pill active" onclick="switchTable(\'tronco\', this)">Tronco</button>
                        <button class="tab-pill" onclick="switchTable(\'bracos\', this)">Braços</button>
                        <button class="tab-pill" onclick="switchTable(\'pernas\', this)">Pernas</button>
                    </div>

                    <div id="tab-tronco" class="table-container active">
                        <table class="comp-table">
                            <thead>
                                <tr>
                                    <th>DATA</th>
                                    <th>Ombro</th>
                                    <th>Tórax</th>
                                    <th>Cintura</th>
                                    <th>Abdômen</th>
                                    <th>Quadril</th>
                                </tr>
                            </thead>
                            <tbody>';
                            foreach($historico_reverso as $i => $h) {
                                echo '<tr>
                                        <td class="fixed-col">'.date('d/m/y', strtotime($h['data_avaliacao'])).'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'ombro').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'torax_relaxado').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'cintura', true).'</td> <td>'.$renderVal($historico_reverso, $i, 'abdomen', true).'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'quadril').'</td>
                                      </tr>';
                            }
            echo '          </tbody>
                        </table>
                    </div>

                    <div id="tab-bracos" class="table-container" style="display:none;">
                        <table class="comp-table">
                            <thead>
                                <tr>
                                    <th>DATA</th>
                                    <th>B. Dir (R)</th>
                                    <th>B. Esq (R)</th>
                                    <th>B. Dir (C)</th>
                                    <th>B. Esq (C)</th>
                                </tr>
                            </thead>
                            <tbody>';
                            foreach($historico_reverso as $i => $h) {
                                echo '<tr>
                                        <td class="fixed-col">'.date('d/m/y', strtotime($h['data_avaliacao'])).'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'braco_dir_relaxado').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'braco_esq_relaxado').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'braco_dir_contraido').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'braco_esq_contraido').'</td>
                                      </tr>';
                            }
            echo '          </tbody>
                        </table>
                    </div>

                    <div id="tab-pernas" class="table-container" style="display:none;">
                        <table class="comp-table">
                            <thead>
                                <tr>
                                    <th>DATA</th>
                                    <th>Coxa Dir</th>
                                    <th>Coxa Esq</th>
                                    <th>Pant. Dir</th>
                                    <th>Pant. Esq</th>
                                </tr>
                            </thead>
                            <tbody>';
                            foreach($historico_reverso as $i => $h) {
                                echo '<tr>
                                        <td class="fixed-col">'.date('d/m/y', strtotime($h['data_avaliacao'])).'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'coxa_dir').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'coxa_esq').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'panturrilha_dir').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'panturrilha_esq').'</td>
                                      </tr>';
                            }
            echo '          </tbody>
                        </table>
                    </div>

                  </div>';
        }
        
        echo '</section>';
        break;


    case 'dieta':
        require_once '../config/db_connect.php';
        $aluno_id = $_SESSION['user_id'];
        $hoje = date('Y-m-d');

        // 1. Busca a Dieta Ativa
        $stmt = $pdo->prepare("SELECT * FROM dietas WHERE aluno_id = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$aluno_id]);
        $dieta = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<section id="dieta-view" class="fade-in">';

        if (!$dieta) {
            echo '<div class="empty-state-modern">
                    <div class="icon-pulse"><i class="fa-solid fa-carrot"></i></div>
                    <h2>Nenhuma Dieta Ativa</h2>
                    <p>Seu treinador ainda não publicou seu plano alimentar.</p>
                  </div>';
        } else {
            // Cabeçalho da Dieta
            echo '<header class="dash-header-clean">
                    <div>
                        <h1 class="greeting-clean">Plano <span class="text-gold">Alimentar</span></h1>
                        <p class="date-clean">'.$dieta['titulo'].' • '.$dieta['objetivo'].'</p>
                    </div>
                  </header>

                  <div class="timeline-diet">';

            // 2. Busca Refeições
            $stmt_ref = $pdo->prepare("SELECT * FROM refeicoes WHERE dieta_id = ? ORDER BY ordem ASC");
            $stmt_ref->execute([$dieta['id']]);
            $refeicoes = $stmt_ref->fetchAll(PDO::FETCH_ASSOC);

            foreach ($refeicoes as $ref) {
                // Verifica se já comeu hoje
                $stmt_check = $pdo->prepare("SELECT id FROM dieta_registro WHERE aluno_id = ? AND refeicao_id = ? AND data_registro = ?");
                $stmt_check->execute([$aluno_id, $ref['id'], $hoje]);
                $checked = $stmt_check->fetch() ? 'checked' : '';
                $activeClass = $checked ? 'completed' : '';

                // Busca Itens da Refeição
                $stmt_itens = $pdo->prepare("SELECT * FROM itens_dieta WHERE refeicao_id = ? ORDER BY opcao_numero ASC");
                $stmt_itens->execute([$ref['id']]);
                $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

                // Agrupa por Opção (1, 2...)
                $opcoes = [];
                foreach($itens as $it) {
                    $opcoes[$it['opcao_numero']][] = $it;
                }

                $horario = date('H:i', strtotime($ref['horario']));

                echo '<div class="diet-card '.$activeClass.'" id="ref_'.$ref['id'].'">
                        
                        <div class="diet-status-bar"></div>

                        <div class="diet-content">
                            <div class="diet-header">
                                <span class="diet-time"><i class="fa-regular fa-clock"></i> '.$horario.'</span>
                                <h3 class="diet-title">'.$ref['nome'].'</h3>
                                
                                <button class="btn-check-meal '.$checked.'" onclick="toggleRefeicao('.$ref['id'].', this)">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>

                            <div class="diet-options-container">';
                                
                                foreach($opcoes as $num => $lista_itens) {
                                    $label = ($num == 1) ? 'Opção Principal' : 'Opção '.$num.' (Substituição)';
                                    $classeOpcao = ($num == 1) ? 'primary' : 'secondary';
                                    
                                    echo '<div class="diet-option-box '.$classeOpcao.'">
                                            <span class="opt-label">'.$label.'</span>';
                                            
                                            foreach($lista_itens as $alimento) {
                                                echo '<div class="food-item">
                                                        <i class="fa-solid fa-caret-right text-gold"></i>
                                                        <div>
                                                            <strong>'.$alimento['descricao'].'</strong>
                                                            '.($alimento['observacao'] ? '<small>'.$alimento['observacao'].'</small>' : '').'
                                                        </div>
                                                      </div>';
                                            }
                                    echo '</div>';
                                    
                                    // Se tiver mais opções, mostra um "OU"
                                    if ($num < count($opcoes)) {
                                        echo '<div class="diet-divider"><span>OU</span></div>';
                                    }
                                }

                echo '      </div>
                        </div>
                      </div>';
            }
            echo '</div>'; // Fim Timeline
        }
        echo '</section>';
        break;

    case 'gerar_pdf':
        require_once '../config/db_connect.php';
        $aluno_id = $_SESSION['user_id'];

        // 1. Busca o Plano Ativo
        $stmt = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY criado_em DESC LIMIT 1");
        $stmt->execute([$aluno_id]);
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plano) {
            echo '<section class="empty-state"><h2>Sem plano ativo</h2></section>';
            break;
        }

        // 2. Busca Divisões e Exercícios
        $stmt_div = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
        $stmt_div->execute([$plano['id']]);
        $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

        // ... (código anterior de busca do plano e divisões) ...

        // 1. Mapa de Dias da Semana
        $mapa_dias = [
            1 => 'Segunda-Feira',
            2 => 'Terça-Feira',
            3 => 'Quarta-Feira',
            4 => 'Quinta-Feira',
            5 => 'Sexta-Feira',
            6 => 'Sábado',
            7 => 'Domingo',
            0 => 'Domingo' // Garantia
        ];

        // 2. Decodifica os dias do banco (que estão como JSON "[1,3,5]")
        $dias_treino = [];
        if (!empty($plano['dias_semana'])) {
            $decoded = json_decode($plano['dias_semana'], true);
            if (is_array($decoded)) {
                $dias_treino = $decoded;
            }
        }

        // 3. Monta o array gigante de dados
        $dados_treinos = [];
        $total_divisoes = count($divisoes);

        // Percorre as divisões (A, B, C...) pelo índice numérico (0, 1, 2...)
        foreach ($divisoes as $index_div => $div) {
            
            // --- LÓGICA DE ASSOCIAÇÃO (A MESMA DO REALIZAR TREINO) ---
            // Descobre quais dias da semana caem nesta divisão
            $dias_desta_divisao = [];
            
            if ($total_divisoes > 0 && !empty($dias_treino)) {
                // Percorre todos os dias que o aluno treina (ex: Seg, Qua, Sex)
                foreach ($dias_treino as $k => $dia_num) {
                    // Se o resto da divisão bater com o índice atual, esse dia é deste treino
                    if (($k % $total_divisoes) == $index_div) {
                        if (isset($mapa_dias[$dia_num])) {
                            $dias_desta_divisao[] = $mapa_dias[$dia_num];
                        }
                    }
                }
            }

            // Cria a string final (ex: "Segunda-Feira / Sexta-Feira")
            // Se não tiver dia calculado, usa um fallback
            $dia_exibicao = !empty($dias_desta_divisao) ? implode(' / ', $dias_desta_divisao) : 'TREINO ' . $div['letra'];

            // Busca Exercícios e Séries
            $stmt_ex = $pdo->prepare("SELECT * FROM exercicios WHERE divisao_id = ? ORDER BY ordem ASC");
            $stmt_ex->execute([$div['id']]);
            $exercicios = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);

            foreach ($exercicios as &$ex) {
                $stmt_s = $pdo->prepare("SELECT * FROM series WHERE exercicio_id = ? ORDER BY id ASC");
                $stmt_s->execute([$ex['id']]);
                $ex['lista_series'] = $stmt_s->fetchAll(PDO::FETCH_ASSOC); 
            }
            
            $dados_treinos[$div['letra']] = [
                'nome' => $div['nome'],
                'letra' => $div['letra'],
                'dia_real' => $dia_exibicao, // <--- Aqui vai o dia certo (ex: Segunda-Feira)
                'exercicios' => $exercicios
            ];
        }
        
        $json_treinos = htmlspecialchars(json_encode($dados_treinos), ENT_QUOTES, 'UTF-8');

        echo '<section id="area-relatorios" class="fade-in">
                
                <header class="dash-header-clean">
                    <div>
                        <h1 class="greeting-clean">Gerador de <span class="text-gold">Fichas</span></h1>
                        <p class="date-clean">Plano Atual: <strong>'.$plano['nome'].'</strong></p>
                    </div>
                </header>

                <input type="hidden" id="json-dados-treinos" value="'.$json_treinos.'">
                <input type="hidden" id="plano-nome-atual" value="'.$plano['nome'].'">

                <div class="pdf-action-card" onclick="document.getElementById(\'modalPDFConfig\').style.display=\'flex\'">
                    <div class="pac-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div class="pac-info">
                        <h3>Ficha de Treino Completa</h3>
                        <p>Visualização em blocos com dias da semana e tipos de série.</p>
                    </div>
                    <div class="pac-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </div>

                <div id="modalPDFConfig" class="modal-overlay" style="display:none;">
                    <div class="modal-content-premium" style="max-width: 450px;">
                        
                        <h3 class="modal-title">
                            <i class="fa-solid fa-sliders"></i> PERSONALIZAR FICHA
                        </h3>
                        
                        <div style="text-align:left; margin-bottom:15px;">
                            <label class="input-label">Nome no Relatório</label>
                            <input type="text" id="pdf_aluno_nome" class="modal-input" value="'.$_SESSION['user_nome'].'">
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                            
                            <div>
                                <label class="input-label">Tema</label>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <input type="color" id="pdf_theme_color" value="#000000" style="width:40px; height:40px; border:none; border-radius:5px; cursor:pointer;">
                                    <span style="font-size:0.8rem; color:#888;">Cabeçalhos</span>
                                </div>
                            </div>

                            <div>
                                <label class="input-label">Fundo</label>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <input type="color" id="pdf_bg_color" value="#000000ff" style="width:40px; height:40px; border:none; border-radius:5px; cursor:pointer;">
                                    <span style="font-size:0.8rem; color:#888;">Folha</span>
                                </div>
                            </div>

                            <div>
                                <label class="input-label">Bordas</label>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <input type="color" id="pdf_border_color" value="#000000" style="width:40px; height:40px; border:none; border-radius:5px; cursor:pointer;">
                                    <span style="font-size:0.8rem; color:#888;">Linhas</span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button class="btn-gold" onclick="gerarFichaCompleta()" style="flex: 2; display:flex; align-items:center; justify-content:center; gap:8px;">
                                <i class="fa-solid fa-file-pdf"></i> BAIXAR PDF
                            </button>
                            
                            <button type="button" class="btn-outline" onclick="debugPreviewPDF()" style="flex: 1; border: 1px solid var(--gold); color: var(--gold); background: transparent; border-radius:10px;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                        <button class="btn-cancel" onclick="document.getElementById(\'modalPDFConfig\').style.display=\'none\'" style="margin-top:15px;">
                            Cancelar
                        </button>
                    </div>
                </div>

                <div id="template-impressao-full" style="display:none;">
                    <div class="pdf-sheet">
                        
                        <div class="sheet-header" id="pdf-header-main">
                            <div class="sh-meta">
                                <span id="render-plano-nome">HIPERTROFIA AVANÇADA</span>
                                <span>DATA: <strong>'.date('d/m/Y').'</strong></span>
                            </div>
                            <h1><strong id="render-aluno-nome">NOME DO ALUNO</strong></h1>
                            <div class="sh-logo">
                                <img src="assets/img/icones/icon-nav.png" alt="Ryan Coach">
                            </div>
                        </div>

                        <div id="pdf-container-treinos"></div>

                        <div class="sheet-footer">
                            <p>Metodologia <strong>RYAN COACH</strong></p>
                        </div>
                    </div>
                </div>

              </section>';
        break;



        // -----------------------------------------------------------------------------------------------------------------------------------------------------
        // NOVOS
        // -----------------------------------------------------------------------------------------------------------------------------------------------------

    case 'novo_treino':
        // Busca lista de alunos APENAS se for Personal/Admin (para o futuro)
        // Se for atleta, $alunos fica vazio e o select não aparece
        $alunos = [];
        if (isset($_SESSION['tipo_conta']) && ($_SESSION['tipo_conta'] === 'personal' || $_SESSION['tipo_conta'] === 'admin')) {
             $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_conta = 'atleta' ORDER BY nome ASC");
             $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo '<section class="fade-in">
                <header class="dash-header">
                    <button onclick="carregarConteudo(\'dashboard\')" style="background:none; border:none; color:#fff; font-size:1.2rem; margin-right:15px;">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h1>CRIAR NOVO <span class="highlight-text">TREINO</span></h1>
                </header>

                <div class="form-container" style="background:#1e1e1e; padding:20px; border-radius:10px; border:1px solid #333;">
                    <form id="formNovoTreino" onsubmit="criarTreino(event)">
                        
                        ';
                        
                        // Lógica: Se for Atleta, input hidden. Se for Personal, Select.
                        if ($_SESSION['tipo_conta'] === 'atleta') {
                            echo '<input type="hidden" name="aluno_id" value="'.$_SESSION['user_id'].'">
                                  <div style="background:rgba(218,165,32,0.1); padding:10px; border-radius:5px; margin-bottom:15px; border:1px solid var(--gold); color:var(--gold);">
                                      <i class="fa-solid fa-user"></i> Criando treino para: <strong>Você mesmo</strong>
                                  </div>';
                        } else {
                            echo '<div class="form-group" style="margin-bottom:15px;">
                                    <label style="display:block; color:#ccc; margin-bottom:5px;">Selecione o Aluno:</label>
                                    <select name="aluno_id" required style="width:100%; padding:10px; background:#333; border:1px solid #444; color:#fff; border-radius:5px;">
                                        <option value="">-- Escolha --</option>';
                                        foreach ($alunos as $al) {
                                            echo '<option value="'.$al['id'].'">'.$al['nome'].'</option>';
                                        }
                            echo   '</select>
                                  </div>';
                        }

        echo '          <div class="form-group" style="margin-bottom:15px;">
                            <label style="display:block; color:#ccc; margin-bottom:5px;">Nome do Treino / Fase:</label>
                            <input type="text" name="nome" placeholder="Ex: Adaptação, Força..." required style="width:100%; padding:10px; background:#333; border:1px solid #444; color:#fff; border-radius:5px;">
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="display:block; color:#ccc; margin-bottom:5px;">Divisão (Letras):</label>
                            <input type="text" name="divisao" placeholder="Ex: ABC, ABCD..." required style="width:100%; padding:10px; background:#333; border:1px solid #444; color:#fff; border-radius:5px; text-transform:uppercase;">
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="display:block; color:#ccc; margin-bottom:5px;">Data de Início:</label>
                            <input type="date" name="data_inicio" value="'.date('Y-m-d').'" required style="width:100%; padding:10px; background:#333; border:1px solid #444; color:#fff; border-radius:5px;">
                        </div>

                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="display:block; color:#ccc; margin-bottom:5px;">Dias de Treino:</label>
                            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px;">
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="1"> Seg</label>
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="2"> Ter</label>
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="3"> Qua</label>
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="4"> Qui</label>
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="5"> Sex</label>
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="6"> Sáb</label>
                                <label style="background:#333; padding:10px; text-align:center; border-radius:5px;"><input type="checkbox" name="dias_semana[]" value="7"> Dom</label>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:20px;">
                            <label style="display:block; color:#ccc; margin-bottom:5px;">Planejamento:</label>
                            <select name="nivel" style="width:100%; padding:10px; background:#333; border:1px solid #444; color:#fff; border-radius:5px;">
                                <option value="avancado" selected>Com Periodização (12 Semanas)</option>
                                <option value="basico">Básico (Sem datas fixas)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-finish" style="width:100%;">
                            CRIAR TREINO <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
              </section>';
        break;
    

    case 'treino_painel':
        require_once '../config/db_connect.php';
        
        // Verifica se tem ID na URL
        $treino_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        if (!$treino_id) { 
            echo "<div class='glass-card'>ID do treino não fornecido.</div>"; 
            break; 
        }

        // 1. BUSCAR DADOS GERAIS (Voltei para a consulta original sem 'personal_id')
        $sql = "SELECT t.*, u.nome as nome_aluno 
                FROM treinos t 
                JOIN usuarios u ON t.aluno_id = u.id 
                WHERE t.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $treino_id]); // Removi o par 'personal_id'
        $treino = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não achar o treino
        if (!$treino) {
            echo "<div class='glass-card'>Treino não encontrado.</div>";
            break;
        }

        // 2. BUSCAR DIVISÕES (A, B, C...)
        $sql_div = "SELECT * FROM treino_divisoes WHERE treino_id = :id ORDER BY letra ASC";
        $stmt_div = $pdo->prepare($sql_div);
        $stmt_div->execute(['id' => $treino_id]);
        $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

        // 3. BUSCAR PERIODIZAÇÃO E MICROCICLOS
        $microciclos = [];
        // Só busca microciclos se NÃO for plano básico (exemplo de lógica)
        if ($treino['nivel_plano'] !== 'basico') {
            $stmt_per = $pdo->prepare("SELECT id FROM periodizacoes WHERE treino_id = ?");
            $stmt_per->execute([$treino_id]);
            $periodizacao_id = $stmt_per->fetchColumn();

            if ($periodizacao_id) {
                $stmt_micro = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero ASC");
                $stmt_micro->execute([$periodizacao_id]);
                $microciclos = $stmt_micro->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // --- INICIO HTML ---
        echo '
            <section id="painel-treino" class="fade-in">
                <div style="display:flex; align-items:center; gap:20px; margin-bottom:30px;">
                    <button class="btn-action-icon" onclick="carregarConteudo(\'treinos\')"><i class="fa-solid fa-arrow-left"></i></button>
                    <div>
                        <h2 style="color:#fff; font-family:Orbitron; margin:0;">'.$treino['nome'].'</h2>
                        <p style="color:#888; font-size:0.9rem;">Aluno: <strong style="color:var(--gold);">'.$treino['nome_aluno'].'</strong> • '.strtoupper($treino['nivel_plano']).'</p>
                    </div>
                </div>

                ';
                
                // EXIBE A PERIODIZAÇÃO SE HOUVER
                if (!empty($microciclos)) {
                    echo '<h3 class="section-title" style="font-size:1rem; margin-bottom:10px;">PERIODIZAÇÃO (12 SEMANAS)</h3>
                          <div class="timeline-wrapper">';
                    
                    foreach ($microciclos as $m) {
                        $inicio = date('d/m', strtotime($m['data_inicio_semana']));
                        $fim = date('d/m', strtotime($m['data_fim_semana']));
                        
                        $hoje = date('Y-m-d');
                        $activeClass = ($hoje >= $m['data_inicio_semana'] && $hoje <= $m['data_fim_semana']) ? 'active' : '';
                        
                        // JSON seguro para passar pro JS
                        $m_json = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');

                        echo '
                        <div class="micro-card '.$activeClass.'" onclick=\'openMicroModal('.$m_json.', '.$treino_id.')\'>
                            <span class="micro-week">SEMANA '.$m['semana_numero'].' <i class="fa-solid fa-pen" style="font-size:0.6rem; margin-left:5px;"></i></span>
                            <span class="micro-date">'.$inicio.' - '.$fim.'</span>
                            <div style="margin-top:5px; font-size:0.7rem; color: inherit; opacity:0.7;">'.$m['nome_fase'].'</div>
                        </div>';
                    }
                    echo '</div>';
                }
        
        echo '
                <div class="glass-card">
                    <div class="division-tabs">';
                        $first = true;
                        foreach ($divisoes as $div) {
                            $active = $first ? 'active' : '';
                            // Nota: openTab é uma função JS que você precisa ter no seu script.js
                            echo '<button class="div-tab-btn '.$active.'" onclick="openTab(event, \'div_'.$div['letra'].'\')">TREINO '.$div['letra'].'</button>';
                            $first = false;
                        }
        echo '      </div>';

                    // CONTEÚDO DAS ABAS (Lista de Exercícios)
                    $firstContent = true;
                    foreach ($divisoes as $div) {
                        $display = $firstContent ? 'active' : '';
                        
                        // Busca exercícios desta divisão
                        $sqlEx = "SELECT * FROM exercicios WHERE divisao_id = ? ORDER BY ordem ASC";
                        $stmtEx = $pdo->prepare($sqlEx);
                        $stmtEx->execute([$div['id']]);
                        $exercicios = $stmtEx->fetchAll(PDO::FETCH_ASSOC);

                        echo '
                        <div id="div_'.$div['letra'].'" class="division-content '.$display.'">
                            
                            <div class="div-header" id="div-treino">
                                <div>
                                    <div style="display:flex; align-items:center; gap: 10px;">
                                        <h3 style="color:#fff; margin:0; font-size: 1.2rem;">TREINO '.$div['letra'].'</h3>
                                        
                                        <button onclick="renomearDivisao('.$div['id'].', \''.$div['letra'].'\', \''.($div['nome'] ?? '').'\')" 
                                                style="background: transparent; border: none; color: #666; cursor: pointer; font-size: 0.9rem; transition: color 0.3s;"
                                                onmouseover="this.style.color=\'var(--gold)\'" 
                                                onmouseout="this.style.color=\'#666\'"
                                                title="Editar Nome do Treino">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                    
                                    <span id="label_nome_div_'.$div['id'].'" style="color:var(--gold); font-size: 0.9rem; font-weight: bold; text-transform: uppercase; display: block; margin-top: 2px;">
                                        '.($div['nome'] ? $div['nome'] : 'SEM NOME DEFINIDO').'
                                    </span>
                                </div>

                                <button class="btn-gerenciar" onclick="openExercicioModal('.$div['id'].', '.$treino_id.')">
                                    <i class="fa-solid fa-plus"></i> ADD EXERCÍCIO
                                </button>
                            </div>

                            <div class="exercise-list">';
                                
                                if (count($exercicios) > 0) {
                                    foreach ($exercicios as $ex) {
                                        // Busca séries deste exercício
                                        $sqlSeries = "SELECT * FROM series WHERE exercicio_id = ?";
                                        $stmtSeries = $pdo->prepare($sqlSeries);
                                        $stmtSeries->execute([$ex['id']]);
                                        $series = $stmtSeries->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        $ex_data = $ex;
                                        $ex_data['series'] = $series;
                                        $ex_json = htmlspecialchars(json_encode($ex_data), ENT_QUOTES, 'UTF-8');

                                        echo '
                                        <div class="exercise-card">
                                            <div class="ex-info">
                                                <span class="ex-meta">'.strtoupper($ex['tipo_mecanica']).'</span>
                                                <h4>'.$ex['nome_exercicio'].'</h4>
                                                <div class="sets-container">';
                                                    foreach ($series as $s) {
                                                        $infoReps = $s['reps_fixas'] ? "(".$s['reps_fixas'].")" : "";
                                                        echo '<span class="set-tag '.$s['categoria'].'">'.$s['quantidade'].'x '.strtoupper($s['categoria']).' '.$infoReps.'</span>';
                                                    }
                                            echo '  </div>
                                            </div>
                                            <div class="ex-actions">
                                                <button class="btn-action-icon" onclick=\'editarExercicio('.$ex_json.', '.$treino_id.', '.$div['id'].')\'>
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                
                                                <button class="btn-action-icon btn-delete" onclick="deletarExercicio('.$ex['id'].', '.$treino_id.')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>';
                                    }
                                } else {
                                    echo '<p style="text-align:center; color:#666; padding:30px;">Nenhum exercício cadastrado.</p>';
                                }
                        
                        echo '</div>
                        </div>';
                        $firstContent = false;
                    }

        echo '  </div>
            </section>

            <div id="modalExercicio" class="modal-overlay">
                <div class="modal-content" style="max-width: 700px;">
                    <button class="modal-close" onclick="closeExercicioModal()">&times;</button>
                    <h3 class="section-title" style="color:var(--gold); margin-bottom:20px;">Novo Exercício</h3>
                    
                    <form id="formExercicio" onsubmit="salvarExercicio(event)">
                        <input type="hidden" name="divisao_id" id="modal_divisao_id">
                        <input type="hidden" name="treino_id" id="modal_treino_id">
                        <input type="hidden" name="exercicio_id" id="modal_exercicio_id">
                        <input type="hidden" name="series_data" id="series_json_input">

                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:2;">
                                <label class="input-label">Nome do Exercício</label>
                                <input type="text" name="nome_exercicio" class="user-input" placeholder="Ex: Supino Reto" required>
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Mecânica</label>
                                <select name="tipo_mecanica" class="user-input">
                                    <option value="livre">Livre / Máquina</option>
                                    <option value="composto">Composto (Periodizado)</option>
                                    <option value="isolador">Isolador (Periodizado)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:1;">
                                <label class="input-label">Link Vídeo (Youtube/Drive)</label>
                                <input type="text" name="video_url" class="user-input" placeholder="https://...">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Observação</label>
                                <input type="text" name="observacao" class="user-input" placeholder="Ex: Segurar 2s na descida">
                            </div>
                        </div>

                        <hr style="border:0; border-top:1px solid #333; margin:20px 0;">

                        <h4 style="color:#fff; font-size:0.9rem; margin-bottom:10px;">Configuração de Séries</h4>
                        
                        <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px;">
                            <label class="input-label" style="color:var(--gold); margin-bottom:10px; display:block;">Adicionar Série</label>
                            
                            <div class="set-inputs-row" style="display:flex; gap:10px; align-items:flex-end;">
                                <div style="flex:0 0 60px;">
                                    <label class="input-label" style="font-size:0.7rem;">Qtd</label>
                                    <input type="number" id="set_qtd" class="user-input" value="1" style="padding:8px;">
                                </div>
                                <div style="flex:1; min-width:100px;">
                                    <label class="input-label" style="font-size:0.7rem;">Tipo</label>
                                    <select id="set_tipo" class="user-input" style="padding:8px;">
                                        <option value="warmup">Warm Up</option>
                                        <option value="feeder">Feeder</option>
                                        <option value="work" selected>Work Set</option>
                                        <option value="top">Top Set</option>
                                        <option value="backoff">Backoff</option>
                                        <option value="falha">Falha</option>
                                    </select>
                                </div>
                                <div style="flex:1; min-width:70px;">
                                    <label class="input-label" style="font-size:0.7rem;">Reps</label>
                                    <input type="text" id="set_reps" class="user-input" placeholder="Ex: 10" style="padding:8px;">
                                </div>
                                <div style="flex:1; min-width:70px;">
                                    <label class="input-label" style="font-size:0.7rem;">Descanso</label>
                                    <input type="text" id="set_desc" class="user-input" placeholder="90s" style="padding:8px;">
                                </div>
                                <div style="flex:0 0 60px;">
                                    <label class="input-label" style="font-size:0.7rem;">RPE</label>
                                    <input type="number" id="set_rpe" class="user-input" placeholder="-" style="padding:8px;">
                                </div>
                                <button type="button" class="btn-gold btn-add-set-mobile" onclick="addSetToList()" style="padding:8px 15px; height:38px;">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <div id="temp-sets-list" style="margin-top:15px; max-height:150px; overflow-y:auto;">
                                <p style="color:#666; font-size:0.8rem; text-align:center; margin-top:10px;">Nenhuma série adicionada.</p>
                            </div>
                        </div>

                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn-gold" style="background:transparent; border:1px solid #555; color:#ccc; margin-right:10px;" onclick="closeExercicioModal()">Cancelar</button>
                            <button type="submit" class="btn-gold">SALVAR EXERCÍCIO</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="modalMicro" class="modal-overlay">
                <div class="modal-content">
                    <button class="modal-close" onclick="closeMicroModal()">&times;</button>
                    <h3 class="section-title" style="color:var(--gold); margin-bottom:20px;">
                        <i class="fa-solid fa-calendar-week"></i> Configurar Semana <span id="span_semana_num"></span>
                    </h3>
                    <form id="formMicro" onsubmit="salvarMicro(event)">
                        <input type="hidden" name="micro_id" id="micro_id">
                        <input type="hidden" name="treino_id" id="micro_treino_id">

                        <div style="margin-bottom:15px;">
                            <label class="input-label">Fase / Nome da Semana</label>
                            <input type="text" name="nome_fase" id="micro_fase" class="user-input" placeholder="Ex: Força ou Choque" required>
                        </div>

                        <h4 style="color:#fff; font-size:0.8rem; margin-bottom:5px; border-bottom:1px solid #333; padding-bottom:5px;">Multiarticulares / Compostos</h4>
                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:2;">
                                <label class="input-label">Faixa de Repetições</label>
                                <input type="text" name="reps_compostos" id="micro_reps_comp" class="user-input" placeholder="Ex: 6 a 8">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Descanso (seg)</label>
                                <input type="number" name="descanso_compostos" id="micro_desc_comp" class="user-input" placeholder="Ex: 120">
                            </div>
                        </div>

                        <h4 style="color:#fff; font-size:0.8rem; margin-bottom:5px; border-bottom:1px solid #333; padding-bottom:5px;">Isoladores / Monoarticulares</h4>
                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:2;">
                                <label class="input-label">Faixa de Repetições</label>
                                <input type="text" name="reps_isoladores" id="micro_reps_iso" class="user-input" placeholder="Ex: 10 a 12">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Descanso (seg)</label>
                                <input type="number" name="descanso_isoladores" id="micro_desc_iso" class="user-input" placeholder="Ex: 60">
                            </div>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label class="input-label">Foco / Comentário para o Aluno</label>
                            <textarea name="foco_comentario" id="micro_foco" class="user-input" rows="3" placeholder="Ex: Focar na progressão de carga..."></textarea>
                        </div>

                        <button type="submit" class="btn-gold" style="width:100%;">SALVAR SEMANA</button>
                    </form>
                </div>
            </div>
        ';
        break;              





    // --- MENU GERAL (HUB DE NAVEGAÇÃO) ---
    case 'menu':
        require_once '../config/db_connect.php';
        $user_id = $_SESSION['user_id'];
        
        // Busca dados básicos
        $stmt = $pdo->prepare("SELECT nome, email, foto, tipo_conta FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $foto = $user['foto'] ? $user['foto'] : 'assets/img/user-default.png';
        
        echo '<section id="menu-hub" class="fade-in">
                
                <div class="menu-profile-header" onclick="carregarConteudo(\'perfil\')">
                    <div class="mph-left">
                        <img src="'.$foto.'" class="mph-avatar">
                        <div class="mph-info">
                            <h3>'.$user['nome'].'</h3>
                            <span>'.$user['email'].'</span>
                            <small class="mph-badge">'.strtoupper($user['tipo_conta']).'</small>
                        </div>
                    </div>
                    <div class="mph-arrow">
                        <span style="font-size:0.7rem; color:#888; margin-right:5px;">Editar</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>

                <h3 class="section-label" style="margin-left: 10px;">PRINCIPAL</h3>
                <div class="menu-grid">
                    
                    <div class="menu-card" onclick="carregarConteudo(\'treinos\')">
                        <div class="mc-icon" style="background: rgba(255, 186, 66, 0.1); color: var(--gold);">
                            <i class="fa-solid fa-dumbbell"></i>
                        </div>
                        <span>Treinos</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'avaliacoes\')">
                        <div class="mc-icon" style="background: rgba(0, 200, 255, 0.1); color: #00c8ff;">
                            <i class="fa-solid fa-ruler-combined"></i>
                        </div>
                        <span>Avaliação</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'historico\')">
                        <div class="mc-icon" style="background: rgba(100, 255, 100, 0.1); color: #64ff64;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <span>Histórico</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'dieta\')">
                        <div class="mc-icon" style="background: rgba(255, 100, 100, 0.1); color: #ff6464;">
                            <i class="fa-solid fa-apple-whole"></i>
                        </div>
                        <span>Dieta</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'financeiro\')">
                        <div class="mc-icon" style="background: rgba(200, 100, 255, 0.1); color: #c864ff;">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <span>Planos</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'gerar_pdf\')">
                        <div class="mc-icon" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid #555;">
                            <i class="fa-solid fa-print"></i>
                        </div>
                        <span>Relatórios</span>
                    </div>
                    
                    <div class="menu-card" onclick="carregarConteudo(\'novo_treino\')">
                        <div class="mc-icon" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid #555;">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <span>Novo Treino</span>
                    </div>

                    <div class="menu-card" onclick="let id = prompt(\'Digite o ID do Treino para editar:\'); if(id) carregarConteudo(\'treino_painel&id=\'+id)">
                        <div class="mc-icon" style="background: rgba(150, 50, 255, 0.1); color: #a855f7; border: 1px solid #a855f7;">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <span>Editor (Teste)</span>
                    </div>

                </div>

                <h3 class="section-label" style="margin-left: 10px; margin-top: 30px;">SISTEMA</h3>
                <div class="settings-list">
                    
                    <a href="https://wa.me/55SEUNUMERO" target="_blank" class="setting-item">
                        <div class="st-left">
                            <i class="fa-brands fa-whatsapp" style="color: #25D366;"></i>
                            <span>Suporte via WhatsApp</span>
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.8rem; color: #666;"></i>
                    </a>

                    <div class="setting-item" onclick="window.location.href=\'index.php\'">
                        <div class="st-left">
                            <i class="fa-solid fa-globe"></i>
                            <span>Página Inicial do Site</span>
                        </div>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>

                    <div class="setting-item logout" onclick="window.location.href=\'actions/logout.php\'">
                        <div class="st-left">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Sair da Conta</span>
                        </div>
                    </div>

                </div>
                
                <div style="text-align:center; margin-top:40px; color:#444; font-size:0.7rem;">
                    <p>Ryan Coach App v1.0</p>
                </div>

              </section>';
        break;

    default:
        // Caso a página pedida não exista
        echo '
            <section id="erro">
                <h1>Página não encontrada</h1>
                <p>O conteúdo solicitado não foi encontrado.</p>
            </section>
        ';
        break;
}

?>