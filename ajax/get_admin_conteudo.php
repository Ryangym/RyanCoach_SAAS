<?php
if(session_status() === PHP_SESSION_NONE) session_start();

$pagina = $_GET['pagina'] ?? 'dashboard';

// Pega o nome do Admin
$nome_admin = $_SESSION['user_nome'] ?? 'Admin';
$partes_admin = explode(' ', trim($nome_admin));
$primeiro_nome_admin = strtoupper($partes_admin[0]);

switch ($pagina) {
    case 'dashboard':
        require_once '../config/db_connect.php';

        // 1. TOTAIS (Mantido)
        $query_alunos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_conta = 'atleta'");
        $total_alunos = $query_alunos->fetchColumn();

        $sql_receita = "SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())";
        $query_receita = $pdo->query($sql_receita);
        $receita_mensal = $query_receita->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $sql_pendencias = "SELECT COUNT(*) FROM pagamentos WHERE status = 'pendente'";
        $query_pendencias = $pdo->query($sql_pendencias);
        $total_pendencias = $query_pendencias->fetchColumn();

        // 2. NOVAS QUERIES INTELIGENTES
        
        // A. Próximos Vencimentos (Status Pendente, ordenado por data mais próxima)
        $sql_vencimentos = "SELECT p.data_vencimento, p.valor, u.nome, u.foto 
                            FROM pagamentos p 
                            JOIN usuarios u ON p.usuario_id = u.id 
                            WHERE p.status = 'pendente' 
                            ORDER BY p.data_vencimento ASC 
                            LIMIT 4";
        $lista_vencimentos = $pdo->query($sql_vencimentos)->fetchAll(PDO::FETCH_ASSOC);

        // B. Novos Alunos (Últimos cadastros)
        $sql_novos = "SELECT nome, foto, data_cadastro 
                      FROM usuarios 
                      WHERE tipo_conta = 'atleta' 
                      ORDER BY id DESC 
                      LIMIT 4";
        $lista_novos = $pdo->query($sql_novos)->fetchAll(PDO::FETCH_ASSOC);

        echo '
            <section id="admin-dash">
                <header class="dash-header">
                    <h1>OLÁ, <span class="highlight-text">'.$primeiro_nome_admin.'.</span></h1>
                    <p style="color: #888;">Visão geral do desempenho da academia</p>
                </header>

                <div class="stats-row">
                    <div class="glass-card">
                        <div class="card-label">ALUNOS TOTAIS</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #00ff00; border-color: #00ff00;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_alunos.'</h3>
                                <p>Cadastrados</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">RECEITA (MÊS)</div>
                        <div class="card-body">
                            <div class="icon-box">
                                <i class="fa-solid fa-brazilian-real-sign"></i>
                            </div>
                            <div class="info-box">
                                <h3>R$ '.number_format($receita_mensal, 2, ',', '.').'</h3>
                                <p>Faturamento Atual</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">PENDÊNCIAS</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #ff4242; border-color: #ff4242;">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_pendencias.'</h3>
                                <p>Pagamentos em aberto</p>
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
                            <span style="font-size: 0.7rem; color: #666; text-transform: uppercase;">Prioridade</span>
                        </div>
                        
                        <div style="padding: 10px;">';
                        
                        if(count($lista_vencimentos) > 0) {
                            foreach($lista_vencimentos as $v) {
                                $foto = !empty($v['foto']) ? $v['foto'] : 'assets/img/icones/user-default.png';
                                $data = date('d/m', strtotime($v['data_vencimento']));
                                
                                // Lógica visual para data (se já passou, fica vermelho forte)
                                $is_atrasado = strtotime($v['data_vencimento']) < time();
                                $cor_data = $is_atrasado ? '#ff4242' : '#ccc';
                                $texto_data = $is_atrasado ? 'VENCEU '.$data : 'VENCE '.$data;

                                echo '
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; margin-bottom: 5px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="'.$foto.'" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #555;">
                                        <div>
                                            <h4 style="color: #ddd; font-size: 0.9rem; margin: 0;">'.$v['nome'].'</h4>
                                            <span style="color: var(--gold); font-size: 0.8rem;">R$ '.number_format($v['valor'], 2, ',', '.').'</span>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="color: '.$cor_data.'; font-size: 0.75rem; font-weight: bold; display: block;">'.$texto_data.'</span>
                                        <button class="btn-gold" style="padding: 2px 8px; font-size: 0.6rem; height: auto;" onclick="carregarConteudo(\'financeiro\')">COBRAR</button>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '<p style="text-align: center; color: #666; padding: 20px;">Nenhuma pendência próxima.</p>';
                        }

        echo '          </div>
                    </div>

                    <div class="glass-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: #fff; font-family: Orbitron; font-size: 1rem; margin:0;">
                                <i class="fa-solid fa-rocket" style="color: var(--gold); margin-right: 10px;"></i> NOVOS MEMBROS
                            </h3>
                            <span style="font-size: 0.7rem; color: #666; text-transform: uppercase;">Crescimento</span>
                        </div>
                        
                        <div style="padding: 10px;">';
                        
                        if(count($lista_novos) > 0) {
                            foreach($lista_novos as $n) {
                                $foto = !empty($n['foto']) ? $n['foto'] : 'assets/img/icones/user-default.png';
                                $data_cadastro = date('d/m', strtotime($n['data_cadastro']));

                                echo '
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; margin-bottom: 5px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="'.$foto.'" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #555;">
                                        <div>
                                            <h4 style="color: #ddd; font-size: 0.9rem; margin: 0;">'.$n['nome'].'</h4>
                                            <span style="color: #666; font-size: 0.75rem;">Entrou em '.$data_cadastro.'</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 5px;">
                                        <button style="background: rgba(255,255,255,0.1); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;" title="Enviar Treino" onclick="carregarConteudo(\'treinos_editor\')"><i class="fa-solid fa-dumbbell"></i></button>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '<p style="text-align: center; color: #666; padding: 20px;">Nenhum cadastro recente.</p>';
                        }

        echo '          </div>
                    </div>

                </div>
            </section>
        ';
        break;

    case 'alunos':
        require_once '../config/db_connect.php';
        
        $sql = "SELECT * FROM usuarios WHERE tipo_conta != 'master' ORDER BY nome ASC";
        $alunos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $total_alunos = count($alunos);

        echo '
            <section id="gerenciar-alunos">
                <header class="dash-header">
                    <h1>GERENCIAR <span class="highlight-text">USUÁRIOS</span></h1>
                    <p class="text-desc">Painel de controle de todos os usuários do sistema.</p>
                </header>
                
                <div class="glass-card mt-large">
                    <div class="section-header-row">
                        <div style="flex: 1; position: relative; max-width: 400px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666;"></i>
                            <input type="text" id="searchAluno" onkeyup="filtrarAlunos()" placeholder="Buscar por nome..." class="admin-input" style="padding-left: 40px;">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table" id="tabelaAlunos">
                            <thead>
                                <tr>
                                    <th class="th-admin-table">USUÁRIO</th>
                                    <th class="th-admin-table">CONTATO</th>
                                    <th class="th-admin-table">NÍVEL</th>
                                    <th class="th-admin-table" id="th-acao">AÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                            if ($total_alunos > 0) {
                                foreach ($alunos as $a) {
                                    $foto = !empty($a['foto']) ? $a['foto'] : 'assets/img/user-default.png';
                                    
                                    // --- LÓGICA DE BADGES ATUALIZADA ---
                                    if ($a['tipo_conta'] === 'admin') {
                                        $nivelTag = '<span class="status-badge" style="background:rgba(255,66,66,0.2); color:#ff4242; border:1px solid #ff4242;">ADMIN</span>';
                                    } elseif ($a['tipo_conta'] === 'personal' || $a['tipo_conta'] === 'coach') {
                                        $nivelTag = '<span class="status-badge" style="background:rgba(218,165,32,0.2); color:var(--gold); border:1px solid var(--gold);">COACH</span>';
                                    } else {
                                        $nivelTag = '<span class="status-badge" style="background:rgba(0,255,0,0.1); color:#00ff00; border:1px solid #00ff00;">ALUNO</span>';
                                    }
                                    
                                    $zap_clean = preg_replace('/[^0-9]/', '', $a['telefone']);
                                    $link_zap = "https://wa.me/55".$zap_clean;

                                    $dados_json = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');

                                    echo '
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <img src="'.$foto.'" class="table-avatar" alt="Foto">
                                                <div>
                                                    <span style="display:block; font-weight:bold; color:#fff;">'.$a['nome'].'</span>
                                                    <span style="font-size:0.8rem; color:#666;">'.$a['email'].'</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <span style="color:#ccc; font-size:0.9rem;">'.$a['telefone'].'</span>
                                                <a href="'.$link_zap.'" target="_blank" class="btn-action-icon btn-confirm" title="WhatsApp">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <td>'.$nivelTag.'</td>
                                        <td style="text-align: right;">
                                            <button class="btn-gold" style="padding: 8px 20px; font-size: 0.75rem;" onclick=\'abrirPainelAluno('.$dados_json.')\'>
                                                <i class="fa-solid fa-gear"></i> GERENCIAR
                                            </button>
                                        </td>
                                    </tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" style="text-align:center; padding:30px;">Nenhum usuário encontrado.</td></tr>';
                            }

        echo '              </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div id="modalGerenciarAluno" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="max-width: 500px;">
                    <button class="modal-close" onclick="fecharPainelAluno()">&times;</button>
                    
                    <div style="text-align:center; margin-bottom:30px;">
                        <img id="hub-foto" src="" style="width:100px; height:100px; border-radius:50%; border:3px solid var(--gold); object-fit:cover; margin-bottom:10px;">
                        <h3 id="hub-nome" style="color:#fff; margin:0; font-family:Orbitron;">Nome do Aluno</h3>
                        <span id="hub-email" style="color:#888; font-size:0.9rem;">email@email.com</span>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        
                        <div class="menu-card" onclick="hubAcao(\'historico\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;">
                            <i class="fa-solid fa-dumbbell" style="font-size:1.5rem; color:var(--gold); margin-bottom:5px;"></i>
                            <span style="display:block; font-size:0.8rem; color:#ccc;">Histórico de Treinos</span>
                        </div>

                        <div class="menu-card" onclick="hubAcao(\'avaliacao_lista\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;">
                            <i class="fa-solid fa-ruler-combined" style="font-size:1.5rem; color:#00e676; margin-bottom:5px;"></i>
                            <span style="display:block; font-size:0.8rem; color:#ccc;">Avaliações Físicas</span>
                        </div>

                        <div class="menu-card" onclick="hubAcao(\'dieta_editor\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;">
                            <i class="fa-solid fa-utensils" style="font-size:1.5rem; color:#ff4242; margin-bottom:5px;"></i>
                            <span style="display:block; font-size:0.8rem; color:#ccc;">Plano Alimentar</span>
                        </div>

                        <div class="menu-card" onclick="hubAcao(\'editar\')" style="background:#1f1f1f; padding:15px; border-radius:10px; text-align:center; cursor:pointer; border:1px solid #333;">
                            <i class="fa-solid fa-user-pen" style="font-size:1.5rem; color:#fff; margin-bottom:5px;"></i>
                            <span style="display:block; font-size:0.8rem; color:#ccc;">Editar Dados</span>
                        </div>

                    </div>

                    <div style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;">
                        <button onclick="hubAcao(\'excluir\')" style="width:100%; background:rgba(255,66,66,0.1); color:#ff4242; border:1px solid #ff4242; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">
                            <i class="fa-solid fa-trash"></i> EXCLUIR USUÁRIO
                        </button>
                    </div>
                </div>
            </div>

            <div id="modalEditarAluno" class="modal-overlay" style="display:none;">
                <div class="modal-content">
                    <button class="modal-close" onclick="closeEditModal()">&times;</button>
                    <h3 class="section-title" style="color: var(--gold); margin-bottom: 20px; text-align: center;">
                        <i class="fa-solid fa-user-pen"></i> Editar Dados
                    </h3>
                    
                    <form action="actions/admin_aluno.php" method="POST">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Nome Completo</label>
                                <input type="text" name="nome" id="edit_nome" class="admin-input" required>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="color:var(--gold); font-size: 0.8rem; font-weight:bold;">Tipo de Usuário (Permissão)</label>
                            <select name="tipo_conta" id="edit_tipo_conta" class="admin-input" style="border-color:var(--gold);">
                                <option value="aluno">Aluno (Padrão)</option>
                                <option value="personal">Coach / Personal</option>
                                <option value="admin">Administrador (Acesso Total)</option>
                            </select>
                        </div>

                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Email</label>
                                <input type="email" name="email" id="edit_email" class="admin-input" required>
                            </div>
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Telefone</label>
                                <input type="text" name="telefone" id="edit_telefone" class="admin-input">
                            </div>
                        </div>

                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Vencimento do Plano</label>
                                <input type="date" name="data_expiracao" id="edit_expiracao" class="admin-input">
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="color:#ff4242; font-size: 0.8rem;">Redefinir Senha</label>
                            <input type="text" name="nova_senha" class="admin-input" placeholder="Deixe vazio para manter">
                        </div>

                        <button type="submit" class="btn-gold" style="width: 100%; padding: 15px;">SALVAR ALTERAÇÕES</button>
                    </form>
                </div>
            </div>
        ';
        break;

    // --- NOVA TELA: LISTA DE AVALIAÇÕES DO ALUNO (Visão Admin) ---
    case 'aluno_avaliacoes':
        require_once '../config/db_connect.php';
        $aluno_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$aluno_id) { echo "ID inválido"; break; }

        // Dados do Aluno para o Cabeçalho
        $stmt_u = $pdo->prepare("SELECT nome, foto FROM usuarios WHERE id = ?");
        $stmt_u->execute([$aluno_id]);
        $aluno = $stmt_u->fetch(PDO::FETCH_ASSOC);

        // Lista de Avaliações
        $sql = "SELECT * FROM avaliacoes WHERE aluno_id = ? ORDER BY data_avaliacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aluno_id]);
        $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Helpers de Medidas (Cópia do Usuário)
        $renderMeasure = function($label, $val) {
            if(!$val) return '';
            return '<div class="m-box"><span>'.$label.'</span><strong>'.$val.'</strong></div>';
        };
        $renderMeasureDouble = function($label, $val1, $val2) {
            if(!$val1 && !$val2) return '';
            return '<div class="m-box-double"><span>'.$label.'</span><div class="vals"><strong>'.($val1?:'-').'</strong><small>/</small><strong>'.($val2?:'-').'</strong></div></div>';
        };

        echo '<section id="aluno-avaliacoes">
                
                <header class="dash-header">
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <button onclick="carregarConteudo(\'alunos\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></button>
                        <img src="'.($aluno['foto'] ?: 'assets/img/user-default.png').'" style="width:40px; height:40px; border-radius:50%; border:1px solid var(--gold);">
                        <div>
                            <h1 style="font-size:1.5rem; margin:0;">AVALIAÇÕES</h1>
                            <span style="color:#888; font-size:0.8rem;">Atleta: '.$aluno['nome'].'</span>
                        </div>
                    </div>
                </header>

                <div class="glass-card">
                    <div class="section-header-row" style="margin-bottom:20px;">
                        <button class="btn-gold" style="background:transparent; border:1px solid var(--gold); color:var(--gold);" onclick="carregarConteudo(\'aluno_progresso&id='.$aluno_id.'\')">
                            <i class="fa-solid fa-chart-line"></i> PROGRESSO
                        </button>
                        
                        <button class="btn-gold" onclick="abrirModalAvaliacao('.$aluno_id.')">
                            <i class="fa-solid fa-plus"></i> NOVA AVALIAÇÃO
                        </button>
                    </div>';

        if (empty($avaliacoes)) {
            echo '<p style="text-align:center; color:#666; padding:40px;">Nenhuma avaliação registrada.</p>';
        } else {
            echo '<div class="eval-timeline-wrapper">';
            foreach ($avaliacoes as $av) {
                // Arquivos
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
                                    '.($av['percentual_gordura'] ? '<span class="bf-tag">BF '.($av['percentual_gordura']*1).'%</span>' : '').'
                                </div>
                                <span class="info-sub">'.count($arquivos).' mídias anexadas</span>
                            </div>
                            <div class="accordion-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>

                        <div class="accordion-body" style="display: none;">
                            <div class="body-padding">
                                <div class="stats-tiles">
                                    <div class="tile"><small>IMC</small><strong>'.($av['imc'] ?: '-').'</strong></div>
                                    <div class="tile"><small>M. MAGRA</small><strong>'.($av['massa_magra_kg'] ? $av['massa_magra_kg'].'kg' : '-').'</strong></div>
                                    <div class="tile"><small>M. GORDA</small><strong>'.($av['massa_gorda_kg'] ? $av['massa_gorda_kg'].'kg' : '-').'</strong></div>
                                </div>';
                                
                                if (!empty($arquivos)) {
                                    echo '<div class="gallery-strip"><div class="strip-scroll">';
                                    foreach ($arquivos as $arq) {
                                        if ($arq['tipo'] == 'foto') echo '<div class="strip-item"><img src="assets/uploads/'.$arq['caminho_ou_url'].'" onclick="window.open(this.src)"></div>';
                                        else echo '<a href="'.$arq['caminho_ou_url'].'" target="_blank" class="strip-item video-item"><i class="fa-solid fa-play"></i></a>';
                                    }
                                    echo '</div></div>';
                                }

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
                                            <span class="mg-label">MEMBROS</span>
                                            <div class="mg-grid-wide">
                                                '.$renderMeasureDouble('Braço (Rel)', $av['braco_dir_relaxado'], $av['braco_esq_relaxado']).'
                                                '.$renderMeasureDouble('Braço (Con)', $av['braco_dir_contraido'], $av['braco_esq_contraido']).'
                                                '.$renderMeasureDouble('Coxa', $av['coxa_dir'], $av['coxa_esq']).'
                                                '.$renderMeasureDouble('Panturrilha', $av['panturrilha_dir'], $av['panturrilha_esq']).'
                                            </div>
                                        </div>
                                      </div>';
                                
                                if($av['observacoes']) echo '<div class="obs-box"><i class="fa-solid fa-quote-left"></i> '.$av['observacoes'].'</div>';

                                // Botão de Excluir (Admin tem poder)
                                echo '<div class="card-footer-actions" style="margin-top:30px; text-align:center; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;">
                                        <a href="actions/avaliacao_delete.php?id='.$av['id'].'" class="btn-danger-outline" onclick="return confirm(\'Apagar avaliação permanentemente?\');">
                                            <i class="fa-solid fa-trash-can"></i> Excluir Avaliação
                                        </a>
                                      </div>';

                echo '      </div>
                        </div>
                      </div>';
            }
            echo '</div>';
        }
        echo '</div></section>';
        break;

    // --- NOVA TELA: PROGRESSO DO ALUNO (GRÁFICOS) ---
    case 'aluno_progresso':
        require_once '../config/db_connect.php';
        $aluno_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // Busca Nome
        $stmt_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmt_u->execute([$aluno_id]);
        $nome_aluno = $stmt_u->fetchColumn();

        // 1. DADOS CRONOLÓGICOS (Chart)
        $sql = "SELECT * FROM avaliacoes WHERE aluno_id = ? ORDER BY data_avaliacao ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aluno_id]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $json_data = ['labels' => [], 'peso' => [], 'bf' => [], 'magra' => []];
        foreach ($historico as $h) {
            if ($h['peso_kg'] > 0) {
                $json_data['labels'][] = date('d/m/y', strtotime($h['data_avaliacao']));
                $json_data['peso'][] = (float)$h['peso_kg'];
                $json_data['bf'][] = (float)$h['percentual_gordura'];
                $json_data['magra'][] = (float)$h['massa_magra_kg'];
            }
        }
        $chart_config = htmlspecialchars(json_encode($json_data), ENT_QUOTES, 'UTF-8');
        
        // 2. DADOS REVERSOS (Tabela)
        $historico_reverso = array_reverse($historico);
        
        $renderVal = function($historico_reverso, $idx, $key, $inverse = false) {
            $val = $historico_reverso[$idx][$key] ?? null;
            if (!$val) return '-';
            $prev = $historico_reverso[$idx + 1][$key] ?? null;
            $html = '<strong>'.$val.'</strong>';
            if ($prev) {
                $diff = $val - $prev;
                if ($diff != 0) {
                    $isGood = $inverse ? ($diff < 0) : ($diff > 0);
                    $color = $isGood ? '#00e676' : '#ff1744';
                    $html .= ' <small style="color:'.$color.'; font-size:0.7em;">'.($diff>0?'+':'').number_format($diff, 1).'</small>';
                }
            }
            return $html;
        };

        echo '<section id="aluno-progresso" class="fade-in">
                <input type="hidden" id="chart-master-data" value="'.$chart_config.'">
                
                <header class="dash-header">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <button onclick="carregarConteudo(\'aluno_avaliacoes&id='.$aluno_id.'\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></button>
                        <div>
                            <h1 style="font-size:1.5rem; margin:0;">ANÁLISE</h1>
                            <span style="color:#888; font-size:0.8rem;">Evolução de '.$nome_aluno.'</span>
                        </div>
                    </div>
                </header>';

        if (count($historico) < 2) {
            echo '<div class="glass-card" style="text-align:center; padding:40px; color:#666;">
                    <i class="fa-solid fa-chart-line" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p>Dados insuficientes para gerar gráficos. Cadastre pelo menos 2 avaliações.</p>
                  </div>';
        } else {
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
                  </div>

                  <div class="comparison-section">
                    <div class="comp-tabs">
                        <button class="tab-pill active" onclick="switchTable(\'tronco\', this)">Tronco</button>
                        <button class="tab-pill" onclick="switchTable(\'bracos\', this)">Braços</button>
                        <button class="tab-pill" onclick="switchTable(\'pernas\', this)">Pernas</button>
                    </div>

                    <div id="tab-tronco" class="table-container active">
                        <table class="comp-table">
                            <thead><tr><th>DATA</th><th>Ombro</th><th>Tórax</th><th>Cintura</th><th>Abdômen</th></tr></thead>
                            <tbody>';
                            foreach($historico_reverso as $i => $h) {
                                echo '<tr>
                                        <td class="fixed-col">'.date('d/m/y', strtotime($h['data_avaliacao'])).'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'ombro').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'torax_relaxado').'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'cintura', true).'</td>
                                        <td>'.$renderVal($historico_reverso, $i, 'abdomen', true).'</td>
                                      </tr>';
                            }
            echo '          </tbody></table>
                    </div>

                    <div id="tab-bracos" class="table-container" style="display:none;">
                        <table class="comp-table">
                            <thead><tr><th>DATA</th><th>B. Dir (R)</th><th>B. Esq (R)</th><th>B. Dir (C)</th><th>B. Esq (C)</th></tr></thead>
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
            echo '          </tbody></table>
                    </div>

                    <div id="tab-pernas" class="table-container" style="display:none;">
                        <table class="comp-table">
                            <thead><tr><th>DATA</th><th>Coxa Dir</th><th>Coxa Esq</th><th>Pant. Dir</th><th>Pant. Esq</th></tr></thead>
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
            echo '          </tbody></table>
                    </div>
                  </div>';
        }
        echo '</section>';
        break;

    case 'aluno_historico':
        require_once '../config/db_connect.php';
        
        // Pega o ID do aluno via URL (admin navegando)
        $aluno_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $data_ref = $_GET['data_ref'] ?? null;

        if (!$aluno_id) { echo "Aluno não identificado."; break; }

        // Busca nome/foto do aluno para mostrar no topo (Contexto do Admin)
        $stmt_aluno = $pdo->prepare("SELECT nome, foto FROM usuarios WHERE id = ?");
        $stmt_aluno->execute([$aluno_id]);
        $dados_aluno = $stmt_aluno->fetch(PDO::FETCH_ASSOC);
        $foto_aluno = $dados_aluno['foto'] ?? 'assets/img/user-default.png';

        // --- MODO 1: DETALHES DO TREINO (DATA ESPECÍFICA) ---
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

            // 2. Busca Detalhes
            $sql_detalhes = "SELECT th.*, e.nome_exercicio, s.categoria 
                             FROM treino_historico th
                             JOIN exercicios e ON th.exercicio_id = e.id
                             LEFT JOIN series s ON th.serie_numero = s.id 
                             WHERE th.aluno_id = :uid AND th.data_treino = :dt
                             ORDER BY e.ordem ASC, th.id ASC";
            $stmt = $pdo->prepare($sql_detalhes);
            $stmt->execute(['uid' => $aluno_id, 'dt' => $data_ref]);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Agrupamento
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

            // RENDERIZAÇÃO (Visual Idêntico ao do Usuário)
            echo '<section id="admin-historico-detalhe" class="fade-in">
                    
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                        <button onclick="carregarConteudo(\'aluno_historico&id='.$aluno_id.'\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <div>
                            <span style="color:#888; font-size:0.8rem; text-transform:uppercase;">Visualizando</span>
                            <h2 style="margin:0; color:#fff; font-size:1.2rem;">TREINO '.$info['letra'].'</h2>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; padding:15px; background:rgba(255,186,66,0.1); border-radius:8px; border:1px solid var(--gold); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:var(--gold); display:block;">'.$info['nome_treino'].'</strong>
                            <span style="color:#ccc; font-size:0.8rem;">'.date('d/m/Y \à\s H:i', strtotime($data_ref)).'</span>
                            <span style="color:#fff; font-size:0.8rem;E display:block;">Aluno: '.$dados_aluno['nome'].'</span>
                        </div>
                        <i class="fa-solid fa-calendar-check" style="color:var(--gold); font-size:1.5rem;"></i>
                    </div>

                    <div class="history-details-list">';
                    
                    if (empty($treino_agrupado)) {
                        echo '<p style="text-align:center; color:#666;">Nenhum dado encontrado.</p>';
                    }

                    foreach ($treino_agrupado as $ex_id => $dados) {
                        echo '<div class="hist-exercise-group" style="background:#141414; border:1px solid #252525; border-radius:12px; margin-bottom:12px; overflow:hidden;">
                                <div class="hist-ex-header" style="background:rgba(255,255,255,0.03); padding:12px 15px; border-bottom:1px solid #2a2a2a; display:flex; align-items:center; gap:10px;">
                                    <i class="fa-solid fa-dumbbell" style="color:var(--gold);"></i>
                                    <span style="color:#fff; font-weight:700;">'.$dados['nome'].'</span>
                                </div>
                                
                                <table class="hist-sets-table" style="width:100%; border-collapse:collapse; text-align:center;">
                                    <thead>
                                        <tr style="background:rgba(0,0,0,0.2); color:#555; font-size:0.65rem; text-transform:uppercase;">
                                            <th style="padding:10px;">#</th>
                                            <th style="padding:10px;">TIPO</th>
                                            <th style="padding:10px;">KG</th>
                                            <th style="padding:10px;">REPS</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                                    
                                    $contador_serie = 1;
                                    foreach ($dados['series'] as $serie) {
                                        $cat = $serie['categoria'] ? $serie['categoria'] : 'work';
                                        
                                        // Badge Colors (Inline para garantir visual igual)
                                        $bgBadge = 'rgba(255,255,255,0.1)';
                                        $colorBadge = '#ccc';
                                        if($cat=='warmup') { $bgBadge='rgba(255,215,0,0.1)'; $colorBadge='#FFD700'; }
                                        if($cat=='work')   { $bgBadge='rgba(255,66,66,0.1)'; $colorBadge='#ff5e5e'; }
                                        if($cat=='feeder') { $bgBadge='rgba(135,206,235,0.1)'; $colorBadge='#87CEEB'; }

                                        echo '<tr style="border-bottom:1px solid #1f1f1f;">
                                                <td style="padding:10px; color:#666; font-weight:bold;">'.$contador_serie.'</td>
                                                <td style="padding:10px;">
                                                    <span style="font-size:0.6rem; padding:3px 8px; border-radius:12px; font-weight:800; background:'.$bgBadge.'; color:'.$colorBadge.'; border:1px solid '.$colorBadge.'">'.strtoupper($cat).'</span>
                                                </td>
                                                <td style="padding:10px; color:#fff; font-weight:bold;">'.($serie['carga_kg']*1).'</td>
                                                <td style="padding:10px; color:#fff;">'.$serie['reps_realizadas'].'</td>
                                              </tr>';
                                        $contador_serie++;
                                    }

                        echo '      </tbody>
                                </table>
                              </div>';
                    }

            echo '  </div>
                  </section>';
            break;
        }

        // --- MODO 2: LISTA DE DATAS (TIMELINE) ---
        $sql_lista = "SELECT th.data_treino, t.nome as nome_treino, td.letra
                      FROM treino_historico th
                      JOIN treinos t ON th.treino_id = t.id
                      JOIN treino_divisoes td ON th.divisao_id = td.id
                      WHERE th.aluno_id = :uid
                      GROUP BY th.data_treino
                      ORDER BY th.data_treino DESC";
        $stmt = $pdo->prepare($sql_lista);
        $stmt->execute(['uid' => $aluno_id]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<section id="admin-historico-lista" class="fade-in">
                <header class="dash-header">
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <img src="'.$foto_aluno.'" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:2px solid var(--gold);">
                        <div>
                            <h1 style="font-size:1.5rem; margin:0;">HISTÓRICO <span class="highlight-text">DO ALUNO</span></h1>
                            <p class="text-desc" style="margin:0;">'.$dados_aluno['nome'].'</p>
                        </div>
                    </div>
                </header>';

        if (empty($historico)) {
            echo '<div class="empty-state" style="text-align:center; padding:50px 20px; color:#666;">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
                    <h2>Nenhum treino registrado</h2>
                    <p>O aluno ainda não registrou atividades.</p>
                  </div>';
        } else {
            echo '<div class="history-list" style="display:flex; flex-direction:column; gap:12px; padding-bottom:90px;">';
            
            foreach ($historico as $h) {
                $data_obj = new DateTime($h['data_treino']);
                $dia = $data_obj->format('d');
                $mes = strftime('%b', $data_obj->getTimestamp());
                
                $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                $mes_txt = $meses[(int)$data_obj->format('m') - 1];
                $hora = $data_obj->format('H:i');

                // Aqui usamos o ID do aluno na navegação
                echo '<div class="history-card" onclick="carregarConteudo(\'aluno_historico&id='.$aluno_id.'&data_ref='.$h['data_treino'].'\')" 
                           style="background:linear-gradient(145deg, #1a1a1a, #111); border:1px solid rgba(255,255,255,0.05); border-radius:16px; padding:18px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; position:relative; overflow:hidden;">
                        
                        <div style="position:absolute; left:0; top:0; bottom:0; width:5px; background:linear-gradient(to bottom, var(--gold), #b8860b);"></div>
                        
                        <div class="hist-date-box" style="padding-right:15px; margin-right:15px; border-right:1px solid rgba(255,255,255,0.1); text-align:center; min-width:65px; margin-left:10px;">
                            <span class="hist-day" style="display:block; font-size:1.5rem; font-weight:800; color:#fff; line-height:1;">'.$dia.'</span>
                            <span class="hist-month" style="display:block; font-size:0.7rem; text-transform:uppercase; color:var(--gold); font-weight:bold; margin-top:2px;">'.$mes_txt.'</span>
                        </div>
                        
                        <div class="hist-info" style="flex:1;">
                            <span class="hist-title" style="display:block; font-size:1.05rem; font-weight:700; color:#fff; margin-bottom:2px;">Treino '.$h['letra'].'</span>
                            <span class="hist-sub" style="font-size:0.8rem; color:#777;">'.$h['nome_treino'].' • '.$hora.'</span>
                        </div>
                        
                        <i class="fa-solid fa-chevron-right hist-arrow" style="color:#444;"></i>
                      </div>';
            }
            
            echo '</div>';
        }
        
        echo '</section>';
        break;

    case 'treinos_editor':
        require_once '../config/db_connect.php';
        
        // 1. LISTAR TREINOS EXISTENTES
        $sql_list = "SELECT t.*, u.nome as nome_aluno, u.foto as foto_aluno 
                     FROM treinos t 
                     JOIN usuarios u ON t.aluno_id = u.id 
                     ORDER BY t.criado_em DESC";
        $treinos = $pdo->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);

        // 2. LISTA DE ALUNOS (Para o Dropdown)
        $alunos = $pdo->query("SELECT id, nome, foto FROM usuarios WHERE tipo_conta = 'atleta' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        echo '
            <section id="editor-treinos">
                <header class="dash-header">
                    <h1>EDITOR DE <span class="highlight-text">TREINOS</span></h1>
                    <p class="text-desc">Gerencie as periodizações e fichas dos alunos.</p>
                </header>

                <div class="glass-card">
                    <div class="section-header-row">
                        <h3 class="section-title" style="margin:0"><i class="fa-solid fa-list"></i> PLANEJAMENTOS</h3>
                        <button class="btn-gold" onclick="toggleNovoTreino()">
                            <i class="fa-solid fa-plus"></i> NOVO TREINO
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table responsive-table">
                            <thead>
                                <tr>
                                    <th class="th-admin-table">ALUNO</th>
                                    <th class="th-admin-table">NOME DO TREINO</th>
                                    <th class="th-admin-table">TIPO</th>
                                    <th class="th-admin-table">VIGÊNCIA</th>
                                    <th class="th-admin-table" id="th-acao">AÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                            if (count($treinos) > 0) {
                                foreach ($treinos as $t) {
                                    $foto = !empty($t['foto_aluno']) ? $t['foto_aluno'] : 'assets/img/icones/user-default.png';
                                    $inicio = date('d/m', strtotime($t['data_inicio']));
                                    $fim = date('d/m', strtotime($t['data_fim']));
                                    
                                    $corBadge = ($t['nivel_plano'] == 'basico') ? '#ccc' : (($t['nivel_plano'] == 'avancado') ? '#FFBA42' : '#ff4242');
                                    
                                    echo '
                                    <tr>
                                        <td data-label="Aluno">
                                            <div class="user-cell">
                                                <img src="'.$foto.'" class="table-avatar" alt="Foto">
                                                <span>'.$t['nome_aluno'].'</span>
                                            </div>
                                        </td>
                                        
                                        <td data-label="Treino">
                                            <strong style="color:#fff;">'.$t['nome'].'</strong><br>
                                            <span style="font-size:0.8rem; color:#666;">Divisão '.$t['divisao_nome'].'</span>
                                        </td>
                                        
                                        <td data-label="Tipo">
                                            <span class="status-badge" style="color:'.$corBadge.'; border-color:'.$corBadge.'; background:transparent;">'.strtoupper($t['nivel_plano']).'</span>
                                        </td>
                                        
                                        <td data-label="Vigência" style="color:#888;">'.$inicio.' a '.$fim.'</td>
                                        
                                        <td data-label="Ação" class="actions" style="text-align:right;">
                                            <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center;">
                                                <button class="btn-action-icon btn-delete" 
                                                        onclick="deletarTreino('.$t['id'].')" 
                                                        title="Excluir Treino">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>

                                                <button class="btn-gold" style="padding: 5px 15px; font-size: 0.8rem;" onclick="carregarConteudo(\'treino_painel&id='.$t['id'].'\')">
                                                    GERENCIAR <i class="fa-solid fa-arrow-right"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" style="text-align:center; padding:30px; color:#666;">Nenhum treino criado ainda.</td></tr>';
                            }

        echo '              </tbody>
                        </table>
                    </div>
                </div>

                <div id="box-novo-treino" class="modal-overlay" style="display:none;">
                    <div class="modal-content selection-modal" style="max-width: 650px; text-align: left; position: relative;">
                        
                        <button class="modal-close" onclick="toggleNovoTreino()">&times;</button>

                        <h3 class="section-title" style="color: var(--gold); margin-bottom: 25px; text-align: center;">
                            <i class="fa-solid fa-dumbbell"></i> Criar Nova Estrutura
                        </h3>
                        
                        <form action="actions/treino_create.php" method="POST">
                            
                            <div class="form-row">
                                <div class="form-col" style="position: relative;">
                                    <label class="input-label">Selecione o Atleta</label>
                                    
                                    <input type="text" id="busca-aluno-treino" class="admin-input" placeholder="Digite para buscar..." autocomplete="off" onkeyup="filtrarAlunosTreino()">
                                    
                                    <input type="hidden" name="aluno_id" id="id-aluno-treino-selecionado" required>
                                    
                                    <div id="dropdown-alunos-treino" class="custom-dropdown-list" style="display:none;">';
                                    
                                    // INÍCIO DO LOOP PHP
                                    if (count($alunos) > 0) {
                                        foreach($alunos as $al) {
                                            // Verifica se tem foto, senão usa padrão
                                            $ft = !empty($al['foto']) ? $al['foto'] : 'assets/img/user-default.png';
                                            
                                            // Protege nomes com aspas simples (ex: D'Avila) para não quebrar o JS
                                            $nome_seguro = addslashes($al['nome']); 
                                            
                                            echo '<div class="dropdown-item" onclick="selecionarAlunoTreino('.$al['id'].', \''.$nome_seguro.'\')">
                                                    <img src="'.$ft.'">
                                                    <span>'.$al['nome'].'</span>
                                                </div>';
                                        }
                                    } else {
                                        echo '<div class="dropdown-item" style="cursor: default; color: #888;">Nenhum aluno encontrado</div>';
                                    }
                                    // FIM DO LOOP PHP

                            echo '  </div>
                                </div>;

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

                            <div id="aviso-periodizacao" class="alert-box">
                                <span class="alert-title">Modo Periodização Ativo</span>
                                <p class="alert-text">Serão gerados 12 Microciclos automaticamente.</p>
                            </div>

                            <button type="submit" class="btn-gold" style="width:100%; margin-top: 15px; padding: 15px;">CRIAR ESTRUTURA</button>
                        </form>
                    </div>
                </div>
            </section>
        ';
        break;

    case 'financeiro':
        require_once '../config/db_connect.php';
        
        // 1. CÁLCULOS (Corrigido para tabela 'pagamentos')
        $sql_fat = "SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())";
        $stmt_fat = $pdo->query($sql_fat);
        $faturamento = $stmt_fat->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $sql_pend = "SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pendente'";
        $stmt_pend = $pdo->query($sql_pend);
        $pendente = $stmt_pend->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. LISTA DE ALUNOS (Para o Dropdown com Pesquisa)
        $alunos = $pdo->query("SELECT id, nome, foto FROM usuarios WHERE tipo_conta = 'atleta' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 3. HISTÓRICO (Corrigido para tabela 'pagamentos' e campo 'usuario_id')
        $sql_hist = "SELECT p.*, u.nome as nome_aluno, u.foto as foto_aluno 
                     FROM pagamentos p 
                     LEFT JOIN usuarios u ON p.usuario_id = u.id 
                     ORDER BY p.id DESC LIMIT 20";
        $transacoes = $pdo->query($sql_hist)->fetchAll(PDO::FETCH_ASSOC);

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
                        <button class="btn-gold" onclick="openModal()">
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
                                    
                                    // Se for pendente usa vencimento, se for pago usa data_pagamento se existir
                                    $dataShow = $t['data_vencimento'];
                                    if($t['status'] == 'pago' && !empty($t['data_pagamento'])) {
                                        $dataShow = $t['data_pagamento'];
                                    }
                                    $dataExibicao = date('d/m/Y', strtotime($dataShow));
                                    
                                    $fotoUser = !empty($t['foto_aluno']) ? $t['foto_aluno'] : 'assets/img/user-default.png';
                                    
                                    // BOTÕES DE AÇÃO (Restaurados)
                                    $btns = '<div style="display:flex; gap:10px; justify-content:flex-end;">';
                                    
                                    // 1. Pagar/Estornar
                                    if ($t['status'] == 'pendente') {
                                        $btns .= '<a href="actions/financeiro_status.php?id='.$t['id'].'&acao=pagar" class="btn-action-icon btn-confirm" title="Confirmar Pagamento"><i class="fa-solid fa-check"></i></a>';
                                    } else {
                                        $btns .= '<a href="actions/financeiro_status.php?id='.$t['id'].'&acao=estornar" class="btn-action-icon btn-undo" title="Desfazer/Estornar"><i class="fa-solid fa-rotate-left"></i></a>';
                                    }

                                    // 2. Excluir (RESTAURADO)
                                    $btns .= '<a href="actions/financeiro_status.php?id='.$t['id'].'&acao=excluir" class="btn-action-icon btn-delete" title="Excluir Registro" onclick="return confirm(\'Tem certeza que deseja excluir este lançamento permanentemente?\')"><i class="fa-solid fa-trash"></i></a>';
                                    
                                    $btns .= '</div>';

                                    echo '
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <img src="'.$fotoUser.'" class="table-avatar" alt="Foto">
                                                <span>'.($t['nome_aluno'] ?: 'Avulso').'</span>
                                            </div>
                                        </td>
                                        <td>'.$t['descricao'].'</td>
                                        <td>'.$dataExibicao.'</td>
                                        <td><strong>R$ '.number_format($t['valor'], 2, ',', '.').'</strong></td>
                                        <td><span class="status-badge '.$statusClass.'">'.strtoupper($t['status']).'</span></td>
                                        <td style="text-align: right;">'.$btns.'</td>
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
                <div class="modal-content">
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                    
                    <h3 class="section-title" style="color: var(--gold); margin-bottom: 20px; text-align: center;">
                        <i class="fa-solid fa-money-bill-wave"></i> Novo Lançamento
                    </h3>
                    
                    <form action="actions/financeiro_add.php" method="POST">
                        
                        <div style="margin-bottom: 15px; position: relative;">
                            <label style="color:#ccc; font-size: 0.8rem;">Aluno</label>
                            
                            <input type="text" id="busca-aluno-input" class="admin-input" placeholder="Digite o nome para buscar..." autocomplete="off" onkeyup="filtrarAlunosFinanceiro()">
                            
                            <input type="hidden" name="usuario_id" id="id-aluno-selecionado" required>

                            <div id="dropdown-alunos" class="custom-dropdown-list" style="display:none;">';
                                foreach($alunos as $al) {
                                    $ft = !empty($al['foto']) ? $al['foto'] : 'assets/img/user-default.png';
                                    // O JS vai ler o nome dentro do span
                                    echo '<div class="dropdown-item" onclick="selecionarAlunoFinanceiro('.$al['id'].', \''.$al['nome'].'\')">
                                            <img src="'.$ft.'">
                                            <span>'.$al['nome'].'</span>
                                          </div>';
                                }
        echo '              </div>
                        </div>

                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 2;">
                                <label style="color:#ccc; font-size: 0.8rem;">Descrição</label>
                                <input type="text" name="descricao" class="admin-input" placeholder="Ex: Plano Trimestral" required>
                            </div>
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Valor (R$)</label>
                                <input type="number" name="valor" step="0.01" class="admin-input" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Vencimento</label>
                                <input type="date" name="data_vencimento" class="admin-input" required value="'.date('Y-m-d').'">
                            </div>
                            <div style="flex: 1;">
                                <label style="color:#ccc; font-size: 0.8rem;">Status</label>
                                <select name="status" class="admin-input">
                                    <option value="pago">Pago (Recebido)</option>
                                    <option value="pendente">Pendente (A receber)</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-gold" style="width: 100%; padding: 15px; font-size: 1rem;">REGISTRAR VENDA</button>
                    </form>
                </div>
            </div>
        ';
        break;
    
    case 'treino_painel':
        require_once '../config/db_connect.php';
        $treino_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        if (!$treino_id) { echo "ID Inválido"; break; }

        // 1. BUSCAR DADOS GERAIS
        $sql = "SELECT t.*, u.nome as nome_aluno FROM treinos t JOIN usuarios u ON t.aluno_id = u.id WHERE t.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $treino_id]);
        $treino = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. BUSCAR DIVISÕES
        $sql_div = "SELECT * FROM treino_divisoes WHERE treino_id = :id ORDER BY letra ASC";
        $stmt_div = $pdo->prepare($sql_div);
        $stmt_div->execute(['id' => $treino_id]);
        $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

        // 3. BUSCAR PERIODIZAÇÃO E MICROCICLOS
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
        
        // --- INICIO HTML ---
        echo '
            <section id="painel-treino">
                <div style="display:flex; align-items:center; gap:20px; margin-bottom:30px;">
                    <button class="btn-action-icon" onclick="carregarConteudo(\'treinos_editor\')"><i class="fa-solid fa-arrow-left"></i></button>
                    <div>
                        <h2 style="color:#fff; font-family:Orbitron; margin:0;">'.$treino['nome'].'</h2>
                        <p style="color:#888; font-size:0.9rem;">Aluno: <strong style="color:var(--gold);">'.$treino['nome_aluno'].'</strong> • '.strtoupper($treino['nivel_plano']).'</p>
                    </div>
                </div>

                ';
                if (!empty($microciclos)) {
                    echo '<h3 class="section-title" style="font-size:1rem; margin-bottom:10px;">PERIODIZAÇÃO (12 SEMANAS)</h3>
                          <div class="timeline-wrapper">';
                    
                    foreach ($microciclos as $m) {
                        $inicio = date('d/m', strtotime($m['data_inicio_semana']));
                        $fim = date('d/m', strtotime($m['data_fim_semana']));
                        
                        $hoje = date('Y-m-d');
                        $activeClass = ($hoje >= $m['data_inicio_semana'] && $hoje <= $m['data_fim_semana']) ? 'active' : '';
                        
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
                            echo '<button class="div-tab-btn '.$active.'" onclick="openTab(event, \'div_'.$div['letra'].'\')">TREINO '.$div['letra'].'</button>';
                            $first = false;
                        }
        echo '      </div>';

                    // CONTEÚDO DAS ABAS (Lista de Exercícios)
                    $firstContent = true;
                    foreach ($divisoes as $div) {
                        $display = $firstContent ? 'active' : '';
                        
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
                                <input type="text" name="nome_exercicio" class="admin-input" placeholder="Ex: Supino Reto" required>
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Mecânica</label>
                                <select name="tipo_mecanica" class="admin-input">
                                    <option value="livre">Livre / Máquina</option>
                                    <option value="composto">Composto (Periodizado)</option>
                                    <option value="isolador">Isolador (Periodizado)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:1;">
                                <label class="input-label">Link Vídeo (Youtube/Drive)</label>
                                <input type="text" name="video_url" class="admin-input" placeholder="https://...">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Observação</label>
                                <input type="text" name="observacao" class="admin-input" placeholder="Ex: Segurar 2s na descida">
                            </div>
                        </div>

                        <hr style="border:0; border-top:1px solid #333; margin:20px 0;">

                        <h4 style="color:#fff; font-size:0.9rem; margin-bottom:10px;">Configuração de Séries</h4>
                        
                        <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px;">
                            <label class="input-label" style="color:var(--gold); margin-bottom:10px; display:block;">Adicionar Série</label>
                            
                            <div class="set-inputs-row" style="display:flex; gap:10px; align-items:flex-end;">
                                <div style="flex:0 0 60px;">
                                    <label class="input-label" style="font-size:0.7rem;">Qtd</label>
                                    <input type="number" id="set_qtd" class="admin-input" value="1" style="padding:8px;">
                                </div>
                                <div style="flex:1; min-width:100px;">
                                    <label class="input-label" style="font-size:0.7rem;">Tipo</label>
                                    <select id="set_tipo" class="admin-input" style="padding:8px;">
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
                                    <input type="text" id="set_reps" class="admin-input" placeholder="Ex: 10" style="padding:8px;">
                                </div>
                                <div style="flex:1; min-width:70px;">
                                    <label class="input-label" style="font-size:0.7rem;">Descanso</label>
                                    <input type="text" id="set_desc" class="admin-input" placeholder="90s" style="padding:8px;">
                                </div>
                                <div style="flex:0 0 60px;">
                                    <label class="input-label" style="font-size:0.7rem;">RPE</label>
                                    <input type="number" id="set_rpe" class="admin-input" placeholder="-" style="padding:8px;">
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
                            <input type="text" name="nome_fase" id="micro_fase" class="admin-input" placeholder="Ex: Força ou Choque" required>
                        </div>

                        <h4 style="color:#fff; font-size:0.8rem; margin-bottom:5px; border-bottom:1px solid #333; padding-bottom:5px;">Multiarticulares / Compostos</h4>
                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:2;">
                                <label class="input-label">Faixa de Repetições</label>
                                <input type="text" name="reps_compostos" id="micro_reps_comp" class="admin-input" placeholder="Ex: 6 a 8">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Descanso (seg)</label>
                                <input type="number" name="descanso_compostos" id="micro_desc_comp" class="admin-input" placeholder="Ex: 120">
                            </div>
                        </div>

                        <h4 style="color:#fff; font-size:0.8rem; margin-bottom:5px; border-bottom:1px solid #333; padding-bottom:5px;">Isoladores / Monoarticulares</h4>
                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:2;">
                                <label class="input-label">Faixa de Repetições</label>
                                <input type="text" name="reps_isoladores" id="micro_reps_iso" class="admin-input" placeholder="Ex: 10 a 12">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Descanso (seg)</label>
                                <input type="number" name="descanso_isoladores" id="micro_desc_iso" class="admin-input" placeholder="Ex: 60">
                            </div>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label class="input-label">Foco / Comentário para o Aluno</label>
                            <textarea name="foco_comentario" id="micro_foco" class="admin-input" rows="3" placeholder="Ex: Focar na progressão de carga..."></textarea>
                        </div>

                        <button type="submit" class="btn-gold" style="width:100%;">SALVAR SEMANA</button>
                    </form>
                </div>
            </div>
        ';
        break;

    case 'perfil':
        require_once '../config/db_connect.php';
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $foto = $user['foto'] ? $user['foto'] : 'assets/img/user-default.png';

        echo '
            <section id="perfil-admin">
                <header class="dash-header">
                    <h1>CONFIGURAÇÕES DO <span class="highlight-text">ADMIN</span></h1>
                </header>

                <div class="glass-card profile-admin">
                    <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="admin-profile-layout">
                            
                            <div class="profile-photo-section">
                                <div class="photo-wrapper">
                                    <img src="'.$foto.'" id="admin-preview">
                                    <label for="admin-upload" class="upload-btn-float">
                                        <i class="fa-solid fa-pen"></i>
                                    </label>
                                    <input type="file" name="foto" id="admin-upload" style="display: none;" onchange="previewImageAdmin(this)">
                                </div>
                                <h3 style="margin-top: 15px; color: #fff; text-align: center; margin-bottom: 5px;">'.$user['nome'].'</h3>
                                <span class="status-badge" style="background: rgba(255,66,66,0.2); color: #ff4242;">MASTER ADMIN</span>
                            </div>

                            <div class="profile-form-section">
                                <h3 class="section-title" style="font-size: 1.1rem; margin-bottom: 15px;">Dados de Acesso</h3>
                                
                                <div class="form-profile">
                                    <div class="input-grid">
                                        <div>
                                            <label class="input-label">Nome Admin</label>
                                            <input type="text" name="nome" value="'.$user['nome'].'" class="input-field">
                                        </div>
                                        <div>
                                            <label class="input-label">Telefone</label>
                                            <input type="text" name="telefone" value="'.$user['telefone'].'" class="input-field">
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <label class="input-label">Email</label>
                                        <input type="email" name="email" value="'.$user['email'].'" class="input-field">
                                    </div>

                                    <hr class="form-divider">

                                    <h3 class="password-section-title" style="color: #ff4242; margin-bottom: 15px;">Segurança</h3>
                                    <div class="input-grid">
                                        <div>
                                            <label class="input-label">Nova Senha</label>
                                            <input type="password" name="nova_senha" class="input-field" placeholder="********">
                                        </div>
                                        <div>
                                            <label class="input-label">Confirmar</label>
                                            <input type="password" name="confirma_senha" class="input-field" placeholder="********">
                                        </div>
                                    </div>

                                    <div style="text-align: right; margin-top: 20px;">
                                        <button type="submit" class="btn-gold" style="background: #ff4242; color: #fff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold;">ATUALIZAR PERFIL</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        ';
        break;

    case 'dieta_editor':
        require_once '../config/db_connect.php';
        $aluno_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // 1. Busca Aluno
        $stmt = $pdo->prepare("SELECT nome, foto FROM usuarios WHERE id = ?");
        $stmt->execute([$aluno_id]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Busca Dieta ATIVA
        $stmt_d = $pdo->prepare("SELECT * FROM dietas WHERE aluno_id = ? LIMIT 1");
        $stmt_d->execute([$aluno_id]);
        $dieta = $stmt_d->fetch(PDO::FETCH_ASSOC);

        echo '<section id="editor-dieta">
                <header class="dash-header">
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <button onclick="carregarConteudo(\'alunos\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></button>
                        <img src="'.($aluno['foto'] ?: 'assets/img/user-default.png').'" style="width:50px; height:50px; border-radius:50%; border:2px solid var(--gold); object-fit:cover;">
                        <div>
                            <h1 style="font-size:1.5rem; margin:0;">EDITOR DE <span class="highlight-text">DIETA</span></h1>
                            <p class="text-desc" style="margin:0;">Atleta: '.$aluno['nome'].'</p>
                        </div>
                    </div>
                </header>';

        // --- ESTADO 1: SEM DIETA ---
        if (!$dieta) {
            echo '<div class="glass-card" style="text-align:center; padding:50px;">
                    <i class="fa-solid fa-utensils" style="font-size:3rem; color:#333; margin-bottom:20px;"></i>
                    <h3 style="color:#fff; margin-bottom:10px;">Nenhum plano alimentar encontrado</h3>
                    <p style="color:#888; margin-bottom:30px;">Crie a primeira dieta para este aluno começar.</p>
                    
                    <form action="actions/dieta_save.php" method="POST" style="max-width:400px; margin:auto;">
                        <input type="hidden" name="acao" value="criar_dieta">
                        <input type="hidden" name="aluno_id" value="'.$aluno_id.'">
                        
                        <input type="text" name="titulo" class="admin-input" placeholder="Título (Ex: Protocolo Cutting)" required style="margin-bottom:10px;">
                        <input type="text" name="objetivo" class="admin-input" placeholder="Objetivo (Ex: 2200 Kcal)" required style="margin-bottom:20px;">
                        
                        <button type="submit" class="btn-gold" style="width:100%;">CRIAR NOVA DIETA</button>
                    </form>
                    
                    <div style="margin-top:30px; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;">
                        <button class="btn-gold" style="background:transparent; border:1px solid #444; color:#888; font-size:0.8rem;" onclick="abrirModalImportar()">
                            <i class="fa-solid fa-file-import"></i> IMPORTAR MODELO DE OUTRO ALUNO
                        </button>
                    </div>
                  </div>';
        } 
        // --- ESTADO 2: COM DIETA (EDITOR) ---
        else {
            // Cálculo de Aderência
            $hoje = date('Y-m-d');
            $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM refeicoes WHERE dieta_id = ?");
            $stmt_total->execute([$dieta['id']]);
            $total_refs = $stmt_total->fetchColumn();

            $stmt_feito = $pdo->prepare("SELECT COUNT(*) FROM dieta_registro WHERE aluno_id = ? AND data_registro = ?");
            $stmt_feito->execute([$aluno_id, $hoje]);
            $feitos = $stmt_feito->fetchColumn();

            $porcentagem = ($total_refs > 0) ? round(($feitos / $total_refs) * 100) : 0;

            // Barra de Aderência
            echo '<div style="background: #222; padding: 10px 20px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #333; display: flex; align-items: center; justify-content: space-between;">
                    <span style="color: #aaa; font-size: 0.8rem;">Aderência Hoje:</span>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 100px; height: 6px; background: #444; border-radius: 3px; overflow: hidden;">
                            <div style="width: '.$porcentagem.'%; height: 100%; background: var(--gold);"></div>
                        </div>
                        <strong style="color: #fff;">'.$porcentagem.'%</strong>
                    </div>
                  </div>';

            echo '<div class="glass-card mb-large">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:20px;">
                        <div>
                            <h3 style="color:var(--gold); margin:0;">'.$dieta['titulo'].'</h3>
                            <span style="color:#888; font-size:0.9rem;">'.$dieta['objetivo'].'</span>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <a href="actions/dieta_save.php?acao=excluir_dieta&id='.$dieta['id'].'&aluno_id='.$aluno_id.'" class="btn-action-icon btn-delete" onclick="return confirm(\'Apagar toda a dieta?\')"><i class="fa-solid fa-trash"></i></a>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:30px;">
                        <button class="btn-gold" onclick="abrirModalRefeicao('.$dieta['id'].')">
                            <i class="fa-solid fa-plus"></i> NOVA REFEIÇÃO
                        </button>
                        
                        <button class="btn-gold" style="background:transparent; border:1px solid var(--gold); color:var(--gold);" onclick="abrirModalImportar()">
                            <i class="fa-solid fa-file-import"></i> IMPORTAR MODELO
                        </button>
                    </div>

                    <div class="diet-editor-list">';

            // Busca Refeições
            $stmt_ref = $pdo->prepare("SELECT * FROM refeicoes WHERE dieta_id = ? ORDER BY ordem ASC");
            $stmt_ref->execute([$dieta['id']]);
            $refeicoes = $stmt_ref->fetchAll(PDO::FETCH_ASSOC);

            if (empty($refeicoes)) {
                 echo '<p style="text-align:center; color:#666; padding:20px;">Nenhuma refeição cadastrada. Comece adicionando uma.</p>';
            }

            foreach($refeicoes as $ref) {
                echo '<div class="meal-edit-card" style="background:#1a1a1a; border:1px solid #333; border-radius:12px; margin-bottom:20px; overflow:hidden;">
                        
                        <div class="meal-header" style="background:#222; padding:15px; display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="background:var(--gold); color:#000; padding:4px 8px; border-radius:6px; font-weight:bold; font-size:0.8rem;">'.date('H:i', strtotime($ref['horario'])).'</span>
                                <strong style="color:#fff;">'.$ref['nome'].'</strong>
                            </div>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-action-icon" onclick="abrirModalAlimento('.$ref['id'].')" title="Add Alimento"><i class="fa-solid fa-plus"></i></button>
                                <a href="actions/dieta_save.php?acao=excluir_refeicao&id='.$ref['id'].'&aluno_id='.$aluno_id.'" class="btn-action-icon btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>

                        <div class="meal-items" style="padding:15px;">';
                        
                        // Busca Itens
                        $stmt_it = $pdo->prepare("SELECT * FROM itens_dieta WHERE refeicao_id = ? ORDER BY opcao_numero ASC");
                        $stmt_it->execute([$ref['id']]);
                        $itens = $stmt_it->fetchAll(PDO::FETCH_ASSOC);

                        if(empty($itens)) {
                            echo '<p style="color:#666; font-style:italic; font-size:0.9rem; text-align:center;">Nenhum alimento adicionado.</p>';
                        } else {
                            foreach($itens as $it) {
                                $tipo = ($it['opcao_numero'] == 1) ? '<span style="color:#00e676; font-size:0.7rem; font-weight:bold;">[PRINCIPAL]</span>' : '<span style="color:#ff9100; font-size:0.7rem; font-weight:bold;">[OPÇÃO '.$it['opcao_numero'].']</span>';
                                
                                echo '<div style="display:flex; justify-content:space-between; align-items:flex-start; padding:10px 0; border-bottom:1px solid #2a2a2a;">
                                        <div style="flex:1;">
                                            '.$tipo.'
                                            <strong style="display:block; color:#eee; font-size:0.95rem;">'.$it['descricao'].'</strong>
                                            '.($it['observacao'] ? '<small style="color:#888;">Obs: '.$it['observacao'].'</small>' : '').'
                                        </div>
                                        <a href="actions/dieta_save.php?acao=excluir_item&id='.$it['id'].'&aluno_id='.$aluno_id.'" style="color:#666; margin-left:10px;"><i class="fa-solid fa-xmark"></i></a>
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

        // --- MODAIS (HTML APENAS) ---
        
        // 1. Nova Refeição
        echo '<div id="modalNovaRefeicao" class="modal-overlay" style="display:none;">
            <div class="modal-content selection-modal" style="text-align:left; max-width:400px;">
                <button class="modal-close" onclick="fecharModalRefeicao()">&times;</button>
                <h3 class="modal-title" style="text-align:center;">Nova Refeição</h3>
                <form action="actions/dieta_save.php" method="POST">
                    <input type="hidden" name="acao" value="add_refeicao">
                    <input type="hidden" name="dieta_id" id="modal_dieta_id">
                    <input type="hidden" name="aluno_id" value="'.$aluno_id.'">
                    
                    <label class="input-label">Nome (Ex: Café da Manhã)</label>
                    <input type="text" name="nome" class="admin-input" required style="margin-bottom:15px;">
                    
                    <label class="input-label">Horário Sugerido</label>
                    <input type="time" name="horario" class="admin-input" required style="margin-bottom:15px;">
                    
                    <label class="input-label">Ordem (1=Primeira, 2=Segunda...)</label>
                    <input type="number" name="ordem" class="admin-input" value="1" required style="margin-bottom:20px;">
                    
                    <button type="submit" class="btn-gold" style="width:100%;">CRIAR REFEIÇÃO</button>
                </form>
            </div>
        </div>';

        // 2. Novo Alimento
        echo '<div id="modalNovoAlimento" class="modal-overlay" style="display:none;">
            <div class="modal-content selection-modal" style="text-align:left; max-width:400px;">
                <button class="modal-close" onclick="fecharModalAlimento()">&times;</button>
                <h3 class="modal-title" style="text-align:center;">Adicionar Alimento</h3>
                <form action="actions/dieta_save.php" method="POST">
                    <input type="hidden" name="acao" value="add_item">
                    <input type="hidden" name="refeicao_id" id="modal_refeicao_id">
                    <input type="hidden" name="aluno_id" value="'.$aluno_id.'">
                    
                    <label class="input-label">Tipo</label>
                    <select name="opcao_numero" class="admin-input" style="margin-bottom:15px;">
                        <option value="1">Opção Principal</option>
                        <option value="2">Opção 2 (Substituição)</option>
                        <option value="3">Opção 3 (Substituição)</option>
                    </select>
                    
                    <label class="input-label">Descrição (O que comer?)</label>
                    <textarea name="descricao" class="admin-input" rows="3" placeholder="Ex: 150g de Frango + 100g de Batata" required style="margin-bottom:15px;"></textarea>
                    
                    <label class="input-label">Observação (Opcional)</label>
                    <input type="text" name="observacao" class="admin-input" placeholder="Ex: Pode usar airfryer" style="margin-bottom:20px;">
                    
                    <button type="submit" class="btn-gold" style="width:100%;">ADICIONAR</button>
                </form>
            </div>
        </div>';

        // 3. Importar Dieta (NOVO)
        echo '<div id="modalImportar" class="modal-overlay" style="display:none;">
            <div class="modal-content selection-modal" style="text-align:left; max-width:400px;">
                <button class="modal-close" onclick="fecharModalImportar()">&times;</button>
                <h3 class="modal-title" style="text-align:center;">Copiar Dieta</h3>
                <p style="color:#ccc; font-size:0.9rem; text-align:center; margin-bottom:20px;">
                    Escolha um aluno para copiar a dieta dele para o <strong>'.$aluno['nome'].'</strong>.
                    <br><span style="color:#ff4242; font-size:0.8rem;">(Isso substituirá a dieta atual!)</span>
                </p>
                
                <form action="actions/dieta_save.php" method="POST">
                    <input type="hidden" name="acao" value="importar_dieta">
                    <input type="hidden" name="aluno_destino_id" value="'.$aluno_id.'">
                    
                    <label class="input-label">Copiar de qual aluno?</label>
                    <select name="aluno_origem_id" class="admin-input" required style="margin-bottom:20px;">
                        <option value="">Selecione...</option>';
                        
                        // Busca alunos que JÁ TÊM dieta criada para servir de modelo
                        // (excluindo o próprio aluno para não copiar de si mesmo)
                        $stmt_m = $pdo->query("SELECT u.id, u.nome, d.titulo FROM usuarios u JOIN dietas d ON u.id = d.aluno_id WHERE d.ativo = 1 AND u.id != $aluno_id ORDER BY u.nome ASC");
                        $modelos = $stmt_m->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($modelos)) {
                            echo '<option value="" disabled>Nenhum modelo disponível</option>';
                        } else {
                            foreach($modelos as $m) {
                                echo '<option value="'.$m['id'].'">'.$m['nome'].' - '.$m['titulo'].'</option>';
                            }
                        }
        echo '      </select>
                    
                    <button type="submit" class="btn-gold" style="width:100%;">COPIAR AGORA</button>
                </form>
            </div>
        </div>';

        break;

    // --- NOVO MENU GERAL ADMIN ---
    case 'admin_menu':
        require_once '../config/db_connect.php';
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("SELECT nome, email, foto FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $foto = $admin['foto'] ? $admin['foto'] : 'assets/img/user-default.png';

        echo '<section id="admin-hub" class="fade-in">
                
                <div class="menu-profile-header" onclick="carregarConteudo(\'perfil\')" style="background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); margin-bottom: 25px; padding: 20px; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="'.$foto.'" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #ff4242;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #fff; font-size: 1.1rem;">'.$admin['nome'].'</h3>
                            <span class="usuario-level" style="color: #ff4242; background: rgba(255, 66, 66, 0.1);">MASTER COACH</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: #666;"></i>
                </div>

                <h3 class="section-label" style="margin-left: 10px; color: #666; font-size: 0.8rem; font-weight: bold; margin-bottom: 10px;">FERRAMENTAS</h3>
                
                <div class="menu-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
                    
                    <div class="menu-card" onclick="carregarConteudo(\'alunos\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-users" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Gerenciar Alunos</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'treinos_editor\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-dumbbell" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Editor de Treinos</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'financeiro\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-sack-dollar" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Fluxo de Caixa</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'perfil\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-gear" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Configurações</span>
                    </div>

                </div>

                <h3 class="section-label" style="margin-left: 10px; color: #666; font-size: 0.8rem; font-weight: bold; margin-bottom: 10px;">SISTEMA</h3>
                
                <div class="settings-list" style="background: #161616; border-radius: 16px; border: 1px solid #333;">
                    
                    <div class="setting-item" onclick="window.location.href=\'index.php\'" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #222; cursor: pointer;">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <i class="fa-solid fa-globe" style="color: #888;"></i>
                            <span style="color: #fff;">Ver Site Principal</span>
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square" style="color: #444; font-size: 0.8rem;"></i>
                    </div>

                    <div class="setting-item" onclick="window.location.href=\'actions/logout.php\'" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <i class="fa-solid fa-right-from-bracket" style="color: #ff4242;"></i>
                            <span style="color: #ff4242;">Sair do Sistema</span>
                        </div>
                    </div>

                </div>

              </section>';
        break;

    default:
        echo '<section><h1>Página não encontrada</h1></section>';
        break;
}
?>