<?php
// Inicia sessão se não estiver iniciada
if(session_status() === PHP_SESSION_NONE) session_start();

require_once '../config/db_connect.php';

// Segurança: Garante que é um Coach/Personal acessando
if (!isset($_SESSION['user_id']) || 
   ($_SESSION['tipo_conta'] !== 'personal' && $_SESSION['tipo_conta'] !== 'coach' && $_SESSION['tipo_conta'] !== 'admin')) {
    echo '<div style="padding:20px; color:#ccc;">Acesso negado.</div>';
    exit;
}

$coach_id = $_SESSION['user_id'];
$pagina = $_GET['pagina'] ?? 'dashboard';

// Pega nome para o cabeçalho
$nome_user = $_SESSION['user_nome'] ?? 'Coach';
$primeiro_nome = explode(' ', trim($nome_user))[0];

switch ($pagina) {
    
    // --- DASHBOARD (Visão Geral do Coach) ---
    case 'dashboard':
        // 1. Total de Alunos (Apenas os do Coach)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE coach_id = ? AND tipo_conta IN ('aluno', 'atleta')");
        $stmt->execute([$coach_id]);
        $total_alunos = $stmt->fetchColumn();

        // 2. Receita Mensal (Pagamentos dos alunos deste Coach)
        $sql_receita = "SELECT SUM(p.valor) as total 
                        FROM pagamentos p 
                        JOIN usuarios u ON p.usuario_id = u.id 
                        WHERE p.status = 'pago' 
                        AND u.coach_id = ? 
                        AND MONTH(p.data_pagamento) = MONTH(CURRENT_DATE()) 
                        AND YEAR(p.data_pagamento) = YEAR(CURRENT_DATE())";
        $stmt = $pdo->prepare($sql_receita);
        $stmt->execute([$coach_id]);
        $receita_mensal = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 3. Pendências (Apenas alunos do Coach)
        $sql_pend = "SELECT COUNT(*) FROM pagamentos p JOIN usuarios u ON p.usuario_id = u.id WHERE p.status = 'pendente' AND u.coach_id = ?";
        $stmt = $pdo->prepare($sql_pend);
        $stmt->execute([$coach_id]);
        $total_pendencias = $stmt->fetchColumn();

        // 4. Listas (Vencimentos e Novos)
        $sql_venc = "SELECT p.data_vencimento, p.valor, u.nome, u.foto 
                     FROM pagamentos p JOIN usuarios u ON p.usuario_id = u.id 
                     WHERE p.status = 'pendente' AND u.coach_id = ? 
                     ORDER BY p.data_vencimento ASC LIMIT 4";
        $stmt = $pdo->prepare($sql_venc);
        $stmt->execute([$coach_id]);
        $lista_vencimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql_novos = "SELECT nome, foto, data_cadastro FROM usuarios WHERE coach_id = ? ORDER BY id DESC LIMIT 4";
        $stmt = $pdo->prepare($sql_novos);
        $stmt->execute([$coach_id]);
        $lista_novos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '
            <section id="admin-dash">
                <header class="dash-header">
                    <h1>OLÁ, <span class="highlight-text">'.strtoupper($primeiro_nome).'.</span></h1>
                    <p style="color: #888;">Painel do Treinador</p>
                </header>

                <div class="stats-row">
                    <div class="glass-card">
                        <div class="card-label">MEUS ALUNOS</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #00ff00; border-color: #00ff00;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_alunos.'</h3>
                                <p>Ativos</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">FATURAMENTO (MÊS)</div>
                        <div class="card-body">
                            <div class="icon-box">
                                <i class="fa-solid fa-brazilian-real-sign"></i>
                            </div>
                            <div class="info-box">
                                <h3>R$ '.number_format($receita_mensal, 2, ',', '.').'</h3>
                                <p>Receita Atual</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">A RECEBER</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #ff4242; border-color: #ff4242;">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_pendencias.'</h3>
                                <p>Pendentes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="insights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 10px;">
                    <div class="glass-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: #fff; font-family: Orbitron; font-size: 1rem; margin:0;">
                                <i class="fa-regular fa-calendar-xmark" style="color: #ff4242; margin-right: 10px;"></i> VENCIMENTOS
                            </h3>
                        </div>
                        <div style="padding: 10px;">';
                        if(count($lista_vencimentos) > 0) {
                            foreach($lista_vencimentos as $v) {
                                $foto = !empty($v['foto']) ? $v['foto'] : 'assets/img/user-default.png';
                                $data = date('d/m', strtotime($v['data_vencimento']));
                                $is_atrasado = strtotime($v['data_vencimento']) < time();
                                $cor_data = $is_atrasado ? '#ff4242' : '#ccc';
                                $texto_data = $is_atrasado ? 'VENCEU '.$data : 'VENCE '.$data;
                                echo '<div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; margin-bottom: 5px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="'.$foto.'" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                                        <div><h4 style="color: #ddd; font-size: 0.9rem; margin: 0;">'.$v['nome'].'</h4><span style="color: var(--gold); font-size: 0.8rem;">R$ '.number_format($v['valor'], 2, ',', '.').'</span></div>
                                    </div>
                                    <span style="color: '.$cor_data.'; font-size: 0.75rem; font-weight: bold;">'.$texto_data.'</span>
                                </div>';
                            }
                        } else { echo '<p style="text-align: center; color: #666; padding: 20px;">Sem pendências.</p>'; }
        echo '          </div>
                    </div>
                    <div class="glass-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: #fff; font-family: Orbitron; font-size: 1rem; margin:0;">
                                <i class="fa-solid fa-rocket" style="color: var(--gold); margin-right: 10px;"></i> RECENTES
                            </h3>
                        </div>
                        <div style="padding: 10px;">';
                        if(count($lista_novos) > 0) {
                            foreach($lista_novos as $n) {
                                $foto = !empty($n['foto']) ? $n['foto'] : 'assets/img/user-default.png';
                                $data_cad = date('d/m', strtotime($n['data_cadastro']));
                                echo '<div style="display: flex; align-items: center; gap: 12px; padding: 12px; margin-bottom: 5px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                        <img src="'.$foto.'" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                                        <div><h4 style="color: #ddd; font-size: 0.9rem; margin: 0;">'.$n['nome'].'</h4><span style="color: #666; font-size: 0.75rem;">Entrou em '.$data_cad.'</span></div>
                                      </div>';
                            }
                        } else { echo '<p style="text-align: center; color: #666; padding: 20px;">Nenhum novo aluno.</p>'; }
        echo '          </div>
                    </div>
                </div>
            </section>';
        break;

    // --- LISTA DE ALUNOS ---
    case 'alunos':
        // Filtra por COACH_ID
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE coach_id = ? AND tipo_conta IN ('aluno', 'atleta') ORDER BY nome ASC");
        $stmt->execute([$coach_id]);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_alunos = count($alunos);

        echo '
            <section id="gerenciar-alunos">
                <header class="dash-header">
                    <h1>MEUS <span class="highlight-text">ALUNOS</span></h1>
                    <p class="text-desc">Gerencie seus atletas.</p>
                </header>
                <div class="glass-card mt-large">
                    <div class="section-header-row">
                        <div style="flex: 1; position: relative; max-width: 400px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666;"></i>
                            <input type="text" id="searchAluno" onkeyup="filtrarAlunos()" placeholder="Buscar..." class="admin-input" style="padding-left: 40px;">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table" id="tabelaAlunos">
                            <thead><tr><th>ALUNO</th><th>CONTATO</th><th>AÇÃO</th></tr></thead>
                            <tbody>';
                            if ($total_alunos > 0) {
                                foreach ($alunos as $a) {
                                    $foto = !empty($a['foto']) ? $a['foto'] : 'assets/img/user-default.png';
                                    $zap = "https://wa.me/55".preg_replace('/[^0-9]/', '', $a['telefone']);
                                    $dados_json = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');
                                    echo '<tr>
                                        <td><div class="user-cell"><img src="'.$foto.'" class="table-avatar"><div><span style="display:block; font-weight:bold; color:#fff;">'.$a['nome'].'</span><span style="font-size:0.8rem; color:#666;">'.$a['email'].'</span></div></div></td>
                                        <td><div style="display:flex; align-items:center; gap:10px;"><a href="'.$zap.'" target="_blank" class="btn-action-icon btn-confirm"><i class="fa-brands fa-whatsapp"></i></a><span style="color:#ccc;">'.$a['telefone'].'</span></div></td>
                                        <td style="text-align: right;"><button class="btn-gold" style="padding: 8px 20px; font-size: 0.75rem;" onclick=\'abrirPainelAluno('.$dados_json.')\'><i class="fa-solid fa-gear"></i> OPÇÕES</button></td>
                                    </tr>';
                                }
                            } else { echo '<tr><td colspan="3" style="text-align:center; padding:30px;">Nenhum aluno encontrado.</td></tr>'; }
        echo '              </tbody>
                        </table>
                    </div>
                </div>
            </section>';
        
        // MODAIS DE ALUNO (Mantidos iguais ao admin, funcionam com o mesmo JS)
        echo '
            <div id="modalGerenciarAluno" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="max-width: 500px;">
                    <button class="modal-close" onclick="fecharPainelAluno()">&times;</button>
                    <div style="text-align:center; margin-bottom:30px;">
                        <img id="hub-foto" src="" style="width:100px; height:100px; border-radius:50%; border:3px solid var(--gold); object-fit:cover; margin-bottom:10px;">
                        <h3 id="hub-nome" style="color:#fff; margin:0; font-family:Orbitron;">Nome</h3>
                        <span id="hub-email" style="color:#888; font-size:0.9rem;">email</span>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="menu-card" onclick="hubAcao(\'historico\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;"><i class="fa-solid fa-dumbbell" style="font-size:1.5rem; color:var(--gold); margin-bottom:5px;"></i><span style="display:block; font-size:0.8rem; color:#ccc;">Histórico</span></div>
                        <div class="menu-card" onclick="hubAcao(\'avaliacao_lista\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;"><i class="fa-solid fa-ruler-combined" style="font-size:1.5rem; color:#00e676; margin-bottom:5px;"></i><span style="display:block; font-size:0.8rem; color:#ccc;">Avaliações</span></div>
                        <div class="menu-card" onclick="hubAcao(\'dieta_editor\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;"><i class="fa-solid fa-utensils" style="font-size:1.5rem; color:#ff4242; margin-bottom:5px;"></i><span style="display:block; font-size:0.8rem; color:#ccc;">Dieta</span></div>
                        <div class="menu-card" onclick="hubAcao(\'editar\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;"><i class="fa-solid fa-user-pen" style="font-size:1.5rem; color:#fff; margin-bottom:5px;"></i><span style="display:block; font-size:0.8rem; color:#ccc;">Editar</span></div>
                    </div>
                </div>
            </div>
            
            <div id="modalEditarAluno" class="modal-overlay" style="display:none;">
                <div class="modal-content">
                    <button class="modal-close" onclick="closeEditModal()">&times;</button>
                    <h3 class="section-title" style="color: var(--gold); margin-bottom: 20px; text-align: center;"><i class="fa-solid fa-user-pen"></i> Editar Aluno</h3>
                    <form action="actions/admin_aluno.php" method="POST">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="nivel" value="aluno"> <div style="margin-bottom:15px;"><label class="input-label">Nome</label><input type="text" name="nome" id="edit_nome" class="admin-input" required></div>
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <div style="flex:1;"><label class="input-label">Email</label><input type="email" name="email" id="edit_email" class="admin-input" required></div>
                            <div style="flex:1;"><label class="input-label">Telefone</label><input type="text" name="telefone" id="edit_telefone" class="admin-input"></div>
                        </div>
                        <div style="margin-bottom:15px;"><label class="input-label">Vencimento</label><input type="date" name="data_expiracao" id="edit_expiracao" class="admin-input"></div>
                        <div style="margin-bottom:20px;"><label style="color:#ff4242; font-size:0.8rem;">Nova Senha (Opcional)</label><input type="text" name="nova_senha" class="admin-input" placeholder="Deixe vazio para manter"></div>
                        <button type="submit" class="btn-gold" style="width:100%; padding:15px;">SALVAR</button>
                    </form>
                </div>
            </div>';
        break;

    // --- EDITOR DE TREINOS (Coach só vê os treinos que ELE criou) ---
    // --- EDITOR DE TREINOS (COACH) ---
    case 'treinos_editor':
        // 1. LISTA DE TREINOS DO COACH
        // IMPORTANTE: Tabela TREINOS usa 'criador_id'
        $sql_list = "SELECT t.*, u.nome as nome_aluno, u.foto as foto_aluno 
                     FROM treinos t 
                     JOIN usuarios u ON t.aluno_id = u.id 
                     WHERE t.criador_id = ? 
                     ORDER BY t.criado_em DESC";
        $stmt = $pdo->prepare($sql_list);
        $stmt->execute([$coach_id]);
        $treinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. LISTA DE ALUNOS DO COACH (Para o Dropdown do Modal)
        // Aqui usamos 'coach_id' na tabela usuarios
        $stmt_a = $pdo->prepare("SELECT id, nome, foto FROM usuarios WHERE coach_id = ? AND tipo_conta IN ('aluno','atleta') ORDER BY nome ASC");
        $stmt_a->execute([$coach_id]);
        $alunos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

        echo '<section id="editor-treinos">
            <header class="dash-header">
                <h1>EDITOR DE <span class="highlight-text">TREINOS</span></h1>
            </header>

            <div class="glass-card">
                <div class="section-header-row">
                    <h3 class="section-title" style="margin:0"><i class="fa-solid fa-list"></i> PLANILHAS ATIVAS</h3>
                    <button class="btn-gold" onclick="toggleNovoTreino()"><i class="fa-solid fa-plus"></i> NOVO TREINO</button>
                </div>

                <div class="table-responsive">
                    <table class="admin-table responsive-table">
                        <thead><tr><th>ALUNO</th><th>TREINO</th><th>TIPO</th><th>VIGÊNCIA</th><th>AÇÃO</th></tr></thead>
                        <tbody>';
                        
                        if (count($treinos) > 0) {
                            foreach ($treinos as $t) {
                                $foto = !empty($t['foto_aluno']) ? $t['foto_aluno'] : 'assets/img/user-default.png';
                                $inicio = date('d/m', strtotime($t['data_inicio']));
                                $fim = date('d/m', strtotime($t['data_fim']));
                                $corBadge = ($t['nivel_plano'] == 'basico') ? '#ccc' : '#FFBA42';
                                echo '<tr>
                                    <td><div class="user-cell"><img src="'.$foto.'" class="table-avatar"><span>'.$t['nome_aluno'].'</span></div></td>
                                    <td><strong style="color:#fff;">'.$t['nome'].'</strong><br><span style="font-size:0.8rem; color:#666;">'.$t['divisao_nome'].'</span></td>
                                    <td><span class="status-badge" style="color:'.$corBadge.'; border-color:'.$corBadge.'; background:transparent;">'.strtoupper($t['nivel_plano']).'</span></td>
                                    <td style="color:#888;">'.$inicio.' a '.$fim.'</td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                                            <button class="btn-action-icon btn-delete" onclick="deletarTreino('.$t['id'].')"><i class="fa-solid fa-trash"></i></button>
                                            <button class="btn-gold" style="padding: 5px 15px; font-size: 0.8rem;" onclick="carregarConteudo(\'treino_painel&id='.$t['id'].'\')">GERENCIAR</button>
                                        </div>
                                    </td>
                                </tr>';
                            }
                        } else { 
                            echo '<tr><td colspan="5" style="text-align:center; padding:30px;">Nenhum treino encontrado.</td></tr>'; 
                        }
        echo '          </tbody>
                    </table>
                </div>
            </div>
            
            <div id="box-novo-treino" class="modal-overlay" style="display:none;">
                <div class="modal-content selection-modal" style="max-width: 650px; text-align: left; position: relative;">
                    
                    <button class="modal-close" onclick="toggleNovoTreino()">&times;</button>

                    <h3 class="section-title" style="color: var(--gold); margin-bottom: 25px; text-align: center;">
                        <i class="fa-solid fa-dumbbell"></i> Criar Nova Estrutura
                    </h3>
                    
                    <form id="formNovoTreino" onsubmit="criarTreino(event)">
                        
                        <div class="form-row">
                            <div class="form-col" style="position: relative;">
                                <label class="input-label">Selecione o Atleta</label>
                                
                                <input type="text" id="busca-aluno-treino" class="admin-input" placeholder="Digite para buscar..." autocomplete="off" onkeyup="filtrarAlunosTreino()">
                                
                                <input type="hidden" name="aluno_id" id="id-aluno-treino-selecionado" required>
                                
                                <div id="dropdown-alunos-treino" class="custom-dropdown-list" style="display:none;">';
                                
                                // LOOP PHP (Alunos filtrados do Coach)
                                if (count($alunos) > 0) {
                                    foreach($alunos as $al) {
                                        $ft = !empty($al['foto']) ? $al['foto'] : 'assets/img/user-default.png';
                                        $nome_seguro = addslashes($al['nome']); // Protege aspas
                                        
                                        echo '<div class="dropdown-item" onclick="selecionarAlunoTreino('.$al['id'].', \''.$nome_seguro.'\')">
                                                <img src="'.$ft.'">
                                                <span>'.$al['nome'].'</span>
                                              </div>';
                                    }
                                } else {
                                    echo '<div class="dropdown-item" style="cursor: default; color: #888;">Nenhum aluno encontrado</div>';
                                }
                                // FIM LOOP

        echo '                  </div>
                            </div>

                            <div class="form-col">
                                <label class="input-label">Nome do Planejamento</label>
                                <input type="text" name="nome" class="admin-input" placeholder="Ex: Hipertrofia Fase 1" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <label class="input-label">Tipo de Plano</label>
                                <select name="nivel" class="admin-input" id="selectNivel" onchange="togglePeriodizacao()" required>
                                    <option value="basico">Básico (Ficha Fixa)</option>
                                    <option value="avancado">Avançado (Periodizado)</option>
                                    <option value="premium">Premium (Periodizado +)</option>
                                </select>
                            </div>
                            <div class="form-col">
                                <label class="input-label">Data de Início</label>
                                <input type="date" name="data_inicio" class="admin-input" required value="'.date('Y-m-d').'">
                            </div>
                            <div class="form-col" style="flex: 0 0 120px;">
                                <label class="input-label">Divisão</label>
                                <input type="text" name="divisao" class="admin-input" placeholder="ABC" maxlength="5" style="text-transform:uppercase;" required>
                            </div>
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label class="input-label">Dias de Treino</label>
                            <div class="days-selector">
                                <label><input type="checkbox" name="dias_semana[]" value="1" class="day-checkbox"><span class="day-label">SEG</span></label>
                                <label><input type="checkbox" name="dias_semana[]" value="2" class="day-checkbox"><span class="day-label">TER</span></label>
                                <label><input type="checkbox" name="dias_semana[]" value="3" class="day-checkbox"><span class="day-label">QUA</span></label>
                                <label><input type="checkbox" name="dias_semana[]" value="4" class="day-checkbox"><span class="day-label">QUI</span></label>
                                <label><input type="checkbox" name="dias_semana[]" value="5" class="day-checkbox"><span class="day-label">SEX</span></label>
                                <label><input type="checkbox" name="dias_semana[]" value="6" class="day-checkbox"><span class="day-label">SÁB</span></label>
                                <label><input type="checkbox" name="dias_semana[]" value="7" class="day-checkbox"><span class="day-label">DOM</span></label>
                            </div>
                        </div>

                        <div id="aviso-periodizacao" class="alert-box" style="display:none;">
                            <span class="alert-title">Modo Periodização Ativo</span>
                            <p class="alert-text">Serão gerados 12 Microciclos automaticamente.</p>
                        </div>

                        <button type="submit" class="btn-gold" style="width:100%; margin-top: 15px; padding: 15px;">CRIAR ESTRUTURA</button>
                    </form>
                </div>
            </div>
        </section>';
        break;

    // --- FINANCEIRO (Coach) ---
    case 'financeiro':
        require_once '../config/db_connect.php';
        
        // Garante que temos o ID do coach (seja da sessão ou variável definida antes)
        $coach_id = $_SESSION['user_id'];

        // 1. CÁLCULOS (Filtrando por Coach)
        $sql_fat = "SELECT SUM(valor) as total FROM pagamentos p 
                    JOIN usuarios u ON p.usuario_id = u.id 
                    WHERE p.status = 'pago' 
                    AND u.coach_id = ? 
                    AND MONTH(p.data_pagamento) = MONTH(CURRENT_DATE()) 
                    AND YEAR(p.data_pagamento) = YEAR(CURRENT_DATE())";
        $stmt_fat = $pdo->prepare($sql_fat);
        $stmt_fat->execute([$coach_id]);
        $faturamento = $stmt_fat->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $sql_pend = "SELECT SUM(valor) as total FROM pagamentos p 
                     JOIN usuarios u ON p.usuario_id = u.id 
                     WHERE p.status = 'pendente' 
                     AND u.coach_id = ?";
        $stmt_pend = $pdo->prepare($sql_pend);
        $stmt_pend->execute([$coach_id]);
        $pendente = $stmt_pend->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. LISTA DE ALUNOS (CORRIGIDO: Apenas vinculados ao Coach Logado)
        $stmt_alunos = $pdo->prepare("SELECT id, nome, foto FROM usuarios WHERE coach_id = ? AND tipo_conta IN ('atleta', 'aluno') ORDER BY nome ASC");
        $stmt_alunos->execute([$coach_id]);
        $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

        // 3. HISTÓRICO (Filtrando por Coach)
        $sql_hist = "SELECT p.*, u.nome as nome_aluno, u.foto as foto_aluno 
                     FROM pagamentos p 
                     LEFT JOIN usuarios u ON p.usuario_id = u.id 
                     WHERE u.coach_id = ?
                     ORDER BY p.id DESC LIMIT 20";
        $stmt_hist = $pdo->prepare($sql_hist);
        $stmt_hist->execute([$coach_id]);
        $transacoes = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

        echo '
            <section id="financeiro">
                <header class="dash-header">
                    <h1>CONTROLE <span class="highlight-text">FINANCEIRO</span></h1>
                    <p class="text-desc">Gestão de caixa e assinaturas</p>
                </header>

                <div class="stats-row">
                    <div class="glass-card">
                        <div class="card-label">FATURAMENTO (MÊS ATUAL)</div>
                        <div class="card-body">
                            <div class="icon-box success"><i class="fa-solid fa-arrow-trend-up"></i></div>
                            <div class="info-box">
                                <h3>R$ '.number_format($faturamento, 2, ',', '.').'</h3>
                                <p class="text-muted">Entradas confirmadas</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">A RECEBER (PENDENTE)</div>
                        <div class="card-body">
                            <div class="icon-box gold"><i class="fa-solid fa-clock"></i></div>
                            <div class="info-box">
                                <h3>R$ '.number_format($pendente, 2, ',', '.').'</h3>
                                <p class="text-muted">Previsão de entrada</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card mt-large">
                    <div class="section-header-row">
                        <h3 class="section-title" style="margin:0">HISTÓRICO DE CAIXA</h3>
                        <button class="btn-gold" onclick="document.getElementById(\'modalLancamento\').style.display=\'flex\'">
                            <i class="fa-solid fa-plus"></i> NOVO LANÇAMENTO
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="th-admin-table">ALUNO</th>
                                    <th class="th-admin-table">DESCRIÇÃO</th>
                                    <th class="th-admin-table">VENCIMENTO</th>
                                    <th class="th-admin-table">VALOR</th>
                                    <th class="th-admin-table">STATUS</th>
                                    <th class="th-admin-table" id="th-acao">AÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                            if (count($transacoes) > 0) {
                                foreach ($transacoes as $t) {
                                    $statusClass = ($t['status'] == 'pago') ? 'pago' : 'pendente';
                                    
                                    // Data correta
                                    $dataShow = $t['data_vencimento'];
                                    if($t['status'] == 'pago' && !empty($t['data_pagamento'])) {
                                        $dataShow = $t['data_pagamento'];
                                    }
                                    $dataExibicao = date('d/m/Y', strtotime($dataShow));
                                    
                                    $fotoUser = !empty($t['foto_aluno']) ? $t['foto_aluno'] : 'assets/img/user-default.png';
                                    $nomeUser = !empty($t['nome_aluno']) ? $t['nome_aluno'] : 'Avulso';

                                    echo '<tr>
                                        <td>
                                            <div class="user-cell">
                                                <img src="'.$fotoUser.'" class="table-avatar">
                                                <span>'.$nomeUser.'</span>
                                            </div>
                                        </td>
                                        <td>'.$t['descricao'].'</td>
                                        <td>'.$dataExibicao.'</td>
                                        <td><strong>R$ '.number_format($t['valor'], 2, ',', '.').'</strong></td>
                                        <td><span class="status-badge '.$statusClass.'">'.strtoupper($t['status']).'</span></td>
                                        <td style="text-align:right;">
                                            <div style="display:flex; gap:10px; justify-content:flex-end;">';
                                                
                                                if ($t['status'] == 'pendente') {
                                                    echo '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'pagar\')" class="btn-action-icon btn-confirm" title="Confirmar Pagamento"><i class="fa-solid fa-check"></i></button>';
                                                } else {
                                                    echo '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'estornar\')" class="btn-action-icon btn-undo" title="Desfazer"><i class="fa-solid fa-rotate-left"></i></button>';
                                                }

                                                echo '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'excluir\')" class="btn-action-icon btn-delete" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" style="text-align:center; padding: 20px; color: #666;">Nenhum lançamento encontrado.</td></tr>';
                            }

        echo '              </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <div id="modalLancamento" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="overflow:visible;"> 
                    <button class="modal-close" onclick="fecharModalFinanceiro()">&times;</button>
                    
                    <h3 class="section-title" style="color:var(--gold); margin-bottom:20px; text-align:center;">
                        <i class="fa-solid fa-money-bill-wave"></i> Novo Lançamento
                    </h3>
                    
                    <form id="formLancamentoFinanceiro">
                        
                        <div style="margin-bottom:15px; position:relative;">
                            <label class="input-label">Aluno</label>
                            
                            <input type="text" id="busca-aluno-fin" class="admin-input" placeholder="Digite para buscar..." autocomplete="off" onkeyup="filtrarAlunosFinanceiro()">
                            <input type="hidden" name="usuario_id" id="id-aluno-fin" required>
                            
                            <div id="dropdown-alunos-fin" class="custom-dropdown-list" style="display:none; position:absolute; width:100%; max-height:150px; overflow-y:auto; background:#222; border:1px solid #444; z-index:1000;">';
                                
                                if (count($alunos) > 0) {
                                    foreach($alunos as $al) {
                                        $ft = !empty($al['foto']) ? $al['foto'] : 'assets/img/user-default.png';
                                        $nome_safe = addslashes($al['nome']);
                                        
                                        echo '<div class="dropdown-item" style="padding:10px; border-bottom:1px solid #333; cursor:pointer; display:flex; align-items:center; gap:10px;" onclick="selecionarAlunoFinanceiro('.$al['id'].', \''.$nome_safe.'\')">
                                                <img src="'.$ft.'" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                                                <span style="color:#fff;">'.$al['nome'].'</span>
                                              </div>';
                                    }
                                } else {
                                    echo '<div style="padding:10px; color:#888;">Nenhum aluno vinculado.</div>';
                                }

        echo '              </div>
                        </div>

                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex:2;">
                                <label class="input-label">Descrição</label>
                                <input type="text" name="descricao" class="admin-input" placeholder="Ex: Mensalidade" required>
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Valor (R$)</label>
                                <input type="number" name="valor" step="0.01" class="admin-input" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <div style="flex:1;">
                                <label class="input-label">Vencimento</label>
                                <input type="date" name="data_vencimento" class="admin-input" required value="'.date('Y-m-d').'">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Status</label>
                                <select name="status" class="admin-input">
                                    <option value="pendente">Pendente</option>
                                    <option value="pago">Pago</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-gold" style="width:100%; padding:15px;">SALVAR</button>
                    </form>
                </div>
            </div>';
        break;
    // --- MENU (Mobile/Geral) ---
    case 'admin_menu':
        // 1. Busca dados do Coach (Incluindo o código de convite)
        $stmt = $pdo->prepare("SELECT nome, foto, codigo_convite FROM usuarios WHERE id = ?");
        $stmt->execute([$coach_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $foto = !empty($coach['foto']) ? $coach['foto'] : 'assets/img/user-default.png';
        $codigo = $coach['codigo_convite'] ?? 'SEMCODIGO';
        
        // Link de Indicação (Ajuste o domínio se necessário)
        $link_indica = "https://ryancoach.com/login.php?ref=" . $codigo;

        echo '<section id="admin-hub" class="fade-in">
                
                <div class="menu-profile-header" onclick="carregarConteudo(\'perfil\')" style="background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); margin-bottom: 25px; padding: 20px; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="'.$foto.'" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #ff4242;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #fff; font-size: 1.1rem;">'.$coach['nome'].'</h3>
                            <span class="usuario-level" style="color: #ff4242; background: rgba(255, 66, 66, 0.1);">COACH</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: #666;"></i>
                </div>

                <h3 class="section-label" style="margin-left: 10px; color: #666; font-size: 0.8rem; font-weight: bold; margin-bottom: 10px;">FERRAMENTAS</h3>
                <div class="menu-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
                    <div class="menu-card" onclick="carregarConteudo(\'alunos\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-users" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Meus Alunos</span>
                    </div>
                    <div class="menu-card" onclick="carregarConteudo(\'treinos_editor\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-dumbbell" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Treinos</span>
                    </div>
                    <div class="menu-card" onclick="carregarConteudo(\'financeiro\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-sack-dollar" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Financeiro</span>
                    </div>
                    <div class="menu-card" onclick="carregarConteudo(\'perfil\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-gear" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Perfil</span>
                    </div>
                </div>

                <div class="settings-list" style="background: #161616; border-radius: 16px; border: 1px solid #333;">
                    
                    <div class="setting-item" onclick="copiarLinkIndicacao(\''.$link_indica.'\')" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="background: rgba(218,165,32,0.1); width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                <i class="fa-solid fa-link" style="color: var(--gold); font-size: 1rem;"></i>
                            </div>
                            <div>
                                <span style="display: block; color: #fff; font-weight: bold;">Link de Convite (Alunos)</span>
                                <span style="display: block; color: #666; font-size: 0.75rem;">Seu código: <strong style="color:var(--gold)">'.$codigo.'</strong></span>
                            </div>
                        </div>
                        <i class="fa-regular fa-copy" style="color: #666;"></i>
                    </div>

                    <div class="setting-item" onclick="window.location.href=\'index.php\'" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer;">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <i class="fa-solid fa-globe" style="color: #888; margin-left: 10px;"></i>
                            <span style="color: #fff;">Ver Site Principal</span>
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square" style="color: #444; font-size: 0.8rem;"></i>
                    </div>

                    <div class="setting-item" onclick="window.location.href=\'actions/logout.php\'" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <i class="fa-solid fa-right-from-bracket" style="color: #ff4242; margin-left: 10px;"></i>
                            <span style="color: #ff4242;">Sair do Sistema</span>
                        </div>
                    </div>
                </div>

                </section>';
        break;

    // --- REUTILIZAÇÃO DO CÓDIGO DO ADMIN PARA PÁGINAS COMUNS ---
    // Como a lógica visual de 'treino_painel', 'aluno_historico', etc. é idêntica
    // e nós já filtramos o acesso nas listas anteriores, podemos incluir o arquivo admin.
    // (Isso evita duplicar 1000 linhas de código desnecessariamente)
    
    case 'aluno_avaliacoes':
    case 'aluno_progresso':
    case 'aluno_historico':
    case 'dieta_editor':
    case 'treino_painel':
    case 'perfil':
        include 'get_admin_conteudo.php';
        break;

    default:
        echo '<section><h1>Página não encontrada</h1></section>';
        break;
}
?>