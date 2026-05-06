<?php
if(session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db_connect.php';

$aluno_id = $_SESSION['user_id'];
$pagina_raw = $_GET['pagina'] ?? 'dashboard';
$partes = explode('&', $pagina_raw);
$pagina = $partes[0];

// --- BUSCA O PLANO DO USUÁRIO NO INÍCIO ---
$stmt_plano = $pdo->prepare("SELECT plano_atual FROM usuarios WHERE id = ?");
$stmt_plano->execute([$aluno_id]);
$dado_user = $stmt_plano->fetch(PDO::FETCH_ASSOC);
$plano_aluno = $dado_user['plano_atual'] ?? 'start'; // 'start', 'pro', 'coach'

$hoje = date('Y-m-d');
$divisao_req = $_GET['divisao_id'] ?? null; // Usado no Realizar Treino
$treino_req  = $_GET['treino_id'] ?? null;  // Usado no Visualizar Treino
$micro_req   = $_GET['micro_id'] ?? null;   // Usado no Visualizar Treino

// Nome do Usuário
$nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Atleta'));
$primeiro_nome = strtoupper($nome[0]);

session_write_close();

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
        
        // --- LÓGICA DE DADOS (DOMINGO A SÁBADO) ---
        $dia_semana_atual = date('w'); // 0 (Dom) a 6 (Sáb)
        
        // Subtrai os dias passados para voltar ao Domingo
        $start_week = date('Y-m-d 00:00:00', strtotime("-$dia_semana_atual days"));
        $end_week   = date('Y-m-d 23:59:59', strtotime($start_week . " +6 days"));
        
        // 1. Frequência
        $stmt_w = $pdo->prepare("SELECT COUNT(DISTINCT data_treino) FROM treino_historico WHERE aluno_id = ? AND data_treino BETWEEN ? AND ?");
        $stmt_w->execute([$aluno_id, $start_week, $end_week]);
        $treinos_semana = $stmt_w->fetchColumn();

        // 2. Volume (Tonelagem)
        $stmt_vol = $pdo->prepare("SELECT SUM(carga_kg * reps_realizadas) FROM treino_historico WHERE aluno_id = ? AND data_treino BETWEEN ? AND ?");
        $stmt_vol->execute([$aluno_id, $start_week, $end_week]);
        $volume = $stmt_vol->fetchColumn() ?: 0;
        $vol_fmt = ($volume > 1000) ? number_format($volume/1000, 1).'k' : $volume;

        // 3. Streak (Ofensiva) - INTELIGENTE (Considera dias de descanso)
        $streak = 0;
        
        // MUDANÇA AQUI: Aumentamos para 365 dias (1 Ano)
        $data_limite = date('Y-m-d', strtotime('-365 days')); 
        
        $stmt_chk = $pdo->prepare("SELECT DISTINCT DATE(data_treino) as dia FROM treino_historico WHERE aluno_id = ? AND data_treino >= ? ORDER BY data_treino DESC");
        $stmt_chk->execute([$aluno_id, $data_limite]);
        $dias_treinados = $stmt_chk->fetchAll(PDO::FETCH_COLUMN);

        // Busca configuração de dias
        $stmt_dias = $pdo->prepare("SELECT dias_semana FROM treinos WHERE aluno_id = ? AND ativo = 1 ORDER BY criado_em DESC LIMIT 1");
        $stmt_dias->execute([$aluno_id]);
        $dias_config_json = $stmt_dias->fetchColumn();
        
        $dias_obrigatorios = $dias_config_json ? json_decode($dias_config_json) : [0,1,2,3,4,5,6];
        if (!is_array($dias_obrigatorios)) $dias_obrigatorios = [0,1,2,3,4,5,6];


        for ($i = 0; $i < 365; $i++) {
            $check_date = date('Y-m-d', strtotime("-$i days"));
            $check_dia_semana = date('w', strtotime($check_date)); 

            // Tratamento Domingo (0 ou 7)
            $dia_buscado = $check_dia_semana;
            if ($check_dia_semana == 0 && in_array(7, $dias_obrigatorios)) {
                $dia_buscado = 7;
            }

            $eh_dia_de_treino = in_array($dia_buscado, $dias_obrigatorios);
            $treinou = in_array($check_date, $dias_treinados);

            if ($treinou) {
                $streak++;
            } 
            else {
                // Se faltou num dia obrigatório (e não é hoje), quebra.
                if ($eh_dia_de_treino) {
                     if ($i > 0) {
                         break; 
                     }
                }
            }
        }

        // --- 4. LÓGICA DO "TREINO DE HOJE"  ---
        $hoje_dia_num = date('w'); // 0 (Dom) a 6 (Sáb)
        
        // Tratamento de legado: Se hoje for 0 (Domingo), aceita também 7 (banco antigo)
        $check_dias = [$hoje_dia_num];
        if ($hoje_dia_num == 0) $check_dias[] = 7;

        $stmt_ativo = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY criado_em DESC LIMIT 1");
        $stmt_ativo->execute([$aluno_id]);
        $treino_ativo = $stmt_ativo->fetch(PDO::FETCH_ASSOC);

        $card_titulo = "SEM TREINO";
        $card_subtitulo = "Nenhum plano ativo";
        $card_badge = "Off";
        $card_letra = "-";
        $is_rest_day = false;
        $divisao_hoje_id = ''; 

        if ($treino_ativo) {
            $dias_treino = json_decode($treino_ativo['dias_semana']);
            if (!is_array($dias_treino)) $dias_treino = [];

            // Verifica se hoje está nos dias de treino (usando a lista segura check_dias)
            if (count(array_intersect($check_dias, $dias_treino)) > 0) {
                
                // Busca as divisões
                $stmt_divs = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
                $stmt_divs->execute([$treino_ativo['id']]);
                $divisoes = $stmt_divs->fetchAll(PDO::FETCH_ASSOC);

                if (count($divisoes) > 0) {
                    // Descobre o índice
                    $indice_hoje = array_search($hoje_dia_num, $dias_treino);
                    
                    // Fallback: se não achou 0 e hoje é domingo, procura o 7
                    if ($indice_hoje === false && $hoje_dia_num == 0) {
                        $indice_hoje = array_search(7, $dias_treino);
                    }

                    if ($indice_hoje !== false) {
                        $indice_divisao = $indice_hoje % count($divisoes);
                        $div_hoje = $divisoes[$indice_divisao];

                        $card_letra = $div_hoje['letra'];
                        $card_titulo = "Treino " . $div_hoje['letra'];
                        $card_subtitulo = $div_hoje['nome'] ? $div_hoje['nome'] : 'Toque para iniciar';
                        $divisao_hoje_id = '&divisao_id=' . $div_hoje['id']; 
                        
                        // Periodização
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
                            
                            // Array visual começando no Domingo
                            $dias = ['D','S','T','Q','Q','S','S']; 
                            $hoje_w = date('w'); // 0 a 6
                            
                            $stmt_d = $pdo->prepare("SELECT DATE(data_treino) FROM treino_historico WHERE aluno_id = ? AND data_treino BETWEEN ? AND ?");
                            $stmt_d->execute([$aluno_id, $start_week, $end_week]);
                            $dias_feitos = $stmt_d->fetchAll(PDO::FETCH_COLUMN);

                            for($i=0; $i<=6; $i++){
                                $dt = date('Y-m-d', strtotime($start_week . " +$i days"));
                                $done = in_array($dt, $dias_feitos) ? 'done' : '';
                                $curr = ($i == $hoje_w) ? 'current' : '';
                                echo '<div class="day-pill '.$done.' '.$curr.'">'.$dias[$i].'</div>';
                            }
        echo '          </div>
                    </div>

                </div>
              </section>';
        break;

    case 'realizar_treino':
        require_once '../config/db_connect.php'; 
        require_once '../helpers/tradutor_treino.php'; // 1. Chama o helper de tradução
        
        if (!isset($_SESSION['user_id'])) { echo "Sessão expirada."; break; }
        
        $aluno_id = $_SESSION['user_id'];
        $hoje = date('Y-m-d');

        // 2. Tenta pegar o idioma da sessão, senão assume o padrão 'pt'
        $idioma_aluno = $_SESSION['pref_idioma'] ?? 'pt';

        // 1. Busca o Treino Ativo e se possível o idioma do banco (como backup)
        // Usamos um JOIN para garantir que temos o idioma real, caso a sessão esteja desatualizada
        $sql = "SELECT t.*, u.pref_idioma 
                FROM treinos t 
                JOIN usuarios u ON t.aluno_id = u.id 
                WHERE t.aluno_id = :uid ORDER BY t.criado_em DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $aluno_id]);
        $treino_ativo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$treino_ativo) {
            echo '<section class="empty-state"><h2>Sem treino ativo</h2></section>';
            break;
        }

        // Atualiza a variável de idioma caso o banco traga algo diferente
        if (isset($treino_ativo['pref_idioma'])) {
            $idioma_aluno = $treino_ativo['pref_idioma'];
            $_SESSION['pref_idioma'] = $idioma_aluno;
        }

        // 2. Lógica de Seleção Automática do Dia
        $divisao_req = filter_input(INPUT_GET, 'divisao_id', FILTER_SANITIZE_NUMBER_INT);
        
        if (!$divisao_req) {
            $hoje_dia_num = date('w'); 
            $check_dias = [$hoje_dia_num];
            if ($hoje_dia_num == 0) $check_dias[] = 7; 

            $dias_treino = json_decode($treino_ativo['dias_semana'] ?? '[]'); 
            if (!is_array($dias_treino)) $dias_treino = [];

            $stmt_div = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
            $stmt_div->execute([$treino_ativo['id']]);
            $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);
            
            $divisao_sugerida = null;
            $qtd_divisoes = count($divisoes);
            
            if (count(array_intersect($check_dias, $dias_treino)) > 0 && $qtd_divisoes > 0) {
                $indice_hoje = array_search($hoje_dia_num, $dias_treino);
                if ($indice_hoje === false && $hoje_dia_num == 0) {
                    $indice_hoje = array_search(7, $dias_treino);
                }

                if ($indice_hoje !== false) {
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
                }
            }
            
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

        // 3. EXIBIÇÃO DO TREINO
        $divisao_id = $divisao_req;
        
        $stmt_d = $pdo->prepare("SELECT * FROM treino_divisoes WHERE id = ?");
        $stmt_d->execute([$divisao_id]);
        $div_atual = $stmt_d->fetch(PDO::FETCH_ASSOC);

        if (!$div_atual) { echo '<p>Erro: Divisão não encontrada.</p>'; break; }

        // --- CARREGAMENTO OTIMIZADO ---
        $stmt_ex = $pdo->prepare("SELECT * FROM exercicios WHERE divisao_id = ? ORDER BY ordem ASC");
        $stmt_ex->execute([$divisao_id]);
        $exercicios = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
        
        $series_por_exercicio = [];
        $historico_por_exercicio = [];

        if (count($exercicios) > 0) {
            $exercicio_ids = array_column($exercicios, 'id');
            $ids_placeholder = implode(',', array_fill(0, count($exercicio_ids), '?'));

            $stmt_all_series = $pdo->prepare("SELECT * FROM series WHERE exercicio_id IN ($ids_placeholder) ORDER BY id ASC");
            $stmt_all_series->execute($exercicio_ids);
            $todas_series = $stmt_all_series->fetchAll(PDO::FETCH_ASSOC);

            foreach ($todas_series as $s) {
                $series_por_exercicio[$s['exercicio_id']][] = $s;
            }

            // Tenta buscar histórico
            try {
                $data_limite_hist = date('Y-m-d', strtotime('-60 days'));
                
                $sql_hist = "SELECT th.exercicio_id, th.serie_id, th.numero_serie, th.serie_numero, th.carga_kg, th.reps_realizadas, th.dados_tecnicos, th.data_treino,
                                    s.tecnica, s.categoria
                             FROM treino_historico th
                             LEFT JOIN series s ON th.serie_id = s.id
                             WHERE th.aluno_id = ? 
                             AND th.exercicio_id IN ($ids_placeholder) 
                             AND th.data_treino >= ?
                             ORDER BY th.data_treino DESC, th.id ASC";
                
                $stmt_hist = $pdo->prepare($sql_hist);
                $stmt_hist->execute(array_merge([$aluno_id], $exercicio_ids, [$data_limite_hist]));
                $historico_raw = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

                $ultima_data_por_exercicio = [];

                foreach ($historico_raw as $h) {
                    $eid = $h['exercicio_id'];
                    $data = $h['data_treino'];

                    if (!isset($ultima_data_por_exercicio[$eid])) {
                        $ultima_data_por_exercicio[$eid] = $data;
                    }

                    if ($ultima_data_por_exercicio[$eid] === $data) {
                        $s_key = $h['serie_id'] ? $h['serie_id'] : $h['serie_numero'];
                        $n_key = $h['numero_serie'] ? $h['numero_serie'] : 1;
                        
                        if (!isset($historico_por_exercicio[$eid][$s_key][$n_key])) {
                            $historico_por_exercicio[$eid][$s_key][$n_key] = [];
                        }
                        $historico_por_exercicio[$eid][$s_key][$n_key][] = $h;
                    }
                }
            } catch (Exception $e) {
                $historico_por_exercicio = [];
            }
        }

        // --- LÓGICA DE PERIODIZAÇÃO ---
        $micro_atual = null;
        if ($treino_ativo['nivel_plano'] !== 'basico') {
             $stmt_per = $pdo->prepare("SELECT id FROM periodizacoes WHERE treino_id = ?");
             $stmt_per->execute([$treino_ativo['id']]);
             $pid = $stmt_per->fetchColumn();
             
             if($pid) {
                 $stmt_m = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero ASC");
                 $stmt_m->execute([$pid]);
                 $micros = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

                 foreach ($micros as $m) {
                     if ($hoje >= $m['data_inicio_semana'] && $hoje <= $m['data_fim_semana']) {
                         $micro_atual = $m;
                         break;
                     }
                 }
                 
                 if (!$micro_atual && !empty($micros)) {
                     $micro_atual = $micros[0]; 
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
            
            $lista_final = [];
            $grupos_temp = []; 

            foreach ($exercicios as $ex) {
                $hash = $ex['agrupamento_hash'] ?? null;
                
                if ($hash) {
                    if (!isset($grupos_temp[$hash])) {
                        $idx = count($lista_final);
                        $lista_final[$idx] = ['tipo' => 'grupo', 'itens' => []];
                        $grupos_temp[$hash] = $idx;
                    }
                    $lista_final[$grupos_temp[$hash]]['itens'][] = $ex;
                } else {
                    $lista_final[] = ['tipo' => 'single', 'itens' => [$ex]];
                }
            }

            foreach ($lista_final as $bloco) {

                if ($bloco['tipo'] === 'grupo') {
                    $qtd_grupo = count($bloco['itens']);
                    $label_grupo = ($qtd_grupo === 2) ? 'BI-SET' : 'TRI-SET';
                    echo '<div class="exec-agrupamento">';
                    echo '<span class="exec-agrupamento-badge">'.$label_grupo.'</span>';
                }

                foreach ($bloco['itens'] as $ex) {
                    
                    $series = $series_por_exercicio[$ex['id']] ?? [];
                    $historico_map = $historico_por_exercicio[$ex['id']] ?? [];

                    $historico_json = htmlspecialchars(json_encode($historico_map), ENT_QUOTES, 'UTF-8');
                    $nome_ex_safe   = htmlspecialchars($ex['nome_exercicio'], ENT_QUOTES, 'UTF-8');
                    $video_html     = (!empty($ex['video_url'])) ? '<a href="'.$ex['video_url'].'" target="_blank" class="exec-video"><i class="fa-solid fa-circle-play"></i></a>' : '';

                    echo '
                    <div class="exec-card">
                        <div class="exec-header" style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="flex:1;">
                                <span class="exec-title">'.$ex['nome_exercicio'].'</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                '.$video_html.'
                                <button type="button" onclick=\'abrirHistoricoExercicio('.$historico_json.', "'.$nome_ex_safe.'")\' 
                                        style="background:rgba(255,186,66,0.15); border:1px solid var(--gold); color:var(--gold); width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-clock-rotate-left" style="font-size:0.9rem;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="set-row-header">
                            <span>SÉRIE</span>
                            <span>META</span>
                            <span>CARGA (KG)</span>
                            <span>REPS</span>
                        </div>';

                    if (count($series) > 0) {
                        foreach ($series as $s) {
                            $tecnica_raw = strtolower(trim((string)($s['tecnica'] ?? 'normal')));
                            $valor_raw   = $s['tecnica_valor'] ?? '';
                            
                            $is_drop    = ($tecnica_raw === 'dropset');
                            $is_rest    = ($tecnica_raw === 'restpause');
                            $is_cluster = ($tecnica_raw === 'clusterset');
                            $has_technique = ($is_drop || $is_rest || $is_cluster);

                            $js_type_arg = 'normal';
                            if ($is_drop) $js_type_arg = 'drop';
                            if ($is_rest) $js_type_arg = 'rest';
                            if ($is_cluster) $js_type_arg = 'cluster';

                            // --- LÓGICA DE PREENCHIMENTO ---
                            $reps = trim((string)($s['reps_fixas'] ?? ''));
                            $desc = trim((string)($s['descanso_fixo'] ?? ''));
                            
                            if ($reps === '-' || $reps === 'Falha') $reps = '';
                            if ($desc === '-' || $desc === '90s') $desc = ''; 

                            $categoria = strtolower($s['categoria'] ?? '');
                            $tipo_mec  = strtolower($ex['tipo_mecanica'] ?? '');

                            // 1. Warmup / Feeder
                            if ($categoria === 'warmup') {
                                if (empty($desc)) $desc = '30s';
                                if (empty($reps)) $reps = '15';
                            } 
                            elseif ($categoria === 'feeder') {
                                if (empty($desc)) $desc = '60s';
                                if (empty($reps)) $reps = '6';
                            } 
                            else {
                                // 2. Periodização
                                if ($micro_atual) {
                                    if ($tipo_mec == 'composto' || $tipo_mec == 'multiarticular') {
                                        if (empty($reps) && !empty($micro_atual['reps_compostos'])) {
                                            $reps = $micro_atual['reps_compostos'];
                                        }
                                        if (empty($desc) && !empty($micro_atual['descanso_compostos'])) {
                                            $desc = $micro_atual['descanso_compostos'].'s';
                                        }
                                    } 
                                    elseif ($tipo_mec == 'isolador' || $tipo_mec == 'monoarticular') {
                                        if (empty($reps) && !empty($micro_atual['reps_isoladores'])) {
                                            $reps = $micro_atual['reps_isoladores'];
                                        }
                                        if (empty($desc) && !empty($micro_atual['descanso_isoladores'])) {
                                            $desc = $micro_atual['descanso_isoladores'].'s';
                                        }
                                    }
                                }
                            }

                            // 3. Fallback
                            if(empty($reps)) $reps = "Falha";
                            if(empty($desc)) $desc = "90s";

                            $qtd_series = (int)($s['quantidade'] ?? 1);
                            if ($qtd_series < 1) $qtd_series = 1;

                            for ($i = 1; $i <= $qtd_series; $i++) {
                                
                                $ph_carga = '-';
                                $ph_reps = '-';
                                
                                if (isset($historico_map[$s['id']][$i])) {
                                    $lista_regs = $historico_map[$s['id']][$i];
                                    if (!empty($lista_regs) && isset($lista_regs[0])) {
                                        $d = $lista_regs[0];
                                        $ph_carga = ($d['carga_kg'] * 1);
                                        $ph_reps  = $d['reps_realizadas'];
                                        if (!empty($d['dados_tecnicos'])) {
                                            $dt_json = json_decode($d['dados_tecnicos'], true);
                                            if (isset($dt_json['reps_string'])) $ph_reps = $dt_json['reps_string'];
                                        }
                                    }
                                } 
                                elseif (isset($historico_map[$s['id']][1])) {
                                    $lista_regs = $historico_map[$s['id']][1];
                                    if (!empty($lista_regs) && isset($lista_regs[0])) {
                                        $d = $lista_regs[0];
                                        $ph_carga = ($d['carga_kg'] * 1);
                                        $ph_reps  = $d['reps_realizadas'];
                                    }
                                }

                                // 3. TRADUÇÃO DAS LABELS (O Segredo Acontece Aqui!)
                                if ($has_technique) {
                                    if ($is_drop) $label_serie = traduzirTermo("dropset", $idioma_aluno);
                                    elseif ($is_rest) $label_serie = traduzirTermo("restpause", $idioma_aluno);
                                    elseif ($is_cluster) $label_serie = traduzirTermo("clusterset", $idioma_aluno);
                                } else {
                                    $cat_original = $s['categoria'] ?? 'normal';
                                    $label_serie = traduzirTermo($cat_original, $idioma_aluno);
                                }

                                $indicador_num = ($qtd_series > 1) ? '#'.$i : '1';
                                if ($qtd_series > 1) $label_serie .= " <small style='font-size:0.6rem; opacity:0.7;'>(".$i."/".$qtd_series.")</small>";

                                $row_class = "set-row-input " . ($s['categoria'] ?? '');
                                if ($is_drop) $row_class .= " technique-drop";
                                if ($is_rest) $row_class .= " technique-rest";
                                if ($is_cluster) $row_class .= " technique-cluster";

                                echo '<div class="'.$row_class.'">';

                                    $label_class = ""; 
                                    if ($is_drop)    $label_class = "text-drop";
                                    elseif ($is_rest)    $label_class = "text-rest";
                                    elseif ($is_cluster) $label_class = "text-cluster";
                                    else $label_class = "text-" . ($s['categoria'] ?? ''); 

                                    echo '
                                    <div class="set-num">
                                        <span style="font-size:1.1rem; display:block;">'.$indicador_num.'</span>
                                        <span class="set-type-label '.$label_class.'" style="font-size:0.65rem;">'.$label_serie.'</span>
                                        <small style="color: #666; font-size:0.65rem;">
                                            <i class="fa-solid fa-clock"></i> '.$desc.'
                                        </small>
                                    </div>';

                                    echo '
                                    <div style="text-align:center;">
                                        <span style="color:#fff; font-size:0.9rem; font-weight:bold;">'.$reps.'</span>
                                        <span style="display:block; font-size:0.6rem; color:#aaa;">ALVO</span>
                                    </div>';

                                    // COLUNAS 3 e 4
                                    if ($has_technique) {
                                        $modal_id = "modal_".$s['id']."_".$i;
                                        
                                        // Também podemos traduzir os textos dos botões se o idioma for EN
                                        $txt_registrar = $idioma_aluno == 'en' ? 'REGISTER' : 'REGISTRAR';
                                        $txt_abrir     = $idioma_aluno == 'en' ? 'OPEN' : 'ABRIR';

                                        $btn_text = $txt_registrar;
                                        $icon = "fa-bolt";
                                        $btn_style = "width:100%; height:38px; border-radius:6px; font-size:0.75rem; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:space-between; padding: 0 12px; transition:0.2s;";
                                        
                                        if ($is_drop) { 
                                            $btn_text = "$txt_abrir DROP SET"; 
                                            $btn_style .= "background: rgba(255, 64, 129, 0.15); border: 1px solid #ff4081; color: #ff4081; box-shadow: 0 0 10px rgba(255, 64, 129, 0.1);";
                                            $icon = "fa-layer-group";
                                        }
                                        elseif ($is_rest) { 
                                            $btn_text = "$txt_abrir REST PAUSE"; 
                                            $btn_style .= "background: rgba(0, 230, 118, 0.15); border: 1px solid #00e676; color: #00e676; box-shadow: 0 0 10px rgba(0, 230, 118, 0.1);";
                                            $icon = "fa-stopwatch";
                                        }
                                        elseif ($is_cluster) { 
                                            $btn_text = "$txt_abrir CLUSTER"; 
                                            $btn_style .= "background: rgba(255, 145, 0, 0.15); border: 1px solid #ff9100; color: #ff9100; box-shadow: 0 0 10px rgba(255, 145, 0, 0.1);";
                                            $icon = "fa-cubes";
                                        }

                                        echo '
                                        <div style="grid-column: span 2;">
                                            <button type="button" id="btn_'.$s['id'].'_'.$i.'" class="btn-open-technique" style="'.$btn_style.'" onclick="openTechniqueModal(\''.$modal_id.'\')">
                                                <span><i class="fa-solid '.$icon.'"></i> &nbsp; '.$btn_text.'</span>
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </button>
                                        </div>';

                                        // MODAL
                                        echo '
                                        <div id="'.$modal_id.'" class="tq-modal-overlay">
                                            <div class="tq-modal-content">
                                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">
                                                    <h3 style="margin:0; font-size:1rem; color:#fff;">'.strip_tags($label_serie).'</h3>
                                                    <span onclick="closeTechniqueModal(\''.$modal_id.'\')" style="cursor:pointer; font-size:1.2rem; padding:0 10px;">&times;</span>
                                                </div>
                                                
                                                <div style="margin-bottom:20px; text-align:center; background:rgba(255,255,255,0.05); padding:10px; border-radius:6px;">
                                                    <span style="color:#aaa; font-size:0.8rem;">'.($idioma_aluno == 'en' ? 'TARGET:' : 'META:').' </span> <b style="color:#fff">'.$reps.'</b>
                                                    <span style="color:#aaa; font-size:0.8rem; margin-left:15px;">'.($idioma_aluno == 'en' ? 'REST:' : 'DESC:').' </span> <b style="color:#fff">'.$desc.'</b>
                                                </div>';

                                                if ($is_drop) {
                                                    echo '<label style="font-size:0.7rem; color:#888;">'.($idioma_aluno == 'en' ? 'Main Set' : 'Série Principal').'</label>
                                                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                                                            <input type="number" step="0.5" name="carga['.$s['id'].']['.$i.']" class="input-exec" placeholder="Kg: '.$ph_carga.'">
                                                            <input type="number" name="reps['.$s['id'].']['.$i.']" class="input-exec" placeholder="Reps: '.$ph_reps.'">
                                                        </div>';
                                                    $qtd_drops = (int)$valor_raw;
                                                    for ($d = 1; $d <= $qtd_drops; $d++) {
                                                        echo '<label style="font-size:0.7rem; color:#ff4081;">DROP #'.$d.' (-20%)</label>
                                                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                                                <input type="number" step="0.5" name="carga['.$s['id'].']['.$i.'_drop_'.$d.']" class="input-exec" placeholder="Carga">
                                                                <input type="number" name="reps['.$s['id'].']['.$i.'_drop_'.$d.']" class="input-exec" placeholder="'.($idioma_aluno == 'en' ? 'Failure' : 'Falha').'">
                                                            </div>';
                                                    }
                                                }

                                                if ($is_rest) {
                                                    echo '<label style="font-size:0.7rem; color:#00e676;">'.($idioma_aluno == 'en' ? 'Load & Total Reps' : 'Carga & Reps Totais').'</label>
                                                        <div style="display:flex; gap:10px; margin-bottom:10px;">
                                                            <input type="number" step="0.5" name="carga['.$s['id'].']['.$i.']" class="input-exec" placeholder="Kg: '.$ph_carga.'">
                                                            <input type="text" name="reps['.$s['id'].']['.$i.']" class="input-exec" placeholder="Ex: 10+5+3">
                                                        </div>
                                                        <div style="margin-bottom:15px;">
                                                            <button type="button" onclick="iniciarTimerRest('.(int)$valor_raw.')" style="width:100%; padding:10px; background:rgba(0, 230, 118, 0.15); border:1px solid #00e676; color:#00e676; border-radius:6px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                                                                <i class="fa-solid fa-stopwatch"></i> '.($idioma_aluno == 'en' ? 'START REST' : 'INICIAR DESCANSO').' ('.(int)$valor_raw.'s)
                                                            </button>
                                                        </div>
                                                        <p style="font-size:0.7rem; color:#666;">'.($idioma_aluno == 'en' ? 'Failure > Rest > Repeat > Sum Reps.' : 'Falha > Iniciar Descanso > Repete > Anota Soma.').'</p>';
                                                }

                                                if ($is_cluster) {
                                                    $parts = explode('|', $valor_raw); 
                                                    $tempo_descanso_cluster = isset($parts[2]) ? (int)$parts[2] : 0;
                                                    
                                                    echo '<label style="font-size:0.7rem; color:#ff9100;">'.($idioma_aluno == 'en' ? 'Fixed Load' : 'Carga Fixa').'</label>
                                                        <div style="margin-bottom:15px;">
                                                            <input type="number" step="0.5" name="carga['.$s['id'].']['.$i.']" class="input-exec" placeholder="Kg: '.$ph_carga.'">
                                                        </div>
                                                        
                                                        <label style="font-size:0.7rem; color:#ff9100;">'.($idioma_aluno == 'en' ? 'Blocks' : 'Blocos').' ('.$parts[1].' reps '.($idioma_aluno == 'en' ? 'each' : 'cada').')</label>
                                                        <div style="display:flex; gap:5px; flex-wrap:wrap; margin-bottom:10px;">';
                                                            for($b=1; $b<=$parts[0]; $b++) {
                                                                echo '<div style="flex:1; background:#222; padding:10px; border-radius:4px; text-align:center; border:1px solid #444;">
                                                                        <span style="font-size:0.7rem; color:#888;">B'.$b.'</span><br>
                                                                        <strong style="color:#fff;">'.$parts[1].'</strong>
                                                                    </div>';
                                                            }
                                                    echo '</div>

                                                        <div style="margin-bottom:15px;">
                                                            <button type="button" onclick="iniciarTimerRest('.$tempo_descanso_cluster.')" style="width:100%; padding:10px; background:rgba(255, 145, 0, 0.15); border:1px solid #ff9100; color:#ff9100; border-radius:6px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                                                                <i class="fa-solid fa-stopwatch"></i> '.($idioma_aluno == 'en' ? 'REST BETWEEN BLOCKS' : 'DESCANSO ENTRE BLOCOS').' ('.$tempo_descanso_cluster.'s)
                                                            </button>
                                                        </div>

                                                        <label style="font-size:0.7rem; color:#888;">'.($idioma_aluno == 'en' ? 'Completed Reps (Sum)' : 'Reps Realizadas (Soma)').'</label>
                                                        <input type="text" name="reps['.$s['id'].']['.$i.']" class="input-exec" placeholder="Ex: 4+4+4+3">';
                                                }

                                            echo '  <div style="margin-top:20px;">
                                                    <button type="button" class="btn-gold" style="width:100%; border-radius:50px;" onclick="confirmTechniqueData(\''.$modal_id.'\', \''.$js_type_arg.'\')">'.($idioma_aluno == 'en' ? 'SAVE DATA' : 'SALVAR DADOS').'</button>
                                                </div>
                                            </div>
                                        </div>';

                                    } else {
                                        // NORMAL
                                        $input_name_carga = "carga[".$s['id']."][".$i."]"; 
                                        $input_name_reps  = "reps[".$s['id']."][".$i."]";
                                        echo '
                                        <div>
                                            <input type="number" step="0.5" name="'.$input_name_carga.'" class="input-exec" placeholder="Ant: '.$ph_carga.'" inputmode="decimal">
                                        </div>
                                        <div style="display:flex; align-items:center; gap:5px;">
                                            <input type="number" name="'.$input_name_reps.'" class="input-exec" placeholder="Ant: '.$ph_reps.'" inputmode="numeric">
                                        </div>';
                                    }

                                echo '</div>'; // Fecha set-row-input
                            }
                        }
                    } else {
                        echo '<p style="color:#666; padding:10px;">'.($idioma_aluno == 'en' ? 'No sets registered.' : 'Sem séries cadastradas.').'</p>';
                    }

                    echo '</div>'; // Fim exec-card
                }

                if ($bloco['tipo'] === 'grupo') {
                    echo '</div>'; // Fecha exec-agrupamento
                }
            } 

        } else {
            echo '<p style="text-align:center; margin-top:20px; color:#888;">Nenhum exercício encontrado nesta divisão.</p>';
        }

        echo '  </div> 

                <button type="submit" class="btn-finish" onclick="return confirm(\'Tem certeza que deseja finalizar este treino? Todos os dados serão salvos.\')">
                    <i class="fa-solid fa-check"></i> FINALIZAR TREINO
                </button>
              </form>';
        break;



        
    case 'treinos':
        require_once '../config/db_connect.php'; 
        require_once '../helpers/tradutor_treino.php'; // 1. Chama o helper de tradução
        
        // Verifica Sessão
        if (!isset($_SESSION['user_id'])) { echo "Sessão expirada."; break; }
        
        $aluno_id = $_SESSION['user_id'];
        $hoje = date('Y-m-d');
        
        // 2. Busca a preferência de idioma da sessão (Padrão: pt)
        $idioma_aluno = $_SESSION['pref_idioma'] ?? 'pt';

        session_write_close();

        // A. BUSCA TODOS OS TREINOS
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
        $treino_req = filter_input(INPUT_GET, 'treino_id', FILTER_SANITIZE_NUMBER_INT);
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
                
                $stmt = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero ASC");
                $stmt->execute([$per['id']]);
                $micros = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $micro_req = filter_input(INPUT_GET, 'micro_id', FILTER_SANITIZE_NUMBER_INT);
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

        // --- E. OTIMIZAÇÃO: BUSCA EM LOTE ---
        $exercicios_por_divisao = [];
        $series_por_exercicio = [];

        if (!empty($divisoes)) {
            $div_ids = array_column($divisoes, 'id');
            $placeholders_div = implode(',', array_fill(0, count($div_ids), '?'));

            $stmt_ex = $pdo->prepare("SELECT * FROM exercicios WHERE divisao_id IN ($placeholders_div) ORDER BY ordem ASC");
            $stmt_ex->execute($div_ids);
            $todos_exercicios = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($todos_exercicios)) {
                $ex_ids = [];
                foreach ($todos_exercicios as $ex) {
                    $exercicios_por_divisao[$ex['divisao_id']][] = $ex;
                    $ex_ids[] = $ex['id'];
                }

                if (!empty($ex_ids)) {
                    $placeholders_ex = implode(',', array_fill(0, count($ex_ids), '?'));
                    $stmt_series = $pdo->prepare("SELECT * FROM series WHERE exercicio_id IN ($placeholders_ex) ORDER BY id ASC");
                    $stmt_series->execute($ex_ids);
                    $todas_series = $stmt_series->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($todas_series as $s) {
                        $series_por_exercicio[$s['exercicio_id']][] = $s;
                    }
                }
            }
        }

        // --- RENDERIZAÇÃO ---
        echo '<section id="meu-treino" class="fade-in">';
        
        echo '<div class="workout-header-main">
                <h2 class="workout-title">'.$treino['nome'].'</h2>
                <div class="meta-tags">
                    <span class="tag">'.strtoupper($treino['nivel_plano']).'</span>
                    '.($meta_treino ? '<span class="tag outline">'.$meta_treino.'</span>' : '').'
                </div>
              </div>';

        // Timeline Periodização
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

            if ($micro_atual) {
                $reps_comp = $micro_atual['reps_compostos'] ?: '-';
                $desc_comp = $micro_atual['descanso_compostos'] ? $micro_atual['descanso_compostos'].'s' : '-';
                $reps_iso  = $micro_atual['reps_isoladores'] ?: '-';
                $desc_iso  = $micro_atual['descanso_isoladores'] ? $micro_atual['descanso_isoladores'].'s' : '-';

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

        // Abas
        echo '<div class="division-tabs">';
        $first = true;
        foreach ($divisoes as $d) {
            $act = $first ? 'active' : '';
            echo '<button class="tab-btn '.$act.'" onclick="abrirTreino(event, \'div_'.$d['id'].'\')">TREINO '.$d['letra'].'</button>';
            $first = false;
        }
        echo '</div>';

        // Conteúdo
        $first = true;
        foreach ($divisoes as $d) {
            $display = $first ? 'block' : 'none';
            $exercicios_raw = $exercicios_por_divisao[$d['id']] ?? [];

            // Agrupamento
            $lista_final = [];
            $grupos_temp = []; 

            if ($exercicios_raw) {
                foreach ($exercicios_raw as $ex) {
                    $hash = $ex['agrupamento_hash'];
                    if ($hash) {
                        if (!isset($grupos_temp[$hash])) {
                            $idx = count($lista_final);
                            $lista_final[$idx] = ['tipo' => 'grupo', 'itens' => []];
                            $grupos_temp[$hash] = $idx;
                        }
                        $lista_final[$grupos_temp[$hash]]['itens'][] = $ex;
                    } else {
                        $lista_final[] = ['tipo' => 'single', 'itens' => [$ex]];
                    }
                }
            }

            echo '<div id="div_'.$d['id'].'" class="treino-content" style="display:'.$display.'">';
            
            if (!empty($lista_final)) {
                foreach ($lista_final as $bloco) {
                    if ($bloco['tipo'] === 'grupo') {
                        $qtd = count($bloco['itens']);
                        $label = ($qtd === 2) ? 'BI-SET' : 'TRI-SET';
                        echo '<div class="agrupamento-wrapper">';
                        echo '<span class="agrupamento-badge">'.$label.'</span>';
                    }

                    foreach ($bloco['itens'] as $ex) {
                        $series = $series_por_exercicio[$ex['id']] ?? [];

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
                                        // 1. Pega valores da SÉRIE
                                        $reps = $s['reps_fixas'];
                                        $desc = $s['descanso_fixo'];
                                        $categoria = strtolower($s['categoria']);

                                        // 2. Regras de Warmup/Feeder (Prioridade Total)
                                        if ($categoria === 'warmup') {
                                            if (empty($desc)) $desc = '30s';
                                            if (empty($reps) || $reps == '-') $reps = '15';
                                        } 
                                        elseif ($categoria === 'feeder') {
                                            if (empty($desc)) $desc = '60s';
                                            if (empty($reps) || $reps == '-') $reps = '6';
                                        } 
                                        else {
                                            // 3. Regra de Periodização
                                            if ($micro_atual) {
                                                if ($ex['tipo_mecanica'] == 'composto') {
                                                    if (empty($reps) && !empty($micro_atual['reps_compostos'])) {
                                                        $reps = $micro_atual['reps_compostos'];
                                                    }
                                                    if (empty($desc) && !empty($micro_atual['descanso_compostos'])) {
                                                        $desc = $micro_atual['descanso_compostos'].'s';
                                                    }
                                                } 
                                                elseif ($ex['tipo_mecanica'] == 'isolador') {
                                                    if (empty($reps) && !empty($micro_atual['reps_isoladores'])) {
                                                        $reps = $micro_atual['reps_isoladores'];
                                                    }
                                                    if (empty($desc) && !empty($micro_atual['descanso_isoladores'])) {
                                                        $desc = $micro_atual['descanso_isoladores'].'s';
                                                    }
                                                }
                                            }
                                        }

                                        // 4. Fallback Final
                                        if(empty($reps) || $reps == '-') $reps = "Falha";
                                        if(empty($desc)) $desc = "-";

                                        // Técnicas e Tradução
                                        $tecnica = strtolower(trim($s['tecnica'] ?? 'normal'));
                                        $valor   = $s['tecnica_valor'] ?? '';
                                        
                                        $cssClass = 'set-item ' . $s['categoria'];
                                        
                                        // A MÁGICA ACONTECE AQUI:
                                        $cat_traduzida = traduzirTermo($s['categoria'], $idioma_aluno);
                                        $labelSet = $s['quantidade'].'x '.$cat_traduzida;
                                        
                                        $extraInfo = '';

                                        if ($tecnica === 'dropset') {
                                            $cssClass .= ' technique-drop';
                                            $labelSet = $s['quantidade'].'x '.traduzirTermo('dropset', $idioma_aluno);
                                            $extraInfo = '<div class="tech-info">+ '.$valor.' Drops</div>';
                                        } elseif ($tecnica === 'restpause') {
                                            $cssClass .= ' technique-rest';
                                            $labelSet = $s['quantidade'].'x '.traduzirTermo('restpause', $idioma_aluno);
                                            $str_pausa = $idioma_aluno == 'en' ? 'Intra Pause:' : 'Pausa Intra:';
                                            $extraInfo = '<div class="tech-info">'.$str_pausa.' '.$valor.'s</div>';
                                        } elseif ($tecnica === 'clusterset') {
                                            $cssClass .= ' technique-cluster';
                                            $labelSet = $s['quantidade'].'x '.traduzirTermo('clusterset', $idioma_aluno);
                                            $parts = explode('|', $valor);
                                            if(count($parts) === 3) {
                                                if ($idioma_aluno == 'en') {
                                                    $extraInfo = '<div class="tech-info">'.$parts[0].' blocks of '.$parts[1].' reps ('.$parts[2].'s)</div>';
                                                } else {
                                                    $extraInfo = '<div class="tech-info">'.$parts[0].' blocos de '.$parts[1].' reps ('.$parts[2].'s)</div>';
                                                }
                                            }
                                        }

                                        echo '<div class="'.$cssClass.'">
                                                <div class="set-top">'.$labelSet.'</div>
                                                <div class="set-bottom">
                                                    <span style="font-size:1.1rem; font-weight:bold;">'.$reps.'</span>
                                                    <small><i class="fa-solid fa-clock" style="font-size:0.6rem;"></i> '.$desc.'</small>
                                                </div>
                                                '.$extraInfo.'
                                              </div>';
                                    }
                                echo '</div>
                                </div>
                              </div>';
                    }

                    if ($bloco['tipo'] === 'grupo') {
                        echo '</div>'; // Fecha agrupamento
                    }
                }

            } else {
                echo '<div class="empty-day">Sem exercícios cadastrados</div>';
            }
            echo '</div>';
            $first = false;
        }

        echo '</section>';
        
        $pdo = null;
        break;

    
    case 'historico':

    if ($plano_aluno === 'start') {
        $titulo_bloqueio = "Histórico Detalhado";
        $texto_bloqueio  = "A análise de evolução de cargas e histórico completo é exclusiva para alunos <strong>PRO</strong>.";
        include '../includes/aviso_bloqueio.php';
        break; 
    }
    require_once '../config/db_connect.php';
    require_once '../helpers/tradutor_treino.php'; // Chama o helper de tradução

    $aluno_id = $_SESSION['user_id'];
    $data_ref = $_GET['data_ref'] ?? null;
    
    // Busca a preferência de idioma da sessão (Padrão: pt)
    $idioma_aluno = $_SESSION['pref_idioma'] ?? 'pt';

    // --- MODO 1: DETALHES DO TREINO ---
    if ($data_ref) {
        // Infos Gerais
        $stmt = $pdo->prepare("SELECT DISTINCT t.nome as nome_treino, td.letra 
                      FROM treino_historico th
                      JOIN treinos t ON th.treino_id = t.id
                      JOIN treino_divisoes td ON th.divisao_id = td.id
                      WHERE th.aluno_id = :uid AND th.data_treino = :dt");
        $stmt->execute(['uid' => $aluno_id, 'dt' => $data_ref]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        // Detalhes
        $stmt = $pdo->prepare("SELECT th.*, e.nome_exercicio, s.categoria, s.tecnica 
                          FROM treino_historico th
                          JOIN exercicios e ON th.exercicio_id = e.id
                          LEFT JOIN series s ON COALESCE(th.serie_id, th.serie_numero) = s.id 
                          WHERE th.aluno_id = :uid AND th.data_treino = :dt
                          ORDER BY e.ordem ASC, th.id ASC");
        $stmt->execute(['uid' => $aluno_id, 'dt' => $data_ref]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupamento
        $treino_agrupado = [];
        foreach ($registros as $reg) {
            $id_ex = $reg['exercicio_id'];
            if (!isset($treino_agrupado[$id_ex])) {
                $treino_agrupado[$id_ex] = ['nome' => $reg['nome_exercicio'], 'series' => []];
            }
            $treino_agrupado[$id_ex]['series'][] = $reg;
        }

        echo '<section class="fade-in">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <button onclick="carregarConteudo(\'historico\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <div>
                            <span style="color:#888; font-size:0.8rem; text-transform:uppercase;">'.($idioma_aluno == 'en' ? 'Viewing' : 'Visualizando').'</span>
                            <h2 style="margin:0; color:#fff; font-size:1.2rem;">'.($idioma_aluno == 'en' ? 'WORKOUT' : 'TREINO').' '.($info['letra'] ?? '?').'</h2>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <button id="btn-editar-hist" onclick="alternarEdicaoHistorico()" style="width:40px; height:40px; border-radius:50%; background:rgba(255, 186, 66, 0.1); border:1px solid var(--gold); color:var(--gold); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.3s;">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button onclick="deletarHistorico(\''.$data_ref.'\')" style="width:40px; height:40px; border-radius:50%; background:rgba(255,66,66,0.1); border:1px solid #ff4242; color:#ff4242; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.3s;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
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
                
                if (empty($treino_agrupado)) echo '<p style="text-align:center; color:#666;">'.($idioma_aluno == 'en' ? 'No data found.' : 'Nenhum dado encontrado.').'</p>';

                foreach ($treino_agrupado as $ex_id => $dados) {
                    echo '<div class="hist-exercise-group">
                            <div class="hist-ex-header">
                                <i class="fa-solid fa-dumbbell"></i>
                                <span>'.$dados['nome'].'</span>
                            </div>
                            
                            <table class="hist-sets-table" style="border-collapse: separate; border-spacing: 0 4px;">
                                <thead>
                                    <tr>
                                        <th width="20%">#</th>
                                        <th width="25%">'.($idioma_aluno == 'en' ? 'TYPE' : 'TIPO').'</th>
                                        <th width="25%">KG</th>
                                        <th width="30%">REPS</th>
                                    </tr>
                                </thead>
                                <tbody>';
                                
                                foreach ($dados['series'] as $serie) {
                                    $id_hist = $serie['id'];
                                    
                                    // Pega os dados técnicos JSON (se existirem)
                                    $dados_tecnicos = !empty($serie['dados_tecnicos']) ? json_decode($serie['dados_tecnicos'], true) : [];
                                    
                                    // Pega a definição original da série
                                    $tecnica_original = strtolower($serie['tecnica'] ?? '');

                                    // -- 1. Definição Padrão (Normal) --
                                    $cat_visual = $serie['categoria'] ? strtolower($serie['categoria']) : 'work';
                                    
                                    // Tradução do Termo Padrão
                                    $label_visual = traduzirTermo($cat_visual, $idioma_aluno);
                                    
                                    // Número Padrão
                                    $num_display = '#'.($serie['numero_serie'] > 0 ? $serie['numero_serie'] : '-');
                                    $num_style = "color:#666; font-weight:bold;";
                                    $row_style = ""; 
                                    
                                    // Reps Padrão
                                    $reps_display = $serie['reps_realizadas'];

                                    // -- 2. Lógica para DROP SET --
                                    if ($tecnica_original === 'dropset') {
                                        $cat_visual = 'technique-drop'; // Rosa
                                        $label_visual = traduzirTermo('dropset', $idioma_aluno);
                                        
                                        // A. É um Drop Específico (tem índice no JSON)
                                        if (isset($dados_tecnicos['drop_index'])) {
                                            $idx_drop = $dados_tecnicos['drop_index'];
                                            $num_display = '<i class="fa-solid fa-turn-up fa-rotate-90" style="margin-right:5px; font-size:0.7rem; opacity:0.7;"></i> DROP '.$idx_drop;
                                            $num_style = "color:#ff4081; font-weight:bold; font-size:0.8rem; padding-left:10px;";
                                            $row_style = "background: linear-gradient(90deg, rgba(255, 64, 129, 0.1) 0%, rgba(0,0,0,0) 100%);";
                                            
                                            // MUDANÇA AQUI: Remove o badge de tipo para os drops filhos
                                            $label_visual = ''; 
                                            $cat_visual = ''; 
                                        } 
                                        // B. É a Série Principal do Drop
                                        else {
                                            // Mantém o número #1, mas pinta de Rosa e mantém o Label Traduzido
                                            $num_style = "color:#ff4081; font-weight:bold;";
                                        }
                                    }

                                    // -- 3. Lógica para REST PAUSE --
                                    elseif ($tecnica_original === 'restpause' || (isset($dados_tecnicos['tipo']) && $dados_tecnicos['tipo'] === 'restpause')) {
                                        $cat_visual = 'technique-rest'; // Verde
                                        $label_visual = traduzirTermo('restpause', $idioma_aluno);
                                        if (!empty($dados_tecnicos['reps_string'])) {
                                            $reps_display = $dados_tecnicos['reps_string'];
                                        }
                                    }

                                    // -- 4. Lógica para CLUSTER SET --
                                    elseif ($tecnica_original === 'clusterset' || (isset($dados_tecnicos['tipo']) && $dados_tecnicos['tipo'] === 'clusterset')) {
                                        $cat_visual = 'technique-cluster'; // Laranja
                                        $label_visual = traduzirTermo('clusterset', $idioma_aluno);
                                        
                                        if (!empty($dados_tecnicos['reps_string'])) {
                                            $reps_display = $dados_tecnicos['reps_string'];
                                        }
                                    }

                                    echo '<tr style="'.$row_style.'">
                                            <td style="'.$num_style.'">'.$num_display.'</td>
                                            
                                            <td>';
                                    
                                    // Só exibe o badge se tiver label (assim os drops ficam vazios).
                                    if ($label_visual !== '') {
                                        echo '<span class="badge-set-type '.$cat_visual.'">'.$label_visual.'</span>';
                                    }
                                            
                                    echo '  </td>
                                            
                                            <td class="editable-cell" data-id="'.$id_hist.'" data-type="carga">
                                                <span class="view-val" style="color:#fff; font-weight:bold;">'.($serie['carga_kg']*1).'</span>
                                                <input type="number" step="0.1" class="edit-input" value="'.($serie['carga_kg']*1).'" style="display:none; width:50px; background:#222; border:1px solid #444; color:#fff; padding:5px; border-radius:4px;">
                                            </td>

                                            <td class="editable-cell" data-id="'.$id_hist.'" data-type="reps">
                                                <span class="view-val" style="color:#fff;">'.$reps_display.'</span>
                                                <input type="text" class="edit-input" value="'.$reps_display.'" style="display:none; width:60px; background:#222; border:1px solid #444; color:#fff; padding:5px; border-radius:4px;">
                                            </td>
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

    // --- MODO 2: LISTA (HISTÓRICO GERAL) ---
    $sql_lista = "SELECT th.data_treino, t.nome as nome_treino, td.letra
                  FROM treino_historico th
                  LEFT JOIN treinos t ON th.treino_id = t.id
                  LEFT JOIN treino_divisoes td ON th.divisao_id = td.id
                  WHERE th.aluno_id = :uid
                  GROUP BY th.data_treino, t.nome, td.letra
                  ORDER BY th.data_treino DESC";
                  
    $stmt = $pdo->prepare($sql_lista);
    $stmt->execute(['uid' => $aluno_id]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<section id="historico-lista" class="fade-in">
            <header class="dash-header">
                <h1>'.($idioma_aluno == 'en' ? 'MY' : 'MEU').' <span class="highlight-text">'.($idioma_aluno == 'en' ? 'HISTORY' : 'HISTÓRICO').'</span></h1>
            </header>';

    if (empty($historico)) {
        echo '<div class="empty-state">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h2>'.($idioma_aluno == 'en' ? 'No workout recorded' : 'Nenhum treino registrado').'</h2>
                <p>'.($idioma_aluno == 'en' ? 'Complete your first workout to see the history.' : 'Realize seu primeiro treino para ver o histórico.').'</p>
              </div>';
    } else {
        echo '<div class="history-list">';
        foreach ($historico as $h) {
            $data_obj = new DateTime($h['data_treino']);
            $dia = $data_obj->format('d');
            
            // Meses em português x inglês
            if ($idioma_aluno == 'en') {
                $meses = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            } else {
                $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            }
            $mes_txt = $meses[(int)$data_obj->format('m') - 1];
            
            $hora = $data_obj->format('H:i');
            
            $treino_arquivado_txt = $idioma_aluno == 'en' ? 'Archived Workout' : 'Treino Arquivado';
            $treino_nome = $h['nome_treino'] ? $h['nome_treino'] : $treino_arquivado_txt;
            
            $letra = $h['letra'] ? $h['letra'] : '?';
            $treino_letra_txt = $idioma_aluno == 'en' ? 'Workout' : 'Treino';

            echo '<div class="history-card" onclick="carregarConteudo(\'historico&data_ref='.$h['data_treino'].'\')">
                    <div class="hist-date-box">
                        <span class="hist-day">'.$dia.'</span>
                        <span class="hist-month">'.$mes_txt.'</span>
                    </div>
                    <div class="hist-info">
                        <span class="hist-title">'.$treino_letra_txt.' '.$letra.'</span>
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
        
        // Garante que não dê erro se o usuário ainda não tiver a preferência salva
        $pref_idioma = isset($user['pref_idioma']) ? $user['pref_idioma'] : 'pt';

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

                        <div>
                            <label class="input-label">Idioma dos Treinos</label>
                            <select name="pref_idioma" class="input-field">
                                <option value="pt" '. ($pref_idioma == 'pt' ? 'selected' : '') .'>Português (Brasil)</option>
                                <option value="en" '. ($pref_idioma == 'en' ? 'selected' : '') .'>English (Nomenclatura Técnica)</option>
                            </select>
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

        // --- DEFINIÇÃO DAS FUNÇÕES AUXILIARES COM ESTRUTURA INLINE EDIT ---
        $renderMeasure = function($label, $field, $val) {
            $display = $val ? $val : '-';
            return '<div class="m-box editable-cell" data-field="'.$field.'">
                        <span>'.$label.'</span>
                        <strong class="view-val">'.$display.'</strong>
                        <input type="number" step="0.01" class="edit-input admin-input" value="'.$val.'" style="display:none; width: 65px; padding: 2px 5px; text-align:center; margin-top:5px; background: rgba(0,0,0,0.2); border: 1px solid #444; color:#fff; border-radius:4px;">
                    </div>';
        };

        $renderMeasureDouble = function($label, $field1, $val1, $field2, $val2) {
            $d1 = $val1 ? $val1 : '-';
            $d2 = $val2 ? $val2 : '-';
            return '<div class="m-box-double">
                        <span>'.$label.'</span>
                        <div class="vals">
                            <div class="editable-cell" data-field="'.$field1.'" style="display:inline-block;">
                                <strong class="view-val">'.$d1.'</strong>
                                <input type="number" step="0.01" class="edit-input admin-input" value="'.$val1.'" style="display:none; width: 50px; padding: 2px; text-align:center; background: rgba(0,0,0,0.2); border: 1px solid #444; color:#fff; border-radius:4px;">
                            </div>
                            <small class="view-val">/</small>
                            <div class="editable-cell" data-field="'.$field2.'" style="display:inline-block;">
                                <strong class="view-val">'.$d2.'</strong>
                                <input type="number" step="0.01" class="edit-input admin-input" value="'.$val2.'" style="display:none; width: 50px; padding: 2px; text-align:center; background: rgba(0,0,0,0.2); border: 1px solid #444; color:#fff; border-radius:4px;">
                            </div>
                        </div>
                    </div>';
        };
        // -------------------------------------------------------

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
            echo '<div class="empty-state">
                    <div class="empty-icon-circle"><i class="fa-solid fa-weight-scale"></i></div>
                    <h2 style="color: #fff; font-family: Orbitron, sans-serif; font-size: 1.5rem; margin: 0 0 10px 0;">Comece sua Jornada</h2>
                    <p style="color: #888; font-size: 0.95rem; margin: 0;">Registre sua primeira avaliação.</p>
                </div>';
        } else {
            echo '<div class="eval-timeline-wrapper">';

            foreach ($avaliacoes as $av) {
                $stmt_arq = $pdo->prepare("SELECT * FROM avaliacoes_arquivos WHERE avaliacao_id = ?");
                $stmt_arq->execute([$av['id']]);
                $arquivos = $stmt_arq->fetchAll(PDO::FETCH_ASSOC);

                $dia = date('d', strtotime($av['data_avaliacao']));
                $mes = date('M', strtotime($av['data_avaliacao']));
                $card_id = 'eval_card_' . $av['id'];

                echo '<div class="accordion-card" id="'.$card_id.'">
                        
                        <div class="accordion-header" onclick="toggleAccordion(\''.$card_id.'\')">
                            <div class="date-badge"><span class="d-day">'.$dia.'</span><span class="d-month">'.$mes.'</span></div>
                            <div class="header-info">
                                <div class="info-main">
                                    <span class="weight-display">'.($av['peso_kg'] * 1).' <small>kg</small></span>
                                    '.($av['percentual_gordura'] ? '<span class="bf-tag bf-tag-display">BF '.($av['percentual_gordura']*1).'%</span>' : '').'
                                </div>
                            </div>
                            <div class="accordion-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>

                        <div class="accordion-body" style="display: none;">
                            <div class="body-padding">
                                
                                <div class="stats-tiles">
                                    <div class="tile"><small>IMC</small><strong class="imc-display">'.($av['imc'] ?: '-').'</strong></div>
                                    <div class="tile"><small>M. MAGRA</small><strong class="magra-display">'.($av['massa_magra_kg'] ? $av['massa_magra_kg'].'kg' : '-').'</strong></div>
                                    <div class="tile"><small>M. GORDA</small><strong class="gorda-display">'.($av['massa_gorda_kg'] ? $av['massa_gorda_kg'].'kg' : '-').'</strong></div>
                                </div>

                                <div class="measures-container">
                                    <div class="m-group">
                                        <span class="mg-label">TRONCO E MEDIDAS GERAIS</span>
                                        <div class="mg-grid">
                                            '.$renderMeasure('Peso (kg)', 'peso_kg', $av['peso_kg']).'
                                            '.$renderMeasure('Altura (cm)', 'altura_cm', $av['altura_cm']).'
                                            '.$renderMeasure('Pescoço', 'pescoco', $av['pescoco']).'
                                            '.$renderMeasure('Ombros', 'ombro', $av['ombro']).'
                                            '.$renderMeasure('Tórax', 'torax_relaxado', $av['torax_relaxado']).'
                                            '.$renderMeasure('Cintura', 'cintura', $av['cintura']).'
                                            '.$renderMeasure('Abdômen', 'abdomen', $av['abdomen']).'
                                            '.$renderMeasure('Quadril', 'quadril', $av['quadril']).'
                                        </div>
                                    </div>

                                    <div class="m-group">
                                        <span class="mg-label">MEMBROS (D / E)</span>
                                        <div class="mg-grid-wide">
                                            '.$renderMeasureDouble('Braço (Rel)', 'braco_dir_relaxado', $av['braco_dir_relaxado'], 'braco_esq_relaxado', $av['braco_esq_relaxado']).'
                                            '.$renderMeasureDouble('Braço (Con)', 'braco_dir_contraido', $av['braco_dir_contraido'], 'braco_esq_contraido', $av['braco_esq_contraido']).'
                                            '.$renderMeasureDouble('Coxa', 'coxa_dir', $av['coxa_dir'], 'coxa_esq', $av['coxa_esq']).'
                                            '.$renderMeasureDouble('Panturrilha', 'panturrilha_dir', $av['panturrilha_dir'], 'panturrilha_esq', $av['panturrilha_esq']).'
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer-actions" style="margin-top:30px; text-align:center; flex-direction: column; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px; display:flex; justify-content:center; gap: 10px;">
                                    
                                    <button class="btn-gold id="btn-editar-av-'.$av['id'].'" onclick="alternarEdicaoAvaliacao('.$av['id'].', this)">
                                        <i class="fa-solid fa-pen"></i> Editar Medidas
                                    </button>

                                    <a href="actions/avaliacao_delete.php?id='.$av['id'].'" class="btn-danger-outline" onclick="return confirm(\'Apagar avaliação permanentemente?\');">
                                        <i class="fa-solid fa-trash-can"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>';
            }
            echo '</div>'; 
        }
        echo '</section>';
        break;


    // --- TELA 2: MEU PROGRESSO (COM DELTAS E GRÁFICO FIX) ---
    case 'progresso':

        if ($plano_aluno === 'start') {
            $titulo_bloqueio = "Progresso de Avaliações";
            $texto_bloqueio  = "A comparação de medidas entre avaliações e visualização de gráficos é exclusivo para alunos <strong>PRO</strong>.";
            include '../includes/aviso_bloqueio.php';
            break; 
        }
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
            echo '<div class="empty-state">
                    <div class="empty-icon-circle">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    
                    <h2 style="color: #fff; font-family: Orbitron, sans-serif; font-size: 1.3rem; margin: 0 0 10px 0; letter-spacing: 1px;">
                        Dados Insuficientes
                    </h2>
                    
                    <p style="color: #999; font-size: 0.9rem; margin: 0; max-width: 400px; line-height: 1.5;">
                        Registre pelo menos 2 avaliações para desbloquear a análise comparativa.
                    </p>
                    
                    <button onclick="carregarConteudo(\'avaliacoes\')" class="btn-gold" style="margin-top:20px;">
                        REGISTRAR AGORA
                    </button>
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
                    <p>Cadastre um plano alimentar para acessar esse menu.</p>
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
    
    if ($plano_aluno === 'start') {
        $titulo_bloqueio = "Geração de PDF";
        $texto_bloqueio  = "Gerar PDF de treinos, periodização e avaliação física é exclusivo para alunos <strong>PRO</strong>.";
        include '../includes/aviso_bloqueio.php';
        break; 
    }

    require_once '../config/db_connect.php';
    $aluno_id = $_SESSION['user_id'];
    
    // Pega o idioma da sessão, caso contrário assume português
    $pref_idioma = $_SESSION['pref_idioma'] ?? 'pt';

    // 1. Busca o Plano Ativo
    $stmt = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY criado_em DESC LIMIT 1");
    $stmt->execute([$aluno_id]);
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plano) {
        echo '<section class="empty-state"><h2>Sem plano ativo</h2></section>';
        break;
    }

    // -------------------------------------------------------------
    // BLOCO DE PERIODIZAÇÃO (MANTIDO)
    // -------------------------------------------------------------
    $micros_para_pdf = [];
    if ($plano['nivel_plano'] !== 'basico') {
        $stmt_per = $pdo->prepare("SELECT id FROM periodizacoes WHERE treino_id = ?");
        $stmt_per->execute([$plano['id']]);
        $per = $stmt_per->fetch(PDO::FETCH_ASSOC);

        if ($per) {
            $stmt_micro = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero ASC");
            $stmt_micro->execute([$per['id']]);
            $micros_para_pdf = $stmt_micro->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    $json_micros = htmlspecialchars(json_encode($micros_para_pdf), ENT_QUOTES, 'UTF-8');

    // -------------------------------------------------------------
    // NOVO BLOCO: BUSCA AVALIAÇÕES FÍSICAS
    // -------------------------------------------------------------
    $stmt_av = $pdo->prepare("SELECT * FROM avaliacoes WHERE aluno_id = ? ORDER BY data_avaliacao DESC LIMIT 5");
    $stmt_av->execute([$aluno_id]);
    $avaliacoes_lista = $stmt_av->fetchAll(PDO::FETCH_ASSOC);
    $json_avaliacoes = htmlspecialchars(json_encode($avaliacoes_lista), ENT_QUOTES, 'UTF-8');
    // -------------------------------------------------------------

    // 2. Busca Divisões e Exercícios (MANTIDO)
    $stmt_div = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
    $stmt_div->execute([$plano['id']]);
    $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

    // Mapa de Dias (MANTIDO)
    $mapa_dias = [
        1 => 'Segunda-Feira', 2 => 'Terça-Feira', 3 => 'Quarta-Feira',
        4 => 'Quinta-Feira', 5 => 'Sexta-Feira', 6 => 'Sábado',
        7 => 'Domingo', 0 => 'Domingo'
    ];

    $dias_treino = [];
    if (!empty($plano['dias_semana'])) {
        $decoded = json_decode($plano['dias_semana'], true);
        if (is_array($decoded)) { $dias_treino = $decoded; }
    }

    $dados_treinos = [];
    $total_divisoes = count($divisoes);

    foreach ($divisoes as $index_div => $div) {
        $dias_desta_divisao = [];
        if ($total_divisoes > 0 && !empty($dias_treino)) {
            foreach ($dias_treino as $k => $dia_num) {
                if (($k % $total_divisoes) == $index_div) {
                    if (isset($mapa_dias[$dia_num])) {
                        $dias_desta_divisao[] = $mapa_dias[$dia_num];
                    }
                }
            }
        }
        $dia_exibicao = !empty($dias_desta_divisao) ? implode(' / ', $dias_desta_divisao) : 'TREINO ' . $div['letra'];

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
            'dia_real' => $dia_exibicao,
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
            <input type="hidden" id="json-dados-micros" value="'.$json_micros.'">
            <input type="hidden" id="json-dados-avaliacoes" value="'.$json_avaliacoes.'">
            <input type="hidden" id="plano-nome-atual" value="'.$plano['nome'].'">

            <div class="pdf-action-card" onclick="abrirModalPDF()">
                <div class="pac-icon"><i class="fa-solid fa-file-pdf"></i></div>
                <div class="pac-info">
                    <h3>Gerar Arquivos PDF</h3>
                    <p>Ficha de Treino, Periodização e Avaliação Física.</p>
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

                    <div style="text-align:left; margin-bottom:15px;">
                        <label class="input-label">Tipo de Arquivo</label>
                        <select id="pdf_tipo_arquivo" class="modal-input" style="cursor:pointer;">
                            <option value="treino">Ficha de Treino (Retrato)</option>
                            <option value="periodizacao">Periodização (Paisagem)</option>
                            <option value="avaliacao">Avaliação Física (Retrato)</option>
                        </select>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                        
                        <div>
                            <label class="input-label">Tema</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="pdf_theme_color" value="#500808" style="width:40px; height:40px; border:none; border-radius:5px; cursor:pointer;">
                                <span style="font-size:0.8rem; color:#888;">Cabeçalhos</span>
                            </div>
                        </div>

                        <div>
                            <label class="input-label">Fundo</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="pdf_bg_color" value="#000000" style="width:40px; height:40px; border:none; border-radius:5px; cursor:pointer;">
                                <span style="font-size:0.8rem; color:#888;">Folha</span>
                            </div>
                        </div>

                        <div>
                            <label class="input-label">Bordas</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" id="pdf_border_color" value="#ff0303" style="width:40px; height:40px; border:none; border-radius:5px; cursor:pointer;">
                                <span style="font-size:0.8rem; color:#888;">Linhas</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="pref_idioma_pdf" value="'.$pref_idioma.'">
                    </div>

                    <div class="modal-actions">
                        <button class="btn-gold" onclick="gerarPDFSelecionado()" style="flex: 2; display:flex; align-items:center; justify-content:center; gap:8px;">
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

            <div id="template-periodizacao-full" style="display: none; width: 330mm; min-height: 190mm; background: black; color: #fff;">
                <div class="pdf-sheet landscape" style="padding: 10px; height: 100%; box-sizing: border-box; display: flex; flex-direction: column;">
                    
                    <div id="pdf-header-perio" style="display: flex; align-items: flex-end; justify-content: space-between; padding-bottom: 10px; margin-bottom: 15px; border-bottom: 4px solid #fff;">
                        <div class="sh-logo">
                            <img src="assets/img/icones/icon-nav.png" style="height: 50px; object-fit: contain;">
                        </div>
                        <div style="text-align: center; flex: 1;">
                            <h1 id="render-aluno-nome-perio" style="font-family: \'Lobster\', cursive; font-size: 35px; margin: 0; text-decoration: none; font-weight: 500;">Nome do Aluno</h1>
                            <span id="render-plano-nome-perio" style="font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #fff; font-weight: bold;">MACROCICLO</span>
                        </div>
                        <div class="sh-meta" style="text-align: right;">
                            <span style="color: #fff; font-size: 10px; font-weight: bold;">PERIODIZAÇÃO</span>
                        </div>
                    </div>

                    <div id="pdf-container-micros">
                        </div>

                    <div class="sheet-footer">
                        Gerado por Ryan Coach App
                    </div>
                </div>
            </div>

            <div id="template-avaliacao-full" style="display:none;">
                <div class="pdf-sheet">
                    <div class="sheet-header" id="pdf-header-aval">
                        <div class="sh-meta">
                            <span>RELATÓRIO TÉCNICO</span>
                            <span>DATA: <strong id="aval-data-ref"></strong></span>
                        </div>
                        <h1><strong id="render-aluno-nome-aval" style="font-family: \'Lobster\', cursive; font-size: 35px; margin: 0; text-decoration: none; font-weight: 500;">NOME</strong></h1>
                        <div class="sh-logo"><img src="assets/img/icones/icon-nav.png"></div>
                    </div>

                    <div id="pdf-container-avaliacao" style="padding: 20px;">
                        </div>

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
        require_once '../config/db_connect.php';
        $user_id = $_SESSION['user_id'];

        // 1. Verifica se tem Coach (Bloqueio de Segurança)
        $stmt_check = $pdo->prepare("SELECT coach_id FROM usuarios WHERE id = ?");
        $stmt_check->execute([$user_id]);
        $tem_coach = !empty($stmt_check->fetchColumn());

        // --- BLOQUEIO TOTAL SE TIVER COACH ---
        if ($tem_coach) {
            echo '<section class="fade-in" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:60vh; text-align:center;">
                    <div class="glass-card" style="padding:40px; max-width:400px; width:90%;">
                        <i class="fa-solid fa-user-lock" style="font-size:3rem; color:var(--gold); margin-bottom:20px;"></i>
                        <h2 style="color:#fff; margin-bottom:10px;">Gerenciamento Restrito</h2>
                        <p style="color:#888; margin-bottom:25px;">Seus treinos são definidos exclusivamente pelo seu Treinador. Você não pode criar ou editar fichas manualmente.</p>
                        <button class="btn-gold" onclick="carregarConteudo(\'treinos\')" style="width:100%;">
                            <i class="fa-solid fa-arrow-left"></i> VOLTAR PARA MINHAS FICHAS
                        </button>
                    </div>
                  </section>';
            break; // PARA A EXECUÇÃO AQUI
        }

        // --- SE CHEGOU AQUI, É ALUNO SEM COACH (SEGUE O FLUXO NORMAL) ---

        // 2. Busca o Plano Atual
        $stmt_p = $pdo->prepare("SELECT plano_atual FROM usuarios WHERE id = ?");
        $stmt_p->execute([$user_id]);
        $dados_user = $stmt_p->fetch(PDO::FETCH_ASSOC);
        $plano_atual = $dados_user['plano_atual'] ?? 'start';

        // 3. Busca os treinos
        $sql = "SELECT * FROM treinos WHERE aluno_id = ? AND ativo = 1 ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $meus_treinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ... (RESTANTE DO CÓDIGO ORIGINAL DE EXIBIÇÃO DA LISTA E MODAL) ...
        echo '<section id="meus-treinos-painel" class="fade-in">
                <div class="meus-treinos-header">
                    <div>
                        <h2 style="font-family:Orbitron; color:var(--gold); margin:0;">Meus Treinos</h2>
                        <p style="color:#888; font-size:0.9rem;">Gerencie seus planejamentos</p>
                    </div>
                    <button class="btn-gold" onclick="toggleNovoTreino()">
                        <i class="fa-solid fa-plus"></i> <span class="mobile-hide">NOVO TREINO</span>
                    </button>
                </div>

                <div class="treinos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
                    
                if (count($meus_treinos) > 0) {
                    foreach ($meus_treinos as $t) {
                        $data_inicio = date('d/m/Y', strtotime($t['data_inicio']));
                        $nivel_label = ucfirst($t['nivel_plano']);
                        $bg_icon = ($t['nivel_plano'] == 'basico') ? 'fa-clipboard-list' : 'fa-dumbbell';
                        
                        echo '
                        <div class="treino-card glass-card" style="position:relative; transition: transform 0.2s; cursor:pointer;" onclick="carregarConteudo(\'treino_painel&id='.$t['id'].'\')">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div style="background:rgba(218,165,32,0.1); width:50px; height:50px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:1.5rem;">
                                    <i class="fa-solid '.$bg_icon.'"></i>
                                </div>
                                <button class="btn-icon-delete" onclick="event.stopPropagation(); deletarTreino('.$t['id'].')" style="background:transparent; border:none; color:#666; font-size:1rem; cursor:pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <h3 style="color:#fff; margin-top:15px; font-size:1.1rem;">'.$t['nome'].'</h3>
                            <div style="display:flex; gap:10px; margin-top:5px;">
                                <span class="badge" style="background:#333; color:#ccc; padding:2px 8px; border-radius:4px; font-size:0.7rem;">'.$t['divisao_nome'].'</span>
                                <span class="badge" style="background:#333; color:var(--gold); padding:2px 8px; border-radius:4px; font-size:0.7rem;">'.$nivel_label.'</span>
                            </div>
                            <div style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.05); padding-top:10px; font-size:0.8rem; color:#666; display:flex; justify-content:space-between;">
                                <span>Início: '.$data_inicio.'</span>
                                <span><i class="fa-solid fa-arrow-right"></i> Acessar</span>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div style="grid-column: 1 / -1; text-align:center; padding:50px; color:#666; border:1px dashed #333; border-radius:10px;">
                            <i class="fa-solid fa-ghost" style="font-size:2rem; margin-bottom:15px; opacity:0.5;"></i>
                            <p>Nenhum treino encontrado.</p>
                          </div>';
                }

        echo '  </div>
              </section>';

        // MODAL NOVO TREINO (Só renderiza aqui porque se tiver coach, já deu break lá em cima)
        echo '
        <div id="box-novo-treino" class="modal-overlay" style="display:none;">
            <div class="modal-content selection-modal" style="max-width: 650px; text-align: left; position: relative;">
                <button class="modal-close" onclick="toggleNovoTreino()">&times;</button>
                <h3 class="section-title" style="color: var(--gold); margin-bottom: 25px; text-align: center;">
                    <i class="fa-solid fa-dumbbell"></i> Criar Nova Estrutura
                </h3>

                <div style="margin-bottom: 25px;">
                    <button onclick="carregarConteudo(\'biblioteca_treinos\')" 
                            style="width:100%; padding:12px; background:rgba(255,255,255,0.03); border:1px solid #333; border-radius:8px; color:#999; font-size:0.85rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; transition:all 0.3s;"
                            onmouseover="this.style.borderColor=\'var(--gold)\'; this.style.background=\'rgba(218, 165, 32, 0.05)\'; this.querySelector(\'span\').style.color=\'#fff\'"
                            onmouseout="this.style.borderColor=\'#333\'; this.style.background=\'rgba(255,255,255,0.03)\'; this.querySelector(\'span\').style.color=\'#999\'">
                        
                        <i class="fa-solid fa-book-open" style="color:var(--gold); font-size:0.9rem;"></i>
                        <span style="font-family:Roboto, sans-serif;">Prefere agilidade? <strong style="color:#eee; font-weight:500;">Explorar Biblioteca de Treinos</strong></span>
                        <i class="fa-solid fa-chevron-right" style="font-size:0.7rem; margin-left:auto; opacity:0.5;"></i>
                    </button>
                </div>

                <form id="formNovoTreino" onsubmit="criarTreino(event)">
                    <div class="form-row">
                        <div class="form-col">
                            <label class="input-label">Nome do Planejamento</label>
                            <input type="text" name="nome" class="user-input" placeholder="Ex: Hipertrofia Fase 1" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="input-label">Tipo de Plano</label>
                            <select name="nivel" class="user-input" id="selectNivel" onchange="togglePeriodizacao()" required>';
                                if ($plano_atual === 'start') {
                                    echo '<option value="basico" selected>Básico (Ficha Fixa)</option>';
                                    echo '<option value="avancado" disabled>Avançado (Bloqueado - Alunos PRO)</option>';
                                } else {
                                    echo '<option value="basico">Básico (Ficha Fixa)</option>';
                                    echo '<option value="avancado" selected>Avançado (Periodizado)</option>';
                                }
                        echo '</select>
                        </div>
                        <div class="form-col">
                            <label class="input-label">Data de Início</label>
                            <input type="date" name="data_inicio" class="user-input" required value="'.date('Y-m-d').'">
                        </div>
                        <div class="form-col" style="flex: 0 0 120px;">
                            <label class="input-label">Divisão</label>
                            <input type="text" name="divisao" class="user-input" placeholder="ABC" maxlength="7" style="text-transform:uppercase;" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label class="input-label">Dias de Treino</label>
                        <div class="days-selector">
                            <label><input type="checkbox" name="dias_semana[]" value="0" class="day-checkbox"><span class="day-label">DOM</span></label>
                            <label><input type="checkbox" name="dias_semana[]" value="1" class="day-checkbox"><span class="day-label">SEG</span></label>
                            <label><input type="checkbox" name="dias_semana[]" value="2" class="day-checkbox"><span class="day-label">TER</span></label>
                            <label><input type="checkbox" name="dias_semana[]" value="3" class="day-checkbox"><span class="day-label">QUA</span></label>
                            <label><input type="checkbox" name="dias_semana[]" value="4" class="day-checkbox"><span class="day-label">QUI</span></label>
                            <label><input type="checkbox" name="dias_semana[]" value="5" class="day-checkbox"><span class="day-label">SEX</span></label>
                            <label><input type="checkbox" name="dias_semana[]" value="6" class="day-checkbox"><span class="day-label">SÁB</span></label>
                        </div>
                    </div>
                    <div id="aviso-periodizacao" class="alert-box" '.($plano_atual === 'start' ? 'style="display:none;"' : '').'>
                        <span class="alert-title">Modo Periodização Ativo</span>
                        <p class="alert-text">Serão gerados 12 Microciclos automaticamente.</p>
                    </div>
                    <button type="submit" class="btn-gold" style="width:100%; margin-top: 15px; padding: 15px;">CRIAR ESTRUTURA</button>
                </form>
            </div>
        </div>';
        
        break;
    

    case 'treino_painel':
        require_once '../config/db_connect.php';
        require_once '../helpers/tradutor_treino.php'; // Helper de tradução
        
        $treino_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $idioma_aluno = $_SESSION['pref_idioma'] ?? 'pt'; // Preferência do usuário logado para as séries

        if (!$treino_id) { 
            echo "<div class='glass-card'>ID do treino não fornecido.</div>"; 
            break; 
        }

        // 1. BUSCAR DADOS DO TREINO
        $sql = "SELECT t.*, u.nome as nome_aluno FROM treinos t JOIN usuarios u ON t.aluno_id = u.id WHERE t.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $treino_id]);
        $treino = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$treino) {
            echo "<div class='glass-card'>Treino não encontrado.</div>";
            break;
        }

        // 2. BUSCAR DIVISÕES
        $sql_div = "SELECT * FROM treino_divisoes WHERE treino_id = :id ORDER BY letra ASC";
        $stmt_div = $pdo->prepare($sql_div);
        $stmt_div->execute(['id' => $treino_id]);
        $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

        // --- OTIMIZAÇÃO: CARREGAR TUDO EM LOTE ---
        $exercicios_por_divisao = [];
        $series_por_exercicio = [];

        if (!empty($divisoes)) {
            $div_ids = array_column($divisoes, 'id');
            $placeholders_div = implode(',', array_fill(0, count($div_ids), '?'));
            
            $stmt_ex = $pdo->prepare("SELECT * FROM exercicios WHERE divisao_id IN ($placeholders_div) ORDER BY ordem ASC");
            $stmt_ex->execute($div_ids);
            $todos_exercicios = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($todos_exercicios)) {
                $ex_ids = [];
                foreach ($todos_exercicios as $ex) {
                    $exercicios_por_divisao[$ex['divisao_id']][] = $ex;
                    $ex_ids[] = $ex['id'];
                }

                if (!empty($ex_ids)) {
                    $placeholders_ex = implode(',', array_fill(0, count($ex_ids), '?'));
                    $stmt_series = $pdo->prepare("SELECT * FROM series WHERE exercicio_id IN ($placeholders_ex) ORDER BY id ASC");
                    $stmt_series->execute($ex_ids);
                    $todas_series = $stmt_series->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($todas_series as $s) {
                        $series_por_exercicio[$s['exercicio_id']][] = $s;
                    }
                }
            }
        }

        // 3. BUSCAR PERIODIZAÇÃO
        $microciclos = [];
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
        
        // --- CABEÇALHO ---
        echo '
            <section id="painel-treino" class="fade-in">
                <div style="display:flex; align-items:center; gap:20px; margin-bottom:30px;">
                    <button class="btn-action-icon" onclick="carregarConteudo(\'novo_treino\')"><i class="fa-solid fa-arrow-left"></i></button>
                    <div>
                        <h2 style="color:#fff; font-family:Orbitron; margin:0;">'.$treino['nome'].'</h2>
                        <p style="color:#888; font-size:0.9rem;">Aluno: <strong style="color:var(--gold);">'.$treino['nome_aluno'].'</strong> • '.strtoupper($treino['nivel_plano']).'</p>
                    </div>
                </div>';
                
                // TIMELINE
                if (!empty($microciclos)) {
                    echo '<h3 class="section-title" style="font-size:1rem; margin-bottom:10px;">PERIODIZAÇÃO</h3>
                          <div class="timeline-wrapper">';
                    foreach ($microciclos as $m) {
                        $inicio = date('d/m', strtotime($m['data_inicio_semana']));
                        $fim = date('d/m', strtotime($m['data_fim_semana']));
                        $hoje = date('Y-m-d');
                        $activeClass = ($hoje >= $m['data_inicio_semana'] && $hoje <= $m['data_fim_semana']) ? 'active' : '';
                        $m_json = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');

                        echo '<div class="micro-card '.$activeClass.'" onclick=\'openMicroModal('.$m_json.', '.$treino_id.')\'>
                                <span class="micro-week">SEMANA '.$m['semana_numero'].'</span>
                                <span class="micro-date">'.$inicio.' - '.$fim.'</span>
                                <div style="margin-top:5px; font-size:0.7rem; opacity:0.7;">'.$m['nome_fase'].'</div>
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
                            echo '<button class="div-tab-btn '.$active.'" onclick="openTab(event, \'div_'.$div['letra'].'\')">TREINO '.$div['letra'].'</button>';
                            $first = false;
                        }
        echo '      </div>';

                    // --- CONTEÚDO DAS DIVISÕES ---
                    $firstContent = true;
                    foreach ($divisoes as $div) {
                        $display = $firstContent ? 'active' : '';
                        
                        $exercicios_raw = $exercicios_por_divisao[$div['id']] ?? [];

                        // AGRUPAMENTO
                        $lista_final = [];
                        $grupos_temp = []; 

                        foreach ($exercicios_raw as $ex) {
                            $hash = $ex['agrupamento_hash'];
                            if ($hash) {
                                if (!isset($grupos_temp[$hash])) {
                                    $idx = count($lista_final);
                                    $lista_final[$idx] = ['tipo' => 'grupo', 'itens' => []];
                                    $grupos_temp[$hash] = $idx;
                                }
                                $lista_final[$grupos_temp[$hash]]['itens'][] = $ex;
                            } else {
                                $lista_final[] = ['tipo' => 'single', 'itens' => [$ex]];
                            }
                        }

                        echo '
                            <div id="div_'.$div['letra'].'" class="division-content '.$display.'">
                                
                                <div class="div-header" id="div-treino">
                                    <div>
                                        <div style="display:flex; align-items:center; gap: 10px;">
                                            <h3 style="color:#fff; margin:0; font-size: 1.2rem;">TREINO '.$div['letra'].'</h3>
                                            <button onclick="renomearDivisao('.$div['id'].', \''.$div['letra'].'\', \''.($div['nome'] ?? '').'\')" 
                                                    style="background: transparent; border: none; color: #666; cursor: pointer; font-size: 0.9rem;"
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
                                
                                if (empty($lista_final)) {
                                    echo '<p style="text-align:center; color:#666; padding:30px;">Nenhum exercício cadastrado.</p>';
                                } else {
                                    foreach ($lista_final as $bloco) {
                                        if ($bloco['tipo'] === 'grupo') {
                                            $label = (count($bloco['itens']) === 2) ? 'BI-SET' : 'TRI-SET';
                                            echo '<div class="agrupamento-wrapper"><span class="agrupamento-badge">'.$label.'</span>';
                                        }

                                        foreach ($bloco['itens'] as $ex) {
                                            $series = $series_por_exercicio[$ex['id']] ?? [];
                                            
                                            $ex_data = $ex;
                                            $ex_data['series'] = $series;
                                            $ex_json = htmlspecialchars(json_encode($ex_data), ENT_QUOTES, 'UTF-8');

                                            echo '
                                            <div class="exercise-card-edit" style="margin-bottom:10px;">
                                                <div class="ex-info">
                                                    <span class="ex-meta">'.strtoupper($ex['tipo_mecanica']).'</span>
                                                    <h4>'.$ex['nome_exercicio'].'</h4>
                                                    <div class="sets-container">';
                                                        foreach ($series as $s) {
                                                            $qtd = $s['quantidade'];
                                                            $reps = $s['reps_fixas'];
                                                            $tecnica = strtolower(trim($s['tecnica'] ?? 'normal'));
                                                            $valor = $s['tecnica_valor'] ?? '';
                                                            
                                                            $style = '';
                                                            // TRADUZ O NOME DA CATEGORIA DA SÉRIE AQUI:
                                                            $label = traduzirTermo($s['categoria'] ?? 'normal', $idioma_aluno);
                                                            $extra = $reps ? "(".$reps.")" : "";

                                                            if ($tecnica === 'dropset') {
                                                                $style = 'background:rgba(255, 64, 129, 0.1); color:#ff4081; border:1px solid rgba(255, 64, 129, 0.3);';
                                                                $label = traduzirTermo('dropset', $idioma_aluno);
                                                                $extra = "<small style='opacity:0.8; font-size:0.85em; margin-left:2px;'>({$valor} drops)</small>";
                                                            } elseif ($tecnica === 'restpause') {
                                                                $style = 'background:rgba(0, 188, 212, 0.1); color:#00bcd4; border:1px solid rgba(0, 188, 212, 0.3);';
                                                                $label = traduzirTermo('restpause', $idioma_aluno);
                                                                $extra = "<small style='opacity:0.8; font-size:0.85em; margin-left:2px;'>({$valor}s)</small>";
                                                            } elseif ($tecnica === 'clusterset') {
                                                                $style = 'background:rgba(255, 145, 0, 0.1); color:#ff9100; border:1px solid rgba(255, 145, 0, 0.3);';
                                                                $label = traduzirTermo('clusterset', $idioma_aluno);
                                                                $parts = explode('|', $valor);
                                                                if(count($parts)===3) $extra = "<small>({$parts[0]}x{$parts[1]})</small>";
                                                            }

                                                            if($style) echo '<span class="set-tag" style="'.$style.'">'.$qtd.'x '.$label.' '.$extra.'</span>';
                                                            else echo '<span class="set-tag '.$s['categoria'].'">'.$qtd.'x '.$label.' '.$extra.'</span>';
                                                        }
                                                echo '  </div>
                                                </div>
                                                <div class="ex-actions">
                                                    <button class="btn-action-icon" onclick=\'editarExercicio('.$ex_json.', '.$treino_id.', '.$div['id'].')\'><i class="fa-solid fa-pen"></i></button>
                                                    <button class="btn-action-icon btn-delete" onclick="deletarExercicio('.$ex['id'].', '.$treino_id.')"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </div>';
                                        }

                                        if ($bloco['tipo'] === 'grupo') echo '</div>'; 
                                    }
                                }
                        echo '</div></div>';
                        $firstContent = false;
                    }

        echo '  </div>
            </section>';
            
        // MODAL DO EXERCÍCIO COM O SELECT TRADUZIDO
        echo '
            <div id="modalExercicio" class="modal-overlay">
                <div class="modal-content" style="max-width: 700px;">
                    <button class="modal-close" onclick="closeExercicioModal()">&times;</button>
                    
                    <div class="editor-exercicio-header">
                        <h3 class="section-title" style="color:var(--gold); margin:0;">Novo Exercício</h3>
                        <div class="block-type-selector" style="display:flex; gap:5px; background:rgba(255,255,255,0.05); padding:4px; border-radius:6px;">
                            <button type="button" class="btn-type-select active" onclick="initBlockState(\'single\')" id="btn-mode-single">Padrão</button>
                            <button type="button" class="btn-type-select" onclick="initBlockState(\'biset\')" id="btn-mode-biset">Bi-set</button>
                            <button type="button" class="btn-type-select" onclick="initBlockState(\'triset\')" id="btn-mode-triset">Tri-set</button>
                        </div>
                    </div>

                    <div id="exercise-tabs-container" style="display:none; gap:5px; margin-bottom:20px; border-bottom:1px solid #333; padding-bottom:10px;"></div>

                    <form id="formExercicio">
                        <input type="hidden" id="modal_divisao_id">
                        <input type="hidden" id="modal_treino_id">
                        <input type="hidden" id="modal_exercicio_id">

                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:2;">
                                <label class="input-label">Nome do Exercício</label>
                                <input type="text" id="inp_nome" class="user-input" placeholder="Ex: Supino Reto" required>
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Mecânica</label>
                                <select id="inp_mecanica" class="user-input">
                                    <option value="livre">Livre / Máquina</option>
                                    <option value="composto">Composto</option>
                                    <option value="isolador">Isolador</option>
                                </select>
                            </div>
                        </div>

                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:1;">
                                <label class="input-label">Vídeo URL</label>
                                <input type="text" id="inp_video" class="user-input" placeholder="https://...">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Observação</label>
                                <input type="text" id="inp_obs" class="user-input" placeholder="Dica...">
                            </div>
                        </div>

                        <hr style="border:0; border-top:1px solid #333; margin:20px 0;">

                        <h4 style="color:#fff; font-size:0.9rem; margin-bottom:10px;">Séries</h4>
                        <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px;">
                            <div class="set-inputs-row" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                                <div style="flex:0 0 60px;"><label class="input-label" style="font-size:0.7rem;">Qtd</label><input type="number" id="set_qtd" class="user-input" value="1" style="padding:8px;"></div>
                                <div style="flex:1; min-width:140px;">
                                    <label class="input-label" style="font-size:0.7rem;">Tipo</label>
                                    <select id="set_tipo" class="user-input" style="padding:8px;" onchange="toggleTechniqueFields()">
                                        <option value="work">'.traduzirTermo('work', $idioma_aluno).' (Work Set)</option>
                                        <option value="warmup">'.traduzirTermo('warmup', $idioma_aluno).' (Warm Up)</option>
                                        <option value="feeder">'.traduzirTermo('feeder', $idioma_aluno).' (Feeder)</option>
                                        <option value="top">'.traduzirTermo('topset', $idioma_aluno).' (Top Set)</option>
                                        <option value="backoff">'.traduzirTermo('backoff', $idioma_aluno).' (Backoff)</option>
                                        <option value="falha">Falha</option>
                                        <option value="dropset" style="color:#ff4d4d;">'.traduzirTermo('dropset', $idioma_aluno).'</option>
                                        <option value="restpause" style="color:#00e676;">'.traduzirTermo('restpause', $idioma_aluno).'</option>
                                        <option value="clusterset" style="color:#00bfff;">'.traduzirTermo('clusterset', $idioma_aluno).'</option>
                                    </select>
                                </div>
                                <div style="flex:0 0 70px;"><label class="input-label" style="font-size:0.7rem;">Reps</label><input type="text" id="set_reps" class="user-input" placeholder="10" style="padding:8px;"></div>
                                <div style="flex:0 0 70px;"><label class="input-label" style="font-size:0.7rem;">Desc</label><input type="text" id="set_desc" class="user-input" placeholder="60s" style="padding:8px;"></div>
                                <button type="button" class="btn-gold" onclick="addSetToList()" style="padding:8px 15px; height:38px;"><i class="fa-solid fa-plus"></i></button>
                            </div>

                            <div id="div_special_inputs" style="margin-top:10px;">
                                <div id="div_drops" style="display:none; padding:10px; border:1px dashed #ff4d4d; border-radius:4px;"><label style="color:#ff4d4d;">Qtd Drops:</label> <input type="number" id="set_drops_qtd" class="user-input" style="width:60px;" placeholder="2"></div>
                                <div id="div_pause" style="display:none; padding:10px; border:1px dashed #00e676; border-radius:4px;"><label style="color:#00e676;">Pausa (s):</label> <input type="number" id="set_rest_time" class="user-input" style="width:60px;" placeholder="15"></div>
                                <div id="div_cluster" style="display:none; padding:10px; border:1px dashed #00bfff; border-radius:4px;">
                                    <label style="color:#00bfff;">Blocos:</label> <input type="number" id="set_cluster_blocos" class="user-input" style="width:50px;"> X <input type="text" id="set_cluster_reps" class="user-input" style="width:50px;" placeholder="reps"> <input type="number" id="set_cluster_rest" class="user-input" style="width:60px;" placeholder="s">
                                </div>
                            </div>

                            <div id="temp-sets-list" style="margin-top:15px; max-height:150px; overflow-y:auto; font-size:0.85rem;"></div>
                        </div>

                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn-gold" style="background:transparent; border:1px solid #555; color:#ccc; margin-right:10px;" onclick="closeExercicioModal()">Cancelar</button>
                            <button type="button" class="btn-gold" id="btn-save-modal" onclick="salvarBlocoExercicios()">SALVAR TUDO</button>
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
    
    case 'biblioteca_treinos':
        echo '<section class="fade-in">
                <div style="display:flex; align-items:center; gap:15px; margin-bottom:30px;">
                    <button onclick="carregarConteudo(\'treinos\')" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div>
                        <h2 style="font-family:Orbitron; color:var(--gold); margin:0;">Biblioteca de Treinos</h2>
                        <p style="color:#888; font-size:0.9rem;">Escolha um protocolo validado e comece hoje.</p>
                    </div>
                </div>

                <div class="library-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
                    
                    <div class="glass-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; border:1px solid #333; transition:transform 0.2s;">
                        <div style="height:150px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), #1a1a1a), url(\'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=800&auto=format&fit=crop\'); background-size:cover; background-position:center; display:flex; align-items:flex-end; padding:15px;">
                            <span style="background:var(--gold); color:#000; font-weight:bold; font-size:0.7rem; padding:4px 8px; border-radius:4px;">MAIS USADO</span>
                        </div>
                        <div style="padding:20px; flex:1; display:flex; flex-direction:column;">
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                                <h3 style="color:#fff; margin:0; font-size:1.2rem;">Push / Pull / Legs</h3>
                                <i class="fa-solid fa-dumbbell" style="color:#666; font-size:1.2rem;"></i>
                            </div>
                            <p style="color:#888; font-size:0.85rem; margin-bottom:20px; flex:1;">
                                O padrão ouro da estética. Divide o corpo por movimentos (Empurrar, Puxar, Pernas). Foco total em hipertrofia.
                            </p>
                            <div style="display:flex; gap:10px; margin-bottom:20px;">
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">ABC</span>
                                    <span style="color:#666; font-size:0.6rem;">DIVISÃO</span>
                                </div>
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">6x</span>
                                    <span style="color:#666; font-size:0.6rem;">DIAS/SEM</span>
                                </div>
                            </div>
                            <button onclick="adotarModelo(\'hipertrofia_abc\')" class="btn-gold" style="width:100%; justify-content:center;">
                                ADOTAR ESTE TREINO
                            </button>
                        </div>
                    </div>

                    <div class="glass-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; border:1px solid #333; transition:transform 0.2s;">
                        <div style="height:150px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), #1a1a1a), url(\'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\'); background-size:cover; background-position:center; display:flex; align-items:flex-end; padding:15px;">
                            <span style="background:#fff; color:#000; font-weight:bold; font-size:0.7rem; padding:4px 8px; border-radius:4px;">PERFORMANCE</span>
                        </div>
                        <div style="padding:20px; flex:1; display:flex; flex-direction:column;">
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                                <h3 style="color:#fff; margin:0; font-size:1.2rem;">Híbrido 5x</h3>
                                <i class="fa-solid fa-flask" style="color:#666; font-size:1.2rem;"></i>
                            </div>
                            <p style="color:#888; font-size:0.85rem; margin-bottom:20px; flex:1;">
                                A melhor divisão para 5 dias. Mescla dias de Força (Upper/Lower) com dias de Hipertrofia (Push/Pull/Legs).
                            </p>
                            <div style="display:flex; gap:10px; margin-bottom:20px;">
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">PHAT</span>
                                    <span style="color:#666; font-size:0.6rem;">DIVISÃO</span>
                                </div>
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">5x</span>
                                    <span style="color:#666; font-size:0.6rem;">SEG-SEX</span>
                                </div>
                            </div>
                            <button onclick="adotarModelo(\'hibrido_5x\')" class="btn-gold" style="width:100%; justify-content:center;">
                                ADOTAR ESTE TREINO
                            </button>
                        </div>
                    </div>

                    <div class="glass-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; border:1px solid #333; transition:transform 0.2s;">
                        <div style="height:150px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), #1a1a1a), url(\'https://images.unsplash.com/photo-1574680096145-d05b474e2155?q=80&w=1169&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\'); background-size:cover; background-position:center; display:flex; align-items:flex-end; padding:15px;">
                            <span style="background:#4CAF50; color:#fff; font-weight:bold; font-size:0.7rem; padding:4px 8px; border-radius:4px;">BASE SÓLIDA</span>
                        </div>
                        <div style="padding:20px; flex:1; display:flex; flex-direction:column;">
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                                <h3 style="color:#fff; margin:0; font-size:1.2rem;">Full Body 3x</h3>
                                <i class="fa-solid fa-child-reaching" style="color:#666; font-size:1.2rem;"></i>
                            </div>
                            <p style="color:#888; font-size:0.85rem; margin-bottom:20px; flex:1;">
                                Treina o corpo todo em uma sessão. Perfeito para iniciantes ou quem tem poucos dias para treinar.
                            </p>
                            <div style="display:flex; gap:10px; margin-bottom:20px;">
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">FB</span>
                                    <span style="color:#666; font-size:0.6rem;">DIVISÃO</span>
                                </div>
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">3x</span>
                                    <span style="color:#666; font-size:0.6rem;">DIAS/SEM</span>
                                </div>
                            </div>
                            <button onclick="adotarModelo(\'fullbody_3x\')" class="btn-gold" style="width:100%; justify-content:center;">
                                ADOTAR ESTE TREINO
                            </button>
                        </div>
                    </div>

                    <div class="glass-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column; border:1px solid #333; transition:transform 0.2s;">
                        <div style="height:150px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), #1a1a1a), url(\'https://images.unsplash.com/photo-1532384748853-8f54a8f476e2?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\'); background-size:cover; background-position:center; display:flex; align-items:flex-end; padding:15px;">
                            <span style="background:#333; color:#fff; font-weight:bold; font-size:0.7rem; padding:4px 8px; border-radius:4px; border:1px solid #555;">INTENSO</span>
                        </div>
                        <div style="padding:20px; flex:1; display:flex; flex-direction:column;">
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                                <h3 style="color:#fff; margin:0; font-size:1.2rem;">Upper / Lower</h3>
                                <i class="fa-solid fa-bolt" style="color:#666; font-size:1.2rem;"></i>
                            </div>
                            <p style="color:#888; font-size:0.85rem; margin-bottom:20px; flex:1;">
                                Alta frequência. Treina superiores e inferiores 2x na semana. Ótimo para força e condicionamento.
                            </p>
                            <div style="display:flex; gap:10px; margin-bottom:20px;">
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">AB</span>
                                    <span style="color:#666; font-size:0.6rem;">DIVISÃO</span>
                                </div>
                                <div style="background:#222; padding:5px 10px; border-radius:6px; text-align:center;">
                                    <span style="display:block; color:#fff; font-weight:bold; font-size:0.9rem;">4x</span>
                                    <span style="color:#666; font-size:0.6rem;">DIAS/SEM</span>
                                </div>
                            </div>
                            <button onclick="adotarModelo(\'upper_lower_ab\')" class="btn-gold" style="width:100%; justify-content:center;">
                                ADOTAR ESTE TREINO
                            </button>
                        </div>
                    </div>

                </div>
              </section>';
        break;

    case 'dieta_editor':
        require_once '../config/db_connect.php';
        $aluno_id = $_SESSION['user_id']; 

        // 1. Verifica Coach
        $stmt_c = $pdo->prepare("SELECT coach_id FROM usuarios WHERE id = ?");
        $stmt_c->execute([$aluno_id]);
        $tem_coach = !empty($stmt_c->fetchColumn());

        // --- BLOQUEIO TOTAL SE TIVER COACH ---
        if ($tem_coach) {
            echo '<section class="fade-in" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:60vh; text-align:center;">
                    <div class="glass-card" style="padding:40px; max-width:400px; width:90%;">
                        <i class="fa-solid fa-utensils" style="font-size:3rem; color:var(--gold); margin-bottom:20px;"></i>
                        <h2 style="color:#fff; margin-bottom:10px;">Edição Bloqueada</h2>
                        <p style="color:#888; margin-bottom:25px;">Seu plano alimentar é prescrito pelo Nutricionista/Coach. Acesse a área de refeições para registrar o consumo.</p>
                        
                        <button class="btn-gold" onclick="carregarConteudo(\'dieta\')" style="width:100%;">
                            <i class="fa-solid fa-eye"></i> VER MEU PLANO
                        </button>
                    </div>
                  </section>';
            break; // PARA A EXECUÇÃO AQUI
        }

        // --- DAQUI PRA BAIXO SÓ EXECUTA SE NÃO TIVER COACH ---

        // 2. Busca Dieta ATIVA
        $stmt_d = $pdo->prepare("SELECT * FROM dietas WHERE aluno_id = ? LIMIT 1");
        $stmt_d->execute([$aluno_id]);
        $dieta = $stmt_d->fetch(PDO::FETCH_ASSOC);

        echo '<section id="minha-dieta-editor">
                <header class="dash-header" style="margin-bottom: 20px;">
                    <div>
                        <h1 style="font-size:1.8rem; margin:0;">MONTAR <span class="highlight-text">DIETA</span></h1>
                        <p class="text-desc" style="margin:0;">Gerencie suas refeições e macros.</p>
                    </div>
                </header>';

        // --- ESTADO 1: SEM DIETA (CRIAR) ---
        if (!$dieta) {
            echo '<div class="glass-card" style="text-align:center; padding:50px;">
                    <i class="fa-solid fa-utensils" style="font-size:3rem; color:#333; margin-bottom:20px;"></i>
                    <h3 style="color:#fff; margin-bottom:10px;">Você ainda não tem uma dieta</h3>
                    <p style="color:#888; margin-bottom:30px;">Crie seu plano alimentar agora mesmo para começar o monitoramento.</p>
                    
                    <form action="actions/dieta_save.php" method="POST" style="max-width:400px; margin:auto;">
                        <input type="hidden" name="acao" value="criar_dieta">
                        <input type="hidden" name="aluno_id" value="'.$aluno_id.'">
                        <input type="hidden" name="origem" value="usuario"> 
                        
                        <input type="text" name="titulo" class="user-input" placeholder="Nome da Dieta (Ex: Hipertrofia)" required style="margin-bottom:10px;">
                        <input type="text" name="objetivo" class="user-input" placeholder="Objetivo (Ex: 2500 Kcal)" required style="margin-bottom:20px;">
                        
                        <button type="submit" class="btn-gold" style="width:100%;">CRIAR MINHA DIETA</button>
                    </form>
                </div>';
        } 
        // --- ESTADO 2: COM DIETA (EDITOR COMPLETO) ---
        else {
            echo '<div class="glass-card mb-large">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:20px;">
                        <div>
                            <h3 style="color:var(--gold); margin:0;">'.$dieta['titulo'].'</h3>
                            <span style="color:#888; font-size:0.9rem;">'.$dieta['objetivo'].'</span>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <a href="actions/dieta_save.php?acao=excluir_dieta&id='.$dieta['id'].'&aluno_id='.$aluno_id.'&origem=usuario" class="btn-action-icon btn-delete" onclick="return confirm(\'Tem certeza? Isso apagará todas as suas refeições configuradas.\')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>

                    <div style="margin-bottom:30px;">
                        <button class="btn-gold" onclick="abrirModalRefeicao('.$dieta['id'].')">
                            <i class="fa-solid fa-plus"></i> NOVA REFEIÇÃO
                        </button>
                    </div>

                    <div class="diet-editor-list">';

                    $stmt_ref = $pdo->prepare("SELECT * FROM refeicoes WHERE dieta_id = ? ORDER BY ordem ASC");
                    $stmt_ref->execute([$dieta['id']]);
                    $refeicoes = $stmt_ref->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($refeicoes)) {
                        echo '<p style="text-align:center; color:#666; padding:20px; border: 1px dashed #333; border-radius: 8px;">Nenhuma refeição cadastrada. Clique no botão acima para começar.</p>';
                    }

                    foreach($refeicoes as $ref) {
                        echo '<div class="meal-edit-card" style="background:#1a1a1a; border:1px solid #333; border-radius:12px; margin-bottom:20px; overflow:hidden;">
                                
                                <div class="meal-header" style="background:#222; padding:15px; display:flex; justify-content:space-between; align-items:center;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span style="background:var(--gold); color:#000; padding:4px 8px; border-radius:6px; font-weight:bold; font-size:0.8rem;">'.date('H:i', strtotime($ref['horario'])).'</span>
                                        <strong style="color:#fff;">'.$ref['nome'].'</strong>
                                    </div>
                                    <div style="display:flex; gap:10px;">
                                        <button class="btn-action-icon" onclick="abrirModalAlimento('.$ref['id'].')" title="Adicionar Alimento"><i class="fa-solid fa-plus"></i></button>
                                        <a href="actions/dieta_save.php?acao=excluir_refeicao&id='.$ref['id'].'&aluno_id='.$aluno_id.'&origem=usuario" class="btn-action-icon btn-delete"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </div>

                                <div class="meal-items" style="padding:15px;">';
                                
                                $stmt_it = $pdo->prepare("SELECT * FROM itens_dieta WHERE refeicao_id = ? ORDER BY opcao_numero ASC");
                                $stmt_it->execute([$ref['id']]);
                                $itens = $stmt_it->fetchAll(PDO::FETCH_ASSOC);

                                if(empty($itens)) {
                                    echo '<p style="color:#666; font-style:italic; font-size:0.9rem; text-align:center; margin:0;">Nenhum alimento nesta refeição.</p>';
                                } else {
                                    foreach($itens as $it) {
                                        $tipo = ($it['opcao_numero'] == 1) ? '<span style="color:#00e676; font-size:0.7rem; font-weight:bold;">[PRINCIPAL]</span>' : '<span style="color:#ff9100; font-size:0.7rem; font-weight:bold;">[OPÇÃO '.$it['opcao_numero'].']</span>';
                                        
                                        echo '<div style="display:flex; justify-content:space-between; align-items:flex-start; padding:10px 0; border-bottom:1px solid #2a2a2a;">
                                                <div style="flex:1;">
                                                    '.$tipo.'
                                                    <strong style="display:block; color:#eee; font-size:0.95rem;">'.$it['descricao'].'</strong>
                                                    '.($it['observacao'] ? '<small style="color:#888;">Obs: '.$it['observacao'].'</small>' : '').'
                                                </div>
                                                <a href="actions/dieta_save.php?acao=excluir_item&id='.$it['id'].'&aluno_id='.$aluno_id.'&origem=usuario" style="color:#666; margin-left:10px;"><i class="fa-solid fa-xmark"></i></a>
                                            </div>';
                                    }
                                }

                        echo '  </div>
                            </div>';
                    }

            echo '  </div>
                </div>';
        }
        echo '</section>';

        // MODAIS DE EDIÇÃO (SÓ CARREGAM SE NÃO TIVER COACH)
        echo '
        <div id="modalNovaRefeicao" class="modal-overlay" style="display:none;">
            <div class="modal-content selection-modal" style="text-align:left; max-width:400px;">
                <button class="modal-close" onclick="fecharModalRefeicao()">&times;</button>
                <h3 class="modal-title" style="text-align:center;">Nova Refeição</h3>
                <form action="actions/dieta_save.php" method="POST">
                    <input type="hidden" name="acao" value="add_refeicao">
                    <input type="hidden" name="dieta_id" id="modal_dieta_id">
                    <input type="hidden" name="aluno_id" value="'.$aluno_id.'">
                    <input type="hidden" name="origem" value="usuario"> 
                    <label class="input-label">Nome (Ex: Café da Manhã)</label>
                    <input type="text" name="nome" class="user-input" required style="margin-bottom:15px;">
                    <label class="input-label">Horário Sugerido</label>
                    <input type="time" name="horario" class="user-input" required style="margin-bottom:15px;">
                    <label class="input-label">Ordem</label>
                    <input type="number" name="ordem" class="user-input" value="1" required style="margin-bottom:20px;">
                    <button type="submit" class="btn-gold" style="width:100%;">CRIAR REFEIÇÃO</button>
                </form>
            </div>
        </div>

        <div id="modalNovoAlimento" class="modal-overlay" style="display:none;">
            <div class="modal-content selection-modal" style="text-align:left; max-width:400px;">
                <button class="modal-close" onclick="fecharModalAlimento()">&times;</button>
                <h3 class="modal-title" style="text-align:center;">Adicionar Alimento</h3>
                <form action="actions/dieta_save.php" method="POST">
                    <input type="hidden" name="acao" value="add_item">
                    <input type="hidden" name="refeicao_id" id="modal_refeicao_id">
                    <input type="hidden" name="aluno_id" value="'.$aluno_id.'">
                    <input type="hidden" name="origem" value="usuario"> 
                    <label class="input-label">Tipo</label>
                    <select name="opcao_numero" class="user-input" style="margin-bottom:15px;">
                        <option value="1">Opção Principal</option>
                        <option value="2">Opção 2 (Substituição)</option>
                        <option value="3">Opção 3 (Substituição)</option>
                    </select>
                    <label class="input-label">Descrição</label>
                    <textarea name="descricao" class="user-input" rows="3" placeholder="Ex: 2 Ovos mexidos + 1 Banana" required style="margin-bottom:15px;"></textarea>
                    <label class="input-label">Observação (Opcional)</label>
                    <input type="text" name="observacao" class="user-input" placeholder="Ex: Sem açúcar" style="margin-bottom:20px;">
                    <button type="submit" class="btn-gold" style="width:100%;">ADICIONAR</button>
                </form>
            </div>
        </div>';

        break;
    
    case 'tutoriais':
        // --- CONFIGURAÇÃO DOS VÍDEOS (EDITE AQUI) ---
        // Basta adicionar ou remover linhas neste array.
        // O 'id' é o código que fica depois do v= no YouTube.
        $lista_videos = [
            [
                'titulo' => 'Como instalar o App no celular',
                'desc'   => 'Entenda como utilizar o site do sistema como app para uma melhor experiência.',
                'id'     => 'lsrY3ESQHv0', // Ex: dQw4w9WgXcQ
                'thumb'  => 'assets/img/thumbs/userpage.png' // Ou use a do youtube (veja abaixo)
            ],
            [
                'titulo' => 'Como Registrar seu Treino',
                'desc'   => 'Aprenda a anotar cargas, reps e usar o cronômetro.',
                'id'     => 'hMBLqRZcZZA',
                'thumb'  => '' // Se vazio, pega automático do YT
            ],
            [
                'titulo' => 'Entendendo a Periodização',
                'desc'   => 'O que são microciclos e como evoluir sua carga.',
                'id'     => 'hMBLqRZcZZA',
                'thumb'  => ''
            ],
            [
                'titulo' => 'Histórico e Evolução',
                'desc'   => 'Como acompanhar seu progresso graficamente.',
                'id'     => 'hMBLqRZcZZA',
                'thumb'  => ''
            ]
        ];

        echo '<section class="fade-in">
                <header class="dash-header">
                    <h1>CENTRAL DE <span class="highlight-text">AJUDA</span></h1>
                    <p>Aprenda a extrair o máximo do seu treinamento.</p>
                </header>
                
                <div class="tutorials-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 30px;">';
                
                foreach($lista_videos as $v) {
                    // Lógica da Thumbnail: Se não tiver personalizada, usa a do YouTube
                    $img = $v['thumb'];
                    if(empty($img)) {
                        $img = "https://img.youtube.com/vi/{$v['id']}/mqdefault.jpg";
                    }

                    // Escapa aspas para não quebrar o HTML do onclick
                    $titulo_safe = htmlspecialchars($v['titulo'], ENT_QUOTES);

                    echo '
                    <div class="video-card" onclick="abrirModalVideo(\''.$v['id'].'\', \''.$titulo_safe.'\')" 
                         style="background: #111; border: 1px solid #333; border-radius: 10px; overflow: hidden; cursor: pointer; transition: transform 0.2s, border-color 0.2s;">
                        
                        <div class="thumb-wrapper" style="position: relative; aspect-ratio: 16/9; overflow: hidden;">
                            <img src="'.$img.'" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.8; transition: 0.3s;">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 50px; height: 50px; background: rgba(0,0,0,0.7); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid var(--gold);">
                                <i class="fa-solid fa-play" style="color: var(--gold); margin-left: 3px;"></i>
                            </div>
                        </div>
                        
                        <div style="padding: 15px;">
                            <h3 style="color: #fff; font-size: 1rem; margin-bottom: 5px;">'.$v['titulo'].'</h3>
                            <p style="color: #888; font-size: 0.8rem;">'.$v['desc'].'</p>
                        </div>
                    </div>';
                }

        echo '  </div>
              </section>
              
              <style>
                .video-card:hover { transform: translateY(-5px); border-color: var(--gold) !important; }
                .video-card:hover img { opacity: 1 !important; transform: scale(1.05); }
              </style>';
        break;

    case 'assinatura':
        require_once '../config/db_connect.php';
        
        $aluno_id = $_SESSION['user_id'];
        
        // 1. ALTERAÇÃO: Adicionei 'data_cadastro' na busca
        $stmt = $pdo->prepare("SELECT nome, email, plano_atual, data_expiracao_plano, data_cadastro FROM usuarios WHERE id = ?");
        $stmt->execute([$aluno_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) { echo '<p>Erro ao carregar dados.</p>'; break; }

        // 2. Lógica de Tempo e Cores
        $hoje = new DateTime();
        $vencimento = new DateTime($user['data_expiracao_plano']);
        $intervalo = $hoje->diff($vencimento);
        $dias_restantes = (int)$intervalo->format('%r%a');

        // Lógica do Ano de Cadastro
        $ano_membro = date('Y', strtotime($user['data_cadastro']));

        // Configuração Padrão
        $bar_color = "#00e676"; // Verde Neon
        $status_msg = "Sua assinatura está ativa e longe do vencimento.";
        $pct = 100;

        if ($dias_restantes < 0) {
            $dias_restantes = 0;
            $pct = 0;
            $bar_color = "#ff4242";
            $status_msg = "Sua assinatura expirou.";
        } elseif ($dias_restantes <= 7) {
            $pct = ($dias_restantes / 30) * 100;
            $bar_color = "#ff4242";
            $status_msg = "Atenção! Seu plano vence em breve.";
        } elseif ($dias_restantes <= 15) {
            $pct = ($dias_restantes / 30) * 100;
            $bar_color = "#ff9100";
            $status_msg = "Fique atento ao vencimento.";
        } else {
            $pct = ($dias_restantes > 30) ? 100 : ($dias_restantes / 30) * 100;
            $bar_color = "#00e676";
        }

        // Estilo do Cartão
        $planos_style = [
            'start' => ['nome' => 'START', 'bg' => 'linear-gradient(135deg, #2c3e50, #000000)', 'color' => '#fff'],
            'pro'   => ['nome' => 'PRO',   'bg' => 'linear-gradient(135deg, #EDC967 0%, #D4AF37 50%, #967711 100%)', 'color' => '#000'],
            'coach' => ['nome' => 'COACH', 'bg' => 'linear-gradient(135deg, #00c6ff, #0072ff)', 'color' => '#fff']
        ];

        $p_key = $user['plano_atual'] ?? 'start';
        $p_info = $planos_style[$p_key] ?? $planos_style['start'];

        echo '
        <section class="fade-in">
            <header class="dash-header">
                <h1>MINHA <span class="highlight-text">ASSINATURA</span></h1>
            </header>

            <div class="subscription-wrapper">
                
                <div class="sub-card-hero" style="background: '.$p_info['bg'].'; color: '.$p_info['color'].';">
                    <div class="card-shine"></div>
                    
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; position:relative; z-index:2;">
                        <img src="assets/img/icones/icon-nav.png" class="card-logo">
                        <span class="card-plan-name">'.$p_info['nome'].'</span>
                    </div>

                    <div style="margin-top:auto; position:relative; z-index:2;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                            <div>
                                <span style="font-size:0.7rem; opacity:0.8; display:block; letter-spacing:1px;">MEMBRO DESDE '.$ano_membro.'</span>
                                <span style="font-size:1.1rem; font-weight:600; letter-spacing:1px; text-transform:uppercase;">'.$user['nome'].'</span>
                            </div>
                            <div style="text-align:right;">
                                <span style="font-size:0.7rem; opacity:0.8; display:block;">VALIDADE</span>
                                <span style="font-size:1rem; font-weight:600;">'.date('d/m/y', strtotime($user['data_expiracao_plano'])).'</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sub-status-panel">
                    <div class="days-counter-wrapper">
                        <span class="days-number">'.$dias_restantes.'</span>
                        <span class="days-label">dias restantes</span>
                    </div>

                    <div class="progress-track">
                        <div class="progress-fill" style="width: '.$pct.'%; background: '.$bar_color.'; box-shadow: 0 0 15px '.$bar_color.';"></div>
                    </div>

                    <p class="status-text" style="color: '.$bar_color.';">'.$status_msg.'</p>

                    <button class="btn-renew" onclick="window.open(\'https://wa.me/5535999928473?text=Olá, quero renovar meu plano!\', \'_blank\')">
                        <i class="fa-brands fa-whatsapp"></i> Renovar Assinatura
                    </button>
                </div>

            </div>
        </section>';
        
        $pdo = null;
        break;




    // --- MENU GERAL (HUB DE NAVEGAÇÃO) ---
    case 'menu':

        // --- LÓGICA DO LINK DE INDICAÇÃO ---
        $codigo = $user['codigo_convite'] ?? 'ERRO'; // Pega o código do usuário logado

        // Detecta protocolo e domínio
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $dominio = $_SERVER['HTTP_HOST'];

        // Ajusta o diretório (remove '/ajax' se estiver sendo chamado de dentro dessa pasta)
        $caminho = dirname($_SERVER['PHP_SELF']);
        $caminho = str_replace('\\', '/', $caminho); // Corrige barras no Windows
        $caminho = str_replace('/ajax', '', $caminho); // Remove a pasta ajax para apontar pra raiz
        $caminho = rtrim($caminho, '/'); // Remove barra final se tiver

        // Monta o link final
        $link_indica = "{$protocolo}://{$dominio}{$caminho}/login.php?ref={$codigo}";

        require_once '../config/db_connect.php';
        $user_id = $_SESSION['user_id'];
        
        // Busca dados do usuário e do coach (se tiver)
        $sql = "SELECT u.nome, u.email, u.foto, u.tipo_conta, u.codigo_convite, u.coach_id, 
                       c.nome as nome_coach, c.foto as foto_coach, c.email as email_coach
                FROM usuarios u 
                LEFT JOIN usuarios c ON u.coach_id = c.id 
                WHERE u.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $foto = !empty($user['foto']) ? $user['foto'] : 'assets/img/user-default.png';
        $codigo = $user['codigo_convite'] ?? '---';
        $link_indica = "https://ryanborges.com/login.php?ref=" . $codigo;
        
        // Verifica se tem coach para decidir o layout do botão e do modal
        $tem_coach = !empty($user['coach_id']);
        $foto_coach = !empty($user['foto_coach']) ? $user['foto_coach'] : 'assets/img/user-default.png';

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
                    <div class="mph-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </div>

                <h3 class="section-label" style="margin-left: 10px;">PRINCIPAL</h3>
                <div class="menu-grid">
                    <div class="menu-card" onclick="carregarConteudo(\'treinos\')">
                        <div class="mc-icon" style="background: rgba(255, 186, 66, 0.1); color: var(--gold);">
                            <i class="fa-solid fa-dumbbell"></i>
                        </div>
                        <span>Meus Treinos</span>
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
                        <span>Ver Dieta</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'assinatura\')">
                        <div class="mc-icon" style="background: rgba(200, 100, 255, 0.1); color: #c864ff;">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <span>Assinatura</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'gerar_pdf\')">
                        <div class="mc-icon" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid #555;">
                            <i class="fa-solid fa-print"></i>
                        </div>
                        <span>Relatórios</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'novo_treino\')">
                        <div class="mc-icon" style="background: rgba(150, 50, 255, 0.1); color: #a855f7; border: 1px solid #a855f7;">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <span>Criar Treino</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'dieta_editor\')">
                        <div class="mc-icon" style="background: rgba(255, 145, 0, 0.1); color: #ff9100; border: 1px solid #ff9100;">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <span>Montar Dieta</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'tutoriais\')">
                        <div class="mc-icon" style="background: rgba(17, 32, 249, 0.1); color: #1c6bffff; border: 1px solid #1c6bffff;">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <span>Tutoriais do sistema</span>
                    </div>
                </div>

                <h3 class="section-label" style="margin-left: 10px; margin-top: 30px;">SISTEMA</h3>
                <div class="settings-list">
                    
                    <div class="setting-item" onclick="copiarLinkIndicacao(\''.$link_indica.'\')" style="cursor:pointer;">
                        <div class="st-left">
                            <i class="fa-solid fa-ticket" style="color: var(--gold);"></i>
                            <div>
                                <span style="display:block;">Indique e Ganhe</span>
                                <span style="display:block; font-size:0.7rem; color:#666;">Cód: <strong style="color:var(--gold)">'.$codigo.'</strong></span>
                            </div>
                        </div>
                        <i class="fa-regular fa-copy" style="font-size: 0.8rem; color: #666;"></i>
                    </div>

                    ';
                    if (!$tem_coach) {
                        // SEM COACH: Botão Vincular
                        echo '<div class="setting-item" onclick="abrirModalVincular()" style="cursor:pointer;">
                                <div class="st-left"><i class="fa-solid fa-user-plus" style="color: #fff;"></i><span>Vincular Personal</span></div>
                                <i class="fa-solid fa-chevron-right"></i>
                              </div>';
                    } else {
                        // COM COACH: Botão Meu Treinador
                        echo '<div class="setting-item" onclick="abrirModalVincular()" style="cursor:pointer;">
                                <div class="st-left"><i class="fa-solid fa-user-check" style="color: #00ff00;"></i>
                                    <div><span style="display:block;">Meu Treinador</span><span style="display:block; font-size:0.7rem; color:#666;">'.$user['nome_coach'].'</span></div>
                                </div>
                                <i class="fa-solid fa-gear" style="font-size: 0.8rem; color: #666;"></i>
                              </div>';
                    }
                    echo '

                    <a href="https://wa.me/5535999928473" target="_blank" class="setting-item">
                        <div class="st-left"><i class="fa-brands fa-whatsapp" style="color: #25D366;"></i><span>Suporte Whatsapp</span></div>
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.8rem; color: #666;"></i>
                    </a>

                    <div class="setting-item" onclick="window.location.href=\'index.php\'">
                        <div class="st-left"><i class="fa-solid fa-globe"></i><span>Página Inicial</span></div>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>

                    <div class="setting-item logout" onclick="window.location.href=\'actions/logout.php\'">
                        <div class="st-left"><i class="fa-solid fa-right-from-bracket"></i><span>Sair da Conta</span></div>
                    </div>
                </div>
                
                <div style="text-align:center; margin-top:40px; color:#444; font-size:0.7rem;"><p>Ryan Coach App v1.0</p></div>
                
                <div id="modalVincularCoach" class="modal-overlay" style="display:none;">
                    <div class="modal-content" style="max-width:400px; text-align:center;">
                        <button class="modal-close" onclick="fecharModalVincular()">&times;</button>
                        
                        ';
                        if (!$tem_coach) {
                            // --- CONTEÚDO PARA VINCULAR (Não tem coach) ---
                            echo '
                            <div style="background:rgba(218,165,32,0.1); width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px auto;">
                                <i class="fa-solid fa-user-plus" style="color:var(--gold); font-size:1.5rem;"></i>
                            </div>
                            <h3 style="color:#fff; margin-bottom:10px;">Vincular Treinador</h3>
                            <p style="color:#ccc; font-size:0.9rem; margin-bottom:20px;">Digite o código do seu Personal Trainer.</p>
                            
                            <form action="actions/aluno_vincular.php" method="POST">
                                <input type="hidden" name="acao" value="vincular">
                                <input type="text" name="codigo_coach" class="user-input" placeholder="Ex: RYAN10" style="text-align:center; text-transform:uppercase; font-size:1.2rem; letter-spacing:2px; margin-bottom:20px; max-width:100%;" required>
                                <button type="submit" class="btn-gold" style="width:100%; padding:12px;">CONECTAR</button>
                            </form>';
                        } else {
                            // --- CONTEÚDO PARA DESVINCULAR (Já tem coach) ---
                            echo '
                            <img src="'.$foto_coach.'" style="width:80px; height:80px; border-radius:50%; border:3px solid var(--gold); object-fit:cover; margin-bottom:15px;">
                            <h3 style="color:#fff; margin:0 0 5px 0;">'.$user['nome_coach'].'</h3>
                            <p style="color:#666; font-size:0.8rem; margin-bottom:25px;">'.$user['email_coach'].'</p>
                            
                            <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:10px; margin-bottom:25px; border:1px solid #333;">
                                <p style="color:#ccc; font-size:0.9rem; margin:0;">Você está vinculado a este treinador. Ele é responsável pelos seus treinos.</p>
                            </div>

                            <form action="actions/aluno_vincular.php" method="POST" onsubmit="return confirm(\'Tem certeza que deseja desvincular? Seus treinos atuais podem ser perdidos.\')">
                                <input type="hidden" name="acao" value="desvincular">
                                <button type="submit" style="background: rgba(255,66,66,0.1); color: #ff4242; border: 1px solid #ff4242; width:100%; padding:12px; border-radius:8px; cursor:pointer; font-weight:bold;">
                                    <i class="fa-solid fa-link-slash"></i> DESVINCULAR
                                </button>
                            </form>';
                        }
                        echo '
                    </div>
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
$pdo = null;
?>