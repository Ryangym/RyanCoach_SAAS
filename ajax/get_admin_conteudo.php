<?php
if(session_status() === PHP_SESSION_NONE) session_start();

$pagina = $_GET['pagina'] ?? 'dashboard';

// Pega o nome do Admin
$nome_admin = $_SESSION['user_nome'] ?? 'Admin';
$partes_admin = explode(' ', trim($nome_admin));
$primeiro_nome_admin = strtoupper($partes_admin[0]);

session_write_close();

switch ($pagina) {
    case 'dashboard':
        require_once '../config/db_connect.php';

        // 1. TOTAIS ESTRATÉGICOS (KPIs)
        
        // Total Coaches (Seus Clientes B2B)
        $sql_coaches = "SELECT COUNT(*) FROM usuarios WHERE tipo_conta IN ('coach', 'personal')";
        $total_coaches = $pdo->query($sql_coaches)->fetchColumn();

        // Total Alunos (Usuários Finais)
        $sql_alunos = "SELECT COUNT(*) FROM usuarios WHERE tipo_conta = 'atleta' OR tipo_conta = 'aluno'";
        $total_alunos = $pdo->query($sql_alunos)->fetchColumn();

        // Receita do Admin (Tudo que não tem coach_id ou é pagamento de coach)
        // Lógica simplificada: Tudo que entrou no mês
        $sql_receita = "SELECT SUM(valor) as total FROM pagamentos WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())";
        $receita_total = $pdo->query($sql_receita)->fetchColumn() ?? 0;

        // Pendências Gerais
        $sql_pend = "SELECT COUNT(*) FROM pagamentos WHERE status = 'pendente'";
        $total_pendencias = $pdo->query($sql_pend)->fetchColumn();


        // 2. LISTAS INTELIGENTES
        
        // A. Novos Coaches (Quem entrou recentemente)
        $sql_novos_coaches = "SELECT nome, foto, data_cadastro, tipo_conta 
                              FROM usuarios 
                              WHERE tipo_conta IN ('coach', 'personal') 
                              ORDER BY id DESC LIMIT 4";
        $lista_coaches = $pdo->query($sql_novos_coaches)->fetchAll(PDO::FETCH_ASSOC);

        // B. Novos Alunos (Crescimento da base)
        $sql_novos_alunos = "SELECT nome, foto, data_cadastro 
                             FROM usuarios 
                             WHERE tipo_conta = 'atleta' 
                             ORDER BY id DESC LIMIT 4";
        $lista_alunos = $pdo->query($sql_novos_alunos)->fetchAll(PDO::FETCH_ASSOC);

        // Saudação
        $nome_admin = $_SESSION['user_nome'] ?? 'Admin';
        $primeiro_nome = explode(' ', trim($nome_admin))[0];

        echo '
            <section id="admin-dash">
                <header class="dash-header">
                    <h1>OLÁ, <span class="highlight-text" style="color: var(--color-admin);">'.strtoupper($primeiro_nome).'.</span></h1>
                    <p style="color: #888;">Painel de Controle Master</p>
                </header>

                <div class="stats-row" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    
                    <div class="glass-card">
                        <div class="card-label">CLIENTES (PERSONAIS)</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: var(--color-coach); border-color: var(--color-coach); background: var(--bg-coach);">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_coaches.'</h3>
                                <p>Ativos</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">USUÁRIOS FINAIS</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #00ff00; border-color: rgba(0, 255, 0, 0.3); background: rgba(0, 255, 0, 0.1);">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_alunos.'</h3>
                                <p>Atletas</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">FATURAMENTO GLOBAL</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #fff; border-color: rgba(255, 255, 255, 0.3); background: rgba(255, 255, 255, 0.1);">
                                <i class="fa-solid fa-earth-americas"></i>
                            </div>
                            <div class="info-box">
                                <h3>R$ '.number_format($receita_total, 2, ',', '.').'</h3>
                                <p>Mês Atual</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">PENDÊNCIAS</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #ffcc00; border-color: rgba(255, 204, 0, 0.3); background: rgba(255, 204, 0, 0.1);">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="info-box">
                                <h3>'.$total_pendencias.'</h3>
                                <p>A Receber</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="insights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 10px;">
                    
                    <div class="glass-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: #fff; font-family: Orbitron; font-size: 1rem; margin:0;">
                                <i class="fa-solid fa-user-tie" style="color: var(--color-coach); margin-right: 10px;"></i> NOVOS PERSONAIS
                            </h3>
                            <button class="btn-gold" style="padding: 5px 10px; font-size: 0.7rem; background: transparent; border: 1px solid #333; color: #888;" onclick="carregarConteudo(\'alunos\')">VER TODOS</button>
                        </div>
                        
                        <div style="padding: 10px;">';
                        
                        if(count($lista_coaches) > 0) {
                            foreach($lista_coaches as $c) {
                                $foto = !empty($c['foto']) ? $c['foto'] : 'assets/img/user-default.png';
                                $data = date('d/m', strtotime($c['data_cadastro']));
                                $badge = strtoupper($c['tipo_conta']);
                                
                                echo '
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; margin-bottom: 5px; background: rgba(255,255,255,0.02); border-radius: 8px; transition: 0.3s; cursor: pointer;" onmouseover="this.style.background=\'rgba(255,255,255,0.05)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.02)\'">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="'.$foto.'" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--color-coach);">
                                        <div>
                                            <h4 style="color: #fff; font-size: 0.9rem; margin: 0;">'.$c['nome'].'</h4>
                                            <span style="color: var(--color-coach); font-size: 0.7rem; font-weight: bold;">'.$badge.'</span>
                                        </div>
                                    </div>
                                    <span style="color: #666; font-size: 0.75rem;">'.$data.'</span>
                                </div>';
                            }
                        } else {
                            echo '<p style="text-align: center; color: #666; padding: 20px;">Nenhum coach recente.</p>';
                        }

        echo '          </div>
                    </div>

                    <div class="glass-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: #fff; font-family: Orbitron; font-size: 1rem; margin:0;">
                                <i class="fa-solid fa-users" style="color: #00ff00; margin-right: 10px;"></i> CRESCIMENTO
                            </h3>
                            <span style="font-size: 0.7rem; color: #666; text-transform: uppercase;">Novos Alunos</span>
                        </div>
                        
                        <div style="padding: 10px;">';
                        
                        if(count($lista_alunos) > 0) {
                            foreach($lista_alunos as $a) {
                                $foto = !empty($a['foto']) ? $a['foto'] : 'assets/img/user-default.png';
                                $data = date('d/m', strtotime($a['data_cadastro']));

                                echo '
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; margin-bottom: 5px; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="'.$foto.'" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; opacity: 0.8;">
                                        <div>
                                            <h4 style="color: #ddd; font-size: 0.9rem; margin: 0;">'.$a['nome'].'</h4>
                                            <span style="color: #666; font-size: 0.75rem;">Atleta</span>
                                        </div>
                                    </div>
                                    <span style="color: #666; font-size: 0.75rem;">'.$data.'</span>
                                </div>';
                            }
                        } else {
                            echo '<p style="text-align: center; color: #666; padding: 20px;">Nenhum aluno recente.</p>';
                        }

        echo '          </div>
                    </div>

                </div>
            </section>
        ';
        break;

    case 'alunos':
        require_once '../config/db_connect.php';
        
        // OTIMIZAÇÃO: Limita a 100 usuários iniciais para não travar o carregamento
        // Seleciona apenas colunas necessárias
        $sql = "SELECT id, nome, email, telefone, foto, tipo_conta, plano_atual, coach_id, data_expiracao_plano 
                FROM usuarios 
                WHERE tipo_conta != 'admin' 
                ORDER BY nome ASC 
                LIMIT 100";
        
        $alunos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $total_alunos_exibidos = count($alunos);
        
        // Conta o total real no banco (leve, pois usa count)
        $total_real = $pdo->query("SELECT COUNT(id) FROM usuarios WHERE tipo_conta != 'admin'")->fetchColumn();

        echo '
            <section id="gerenciar-alunos">
                <header class="dash-header">
                    <h1>GERENCIAR <span class="highlight-text">USUÁRIOS</span></h1>
                    <p class="text-desc">Exibindo '.$total_alunos_exibidos.' de '.$total_real.' usuários.</p>
                </header>
                
                <div class="glass-card mt-large">
                    <div class="section-header-row">
                        <div style="flex: 1; position: relative; max-width: 400px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666;"></i>
                            <input type="text" id="searchAluno" onkeyup="filtrarAlunos()" placeholder="Filtrar nesta lista..." class="admin-input" style="padding-left: 40px;">
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
                            
                            if ($total_alunos_exibidos > 0) {
                                foreach ($alunos as $a) {
                                    $foto = !empty($a['foto']) ? $a['foto'] : 'assets/img/user-default.png';
                                    
                                    // --- LÓGICA DE BADGES ATUALIZADA ---
                                    if ($a['tipo_conta'] === 'admin') {
                                        $nivelTag = '<span class="status-badge" style="background:var(--bg-admin); color:var(--color-admin); border:1px solid var(--color-admin);">ADMIN</span>';
                                    } elseif ($a['tipo_conta'] === 'personal' || $a['tipo_conta'] === 'coach') {
                                        $nivelTag = '<span class="status-badge" style="background:var(--bg-coach); color:var(--color-coach); border:1px solid var(--color-coach);">COACH</span>';
                                    } else {
                                        $nivelTag = '<span class="status-badge" style="background:var(--bg-atleta); color:var(--color-atleta); border:1px solid var(--color-atleta);">ATLETA</span>';
                                    }
                                    
                                    $zap_clean = preg_replace('/[^0-9]/', '', $a['telefone']);
                                    $link_zap = "https://wa.me/55".$zap_clean;

                                    // Prepara JSON seguro
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
                    </div>';
                    
                    if ($total_real > 100) {
                        echo '<p style="text-align:center; color:#666; font-size:0.8rem; margin-top:10px;">* Exibindo apenas os primeiros 100 usuários para otimizar a performance.</p>';
                    }

        echo '  </div>
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

                    <div style="margin-top:15px;">
                        <button onclick="hubAcao(\'indicacoes_modal\')" style="width:100%; background:rgba(255,186,66,0.1); color:var(--gold); border:1px solid var(--gold); padding:12px; border-radius:8px; cursor:pointer; font-weight:bold; transition:0.3s;">
                            <i class="fa-solid fa-users-viewfinder"></i> VER INDICAÇÕES
                        </button>
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
                                <option value="atleta">Atleta (Padrão)</option>
                                <option value="personal">Coach / Personal</option>
                                <option value="admin">Administrador (Acesso Total)</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="color:var(--gold); font-size: 0.8rem; font-weight:bold;">Plano Ativo</label>
                            <select name="plano_atual" id="edit_plano_atual" class="admin-input" style="border-color:var(--gold);">
                                <option value="start">Plano Start (Básico)</option>
                                <option value="pro">Plano Pro (Elite)</option>
                                <option value="coach">Plano Coach (Personal)</option>
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
                                
                                <div style="display: flex; gap: 10px;">
                                    <input type="date" name="data_expiracao_plano" id="edit_data_expiracao_plano" class="admin-input" style="flex:1;">
                                    
                                    <button type="button" onclick="adicionar30Dias()" 
                                            style="background: var(--gold); color: #000; border: none; border-radius: 5px; padding: 0 15px; cursor: pointer; font-weight: bold; font-size: 0.8rem; white-space: nowrap;">
                                            <i class="fa-solid fa-calendar-plus"></i> +30 Dias
                                    </button>
                                </div>
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
            <div id="modalListaIndicacoes" class="modal-overlay" style="display:none; align-items:center; justify-content:center;">
                <div class="modal-content" style="max-width: 600px; width: 100%;">
                    <button class="modal-close" onclick="document.getElementById(\'modalListaIndicacoes\').style.display=\'none\'">&times;</button>
                    
                    <h3 class="section-title" style="color: var(--gold); margin-bottom: 5px; text-align: center;">
                        <i class="fa-solid fa-people-group"></i> Indicações
                    </h3>
                    <p style="text-align:center; color:#888; margin-bottom:20px; font-size:0.9rem;">
                        Usuários que se cadastraram com o código deste aluno.
                    </p>

                    <div style="max-height: 300px; overflow-y: auto; background: rgba(0,0,0,0.3); border-radius: 8px; border: 1px solid #333;">
                        <table class="admin-table" style="margin:0;">
                            <thead>
                                <tr>
                                    <th style="background:#111; position:sticky; top:0;">Nome</th>
                                    <th style="background:#111; position:sticky; top:0;">Plano</th>
                                    <th style="background:#111; position:sticky; top:0;">Data</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_indicacoes">
                                </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px; color: #666; font-size: 0.8rem;">
                        Total encontrado: <strong id="total_indicacoes_badge" style="color: #fff;">0</strong>
                    </div>
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

        // Helpers de Medidas (Atualizados para a Edição Inline)
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

        echo '<section id="aluno-avaliacoes">
                
                <header class="dash-header">
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <button onclick="carregarConteudo(\'alunos\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></button>
                        <img src="'.($aluno['foto'] ?: 'assets/img/user-default.png').'" style="width:40px; object-fit:cover; height:40px; border-radius:50%; border:1px solid var(--gold);">
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
                                    '.($av['percentual_gordura'] ? '<span class="bf-tag bf-tag-display">BF '.($av['percentual_gordura']*1).'%</span>' : '').'
                                </div>
                                <span class="info-sub">'.count($arquivos).' mídias anexadas</span>
                            </div>
                            <div class="accordion-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>

                        <div class="accordion-body" style="display: none;">
                            <div class="body-padding">
                                <div class="stats-tiles">
                                    <div class="tile"><small>IMC</small><strong class="imc-display">'.($av['imc'] ?: '-').'</strong></div>
                                    <div class="tile"><small>M. MAGRA</small><strong class="magra-display">'.($av['massa_magra_kg'] ? $av['massa_magra_kg'].'kg' : '-').'</strong></div>
                                    <div class="tile"><small>M. GORDA</small><strong class="gorda-display">'.($av['massa_gorda_kg'] ? $av['massa_gorda_kg'].'kg' : '-').'</strong></div>
                                </div>';
                                
                                if (!empty($arquivos)) {
                                    echo '<div class="gallery-strip"><div class="strip-scroll">';
                                    foreach ($arquivos as $arq) {
                                        if ($arq['tipo'] == 'foto') echo '<div class="strip-item"><img src="assets/uploads/'.$arq['caminho_ou_url'].'" onclick="window.open(this.src)"></div>';
                                        else echo '<a href="'.$arq['caminho_ou_url'].'" target="_blank" class="strip-item video-item"><i class="fa-solid fa-play"></i></a>';
                                    }
                                    echo '</div></div>';
                                }

                                // GRID DE MEDIDAS (Incluindo Peso e Altura para o Inline Edit recalcular o BF)
                                echo '<div class="measures-container">
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
                                            <span class="mg-label">MEMBROS</span>
                                            <div class="mg-grid-wide">
                                                '.$renderMeasureDouble('Braço (Rel)', 'braco_dir_relaxado', $av['braco_dir_relaxado'], 'braco_esq_relaxado', $av['braco_esq_relaxado']).'
                                                '.$renderMeasureDouble('Braço (Con)', 'braco_dir_contraido', $av['braco_dir_contraido'], 'braco_esq_contraido', $av['braco_esq_contraido']).'
                                                '.$renderMeasureDouble('Coxa', 'coxa_dir', $av['coxa_dir'], 'coxa_esq', $av['coxa_esq']).'
                                                '.$renderMeasureDouble('Panturrilha', 'panturrilha_dir', $av['panturrilha_dir'], 'panturrilha_esq', $av['panturrilha_esq']).'
                                            </div>
                                        </div>
                                      </div>';
                                
                                if($av['observacoes']) echo '<div class="obs-box"><i class="fa-solid fa-quote-left"></i> '.$av['observacoes'].'</div>';

                                // Botão de Editar Medidas ao lado do de Excluir
                                echo '<div class="card-footer-actions" style="margin-top:30px; text-align:center; flex-direction: column; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px; display:flex; justify-content:center; gap: 10px;">
                                        
                                        <button class="btn-gold" id="btn-editar-av-'.$av['id'].'" onclick="alternarEdicaoAvaliacao('.$av['id'].', this)">
                                            <i class="fa-solid fa-pen"></i> Editar Medidas
                                        </button>

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
        require_once '../helpers/tradutor_treino.php'; // Chama o helper de tradução
        
        // Pega a preferência de idioma do usuário logado (Admin) para não quebrar a lógica de tradução das séries
        $idioma_sessao = $_SESSION['pref_idioma'] ?? 'pt';

        // Pega o ID do aluno via URL
        $aluno_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $data_ref = $_GET['data_ref'] ?? null;

        if (!$aluno_id) { 
            echo "<div class='alert-box'>Aluno não identificado.</div>"; 
            break; 
        }

        // Busca dados do aluno para o cabeçalho
        $stmt_aluno = $pdo->prepare("SELECT nome, foto FROM usuarios WHERE id = ?");
        $stmt_aluno->execute([$aluno_id]);
        $dados_aluno = $stmt_aluno->fetch(PDO::FETCH_ASSOC);
        $nome_aluno = $dados_aluno['nome'] ?? 'Aluno';
        $foto_aluno = $dados_aluno['foto'] ?? 'assets/img/user-default.png';

        // --- MODO 1: DETALHES DO TREINO ---
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

            // 2. Busca Detalhes (COM s.tecnica)
            $sql_detalhes = "SELECT th.*, e.nome_exercicio, s.categoria, s.tecnica 
                              FROM treino_historico th
                              JOIN exercicios e ON th.exercicio_id = e.id
                              LEFT JOIN series s ON COALESCE(th.serie_id, th.serie_numero) = s.id 
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
                    $treino_agrupado[$id_ex] = ['nome' => $reg['nome_exercicio'], 'series' => []];
                }
                $treino_agrupado[$id_ex]['series'][] = $reg;
            }

            echo '<section id="admin-historico-detalhe" class="fade-in">
                    
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <button onclick="carregarConteudo(\'aluno_historico&id='.$aluno_id.'\')" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <div>
                                <span style="color:#888; font-size:0.8rem; text-transform:uppercase;">Visualizando</span>
                                <h2 style="margin:0; color:#fff; font-size:1.2rem;">TREINO '.($info['letra'] ?? '?').'</h2>
                            </div>
                        </div>

                        <div style="display:flex; gap:10px;">
                            <button id="btn-editar-hist-adm" onclick="alternarEdicaoHistoricoAdm(\''.$aluno_id.'\')" style="width:40px; height:40px; border-radius:50%; background:rgba(255, 186, 66, 0.1); border:1px solid var(--gold); color:var(--gold); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.3s;">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="deletarHistoricoAdm(\''.$data_ref.'\', \''.$aluno_id.'\')" style="width:40px; height:40px; border-radius:50%; background:rgba(255,66,66,0.1); border:1px solid #ff4242; color:#ff4242; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.3s;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; padding:15px; background:rgba(255,186,66,0.1); border-radius:8px; border:1px solid var(--gold); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:var(--gold); display:block;">'.($info['nome_treino'] ?? 'Treino').'</strong>
                            <span style="color:#ccc; font-size:0.8rem;">'.date('d/m/Y \à\s H:i', strtotime($data_ref)).'</span>
                            <span style="color:#fff; font-size:0.8rem; display:block; margin-top:5px;">Aluno: '.$nome_aluno.'</span>
                        </div>
                        <i class="fa-solid fa-calendar-check" style="color:var(--gold); font-size:1.5rem;"></i>
                    </div>

                    <div class="history-details-list-adm">';
                    
                    if (empty($treino_agrupado)) echo '<p style="text-align:center; color:#666;">Nenhum dado encontrado.</p>';

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
                                    
                                    foreach ($dados['series'] as $serie) {
                                        $id_hist = $serie['id'];
                                        $dados_tecnicos = !empty($serie['dados_tecnicos']) ? json_decode($serie['dados_tecnicos'], true) : [];
                                        $tecnica_original = strtolower($serie['tecnica'] ?? '');

                                        // --- LÓGICA VISUAL COM TRADUÇÃO ---
                                        
                                        // 1. Definição Padrão
                                        $cat_visual = $serie['categoria'] ? strtolower($serie['categoria']) : 'work';
                                        
                                        // AQUI APLICAMOS A TRADUÇÃO!
                                        $label_visual = traduzirTermo($cat_visual, $idioma_sessao);
                                        
                                        $num_display = '#'.($serie['numero_serie'] > 0 ? $serie['numero_serie'] : '-');
                                        $num_style = "padding:10px; color:#666; font-weight:bold;"; 
                                        $row_style = "border-bottom:1px solid #1f1f1f;"; // Padrão admin
                                        $reps_display = $serie['reps_realizadas'];

                                        // 2. Drop Set
                                        if ($tecnica_original === 'dropset') {
                                            $cat_visual = 'technique-drop';
                                            $label_visual = traduzirTermo('dropset', $idioma_sessao);
                                            
                                            // A. Drop Filho
                                            if (isset($dados_tecnicos['drop_index'])) {
                                                $idx_drop = $dados_tecnicos['drop_index'];
                                                $num_display = '<i class="fa-solid fa-turn-up fa-rotate-90" style="margin-right:5px; font-size:0.7rem; opacity:0.7;"></i> DROP '.$idx_drop;
                                                $num_style = "padding:10px; color:#ff4081; font-weight:bold; font-size:0.8rem;"; 
                                                $row_style = "border-bottom:1px solid #1f1f1f; background: linear-gradient(90deg, rgba(255, 64, 129, 0.1) 0%, rgba(0,0,0,0) 100%);";
                                                
                                                $label_visual = ''; 
                                                $cat_visual = ''; 
                                            } 
                                            // B. Drop Pai
                                            else {
                                                $num_style = "padding:10px; color:#ff4081; font-weight:bold;";
                                            }
                                        }

                                        // 3. Rest Pause
                                        elseif ($tecnica_original === 'restpause' || (isset($dados_tecnicos['tipo']) && $dados_tecnicos['tipo'] === 'restpause')) {
                                            $cat_visual = 'technique-rest';
                                            $label_visual = traduzirTermo('restpause', $idioma_sessao);
                                            if (!empty($dados_tecnicos['reps_string'])) $reps_display = $dados_tecnicos['reps_string'];
                                        }

                                        // 4. Cluster
                                        elseif ($tecnica_original === 'clusterset' || (isset($dados_tecnicos['tipo']) && $dados_tecnicos['tipo'] === 'clusterset')) {
                                            $cat_visual = 'technique-cluster';
                                            $label_visual = traduzirTermo('clusterset', $idioma_sessao);
                                            if (!empty($dados_tecnicos['reps_string'])) $reps_display = $dados_tecnicos['reps_string'];
                                        }

                                        // --- RENDERIZAÇÃO ---
                                        echo '<tr style="'.$row_style.'">
                                                <td style="'.$num_style.'">'.$num_display.'</td>
                                                
                                                <td style="padding:10px;">';
                                                if ($label_visual !== '') {
                                                    // CSS externo cuida das cores baseado na classe $cat_visual
                                                    echo '<span class="badge-set-type '.$cat_visual.'">'.$label_visual.'</span>';
                                                }
                                        echo '  </td>
                                                
                                                <td class="editable-cell-adm" data-id="'.$id_hist.'" data-type="carga" style="padding:10px;">
                                                    <span class="view-val" style="color:#fff; font-weight:bold;">'.($serie['carga_kg']*1).'</span>
                                                    <input type="number" step="0.1" class="edit-input" value="'.($serie['carga_kg']*1).'" style="display:none; width:60px; background:#222; border:1px solid #444; color:#fff; padding:5px; border-radius:4px; text-align:center;">
                                                </td>

                                                <td class="editable-cell-adm" data-id="'.$id_hist.'" data-type="reps" style="padding:10px;">
                                                    <span class="view-val" style="color:#fff;">'.$reps_display.'</span>
                                                    <input type="text" class="edit-input" value="'.$reps_display.'" style="display:none; width:50px; background:#222; border:1px solid #444; color:#fff; padding:5px; border-radius:4px; text-align:center;">
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

        // --- MODO 2: LISTA TIMELINE (Mantenha o código existente do modo lista abaixo...) ---
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

        echo '<section id="admin-historico-lista" class="fade-in">
                <header class="dash-header">
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <img src="'.$foto_aluno.'" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:2px solid var(--gold);">
                        <div>
                            <h1 style="font-size:1.5rem; margin:0;">HISTÓRICO <span class="highlight-text">DO ALUNO</span></h1>
                            <p class="text-desc" style="margin:0;">'.$nome_aluno.'</p>
                        </div>
                    </div>
                </header>';

        if (empty($historico)) {
            echo '<div class="empty-state" style="text-align:center; padding:50px 20px; color:#666;">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
                    <h2>Nenhum treino registrado</h2>
                </div>';
        } else {
            echo '<div class="history-list" style="display:flex; flex-direction:column; gap:12px; padding-bottom:90px;">';
            foreach ($historico as $h) {
                $data_obj = new DateTime($h['data_treino']);
                $dia = $data_obj->format('d');
                $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                $mes_txt = $meses[(int)$data_obj->format('m') - 1];
                $hora = $data_obj->format('H:i');
                $treino_nome = $h['nome_treino'] ? $h['nome_treino'] : 'Treino Arquivado';
                $letra = $h['letra'] ? $h['letra'] : '?';

                echo '<div class="history-card" onclick="carregarConteudo(\'aluno_historico&id='.$aluno_id.'&data_ref='.$h['data_treino'].'\')" 
                           style="background:linear-gradient(145deg, #1a1a1a, #111); border:1px solid rgba(255,255,255,0.05); border-radius:16px; padding:18px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; position:relative; overflow:hidden;">
                        <div style="position:absolute; left:0; top:0; bottom:0; width:5px; background:linear-gradient(to bottom, var(--gold), #b8860b);"></div>
                        <div class="hist-date-box" style="padding-right:15px; margin-right:15px; border-right:1px solid rgba(255,255,255,0.1); text-align:center; min-width:65px; margin-left:10px;">
                            <span class="hist-day" style="display:block; font-size:1.5rem; font-weight:800; color:#fff; line-height:1;">'.$dia.'</span>
                            <span class="hist-month" style="display:block; font-size:0.7rem; text-transform:uppercase; color:var(--gold); font-weight:bold; margin-top:2px;">'.$mes_txt.'</span>
                        </div>
                        <div class="hist-info" style="flex:1;">
                            <span class="hist-title" style="display:block; font-size:1.05rem; font-weight:700; color:#fff; margin-bottom:2px;">Treino '.$letra.'</span>
                            <span class="hist-sub" style="font-size:0.8rem; color:#777;">'.$treino_nome.' • '.$hora.'</span>
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
                        
                        <form id="formNovoTreino" onsubmit="criarTreino(event)">
                            
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
                                    </select>
                                </div>
                                <div class="form-col">
                                    <label class="input-label">Data de Início</label>
                                    <input type="date" name="data_inicio" class="admin-input" required value="'.date('Y-m-d').'">
                                </div>
                                <div class="form-col" style="flex: 0 0 120px;">
                                    <label class="input-label">Divisão</label>
                                    <input type="text" name="divisao" class="admin-input" placeholder="ABC" maxlength="7" style="text-transform:uppercase;" required>
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
        
        // --- 1. OTIMIZAÇÃO DE DATAS (Para usar Índices) ---
        // Em vez de MONTH() e YEAR(), usamos BETWEEN (muito mais rápido no SQL)
        $primeiro_dia = date('Y-m-01 00:00:00');
        $ultimo_dia   = date('Y-m-t 23:59:59');

        // Filtro: "Caixa do Admin"
        // Usuários sem coach (NULL/0) OU tipo 'coach'/'personal'
        $filtro_admin_sql = " AND (u.coach_id IS NULL OR u.coach_id = 0 OR u.tipo_conta IN ('coach', 'personal')) ";

        // --- 2. CÁLCULOS TOTAIS (OTIMIZADOS) ---
        
        // CARD 1: Faturamento GLOBAL (Mês Atual)
        // Usa índice na data_pagamento
        $stmt_fat = $pdo->prepare("SELECT SUM(valor) FROM pagamentos WHERE status = 'pago' AND data_pagamento BETWEEN ? AND ?");
        $stmt_fat->execute([$primeiro_dia, $ultimo_dia]);
        $fat_global = $stmt_fat->fetchColumn() ?? 0;

        // CARD 2: Faturamento ADMIN (Mês Atual)
        // Otimizado com JOIN direto
        $sql_fat_admin = "SELECT SUM(p.valor) FROM pagamentos p 
                          JOIN usuarios u ON p.usuario_id = u.id 
                          WHERE p.status = 'pago' 
                          AND p.data_pagamento BETWEEN ? AND ?" 
                          . $filtro_admin_sql;
        $stmt_fat_adm = $pdo->prepare($sql_fat_admin);
        $stmt_fat_adm->execute([$primeiro_dia, $ultimo_dia]);
        $fat_admin = $stmt_fat_adm->fetchColumn() ?? 0;

        // CARD 3: Pendente ADMIN (Total Geral Pendente)
        $sql_pend_admin = "SELECT SUM(p.valor) FROM pagamentos p 
                           JOIN usuarios u ON p.usuario_id = u.id 
                           WHERE p.status = 'pendente'" 
                           . $filtro_admin_sql;
        $pend_admin = $pdo->query($sql_pend_admin)->fetchColumn() ?? 0;


        // --- 3. LISTAS (PAGINADAS / LIMITADAS) ---
        
        // Lista A: CAIXA ADMIN
        $sql_lista_admin = "SELECT p.id, p.descricao, p.valor, p.status, p.data_pagamento, p.data_vencimento,
                                   u.nome as nome_pagador, u.foto as foto_pagador, u.tipo_conta,
                                   c.nome as nome_coach
                            FROM pagamentos p 
                            LEFT JOIN usuarios u ON p.usuario_id = u.id 
                            LEFT JOIN usuarios c ON u.coach_id = c.id
                            WHERE 1=1 " . $filtro_admin_sql . "
                            ORDER BY p.id DESC LIMIT 50";
        $lista_admin = $pdo->query($sql_lista_admin)->fetchAll(PDO::FETCH_ASSOC);

        // Lista B: GLOBAL (Tudo)
        $sql_lista_global = "SELECT p.id, p.descricao, p.valor, p.status, p.data_pagamento, p.data_vencimento,
                                    u.nome as nome_pagador, u.foto as foto_pagador, u.tipo_conta,
                                    c.nome as nome_coach
                             FROM pagamentos p 
                             LEFT JOIN usuarios u ON p.usuario_id = u.id 
                             LEFT JOIN usuarios c ON u.coach_id = c.id
                             ORDER BY p.id DESC LIMIT 50";
        $lista_global = $pdo->query($sql_lista_global)->fetchAll(PDO::FETCH_ASSOC);


        // --- 4. OTIMIZAÇÃO DO MODAL DE USUÁRIOS ---
        // Buscamos apenas colunas essenciais e limitamos se for muito grande
        // Se tiver mais de 200 usuários, o ideal seria buscar via AJAX ao digitar
        $usuarios_list = $pdo->query("SELECT id, nome, foto, tipo_conta FROM usuarios WHERE tipo_conta IN ('atleta', 'aluno', 'coach', 'personal') ORDER BY nome ASC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);


        echo '
            <section id="financeiro">
                <header class="dash-header">
                    <h1>CONTROLE <span class="highlight-text">FINANCEIRO (ADMIN)</span></h1>
                </header>

                <div class="stats-row">
                    <div class="glass-card">
                        <div class="card-label">FATURAMENTO GLOBAL (MÊS)</div>
                        <div class="card-body">
                            <div class="icon-box" style="color: #fff; border-color: #fff;"><i class="fa-solid fa-earth-americas"></i></div>
                            <div class="info-box">
                                <h3>R$ '.number_format($fat_global, 2, ',', '.').'</h3>
                                <p class="text-muted">Total Plataforma</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">RECEITA ADMIN (MÊS)</div>
                        <div class="card-body">
                            <div class="icon-box success"><i class="fa-solid fa-wallet"></i></div>
                            <div class="info-box">
                                <h3>R$ '.number_format($fat_admin, 2, ',', '.').'</h3>
                                <p class="text-muted">Caixa Próprio</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="card-label">PENDENTE ADMIN</div>
                        <div class="card-body">
                            <div class="icon-box gold"><i class="fa-solid fa-clock"></i></div>
                            <div class="info-box">
                                <h3>R$ '.number_format($pend_admin, 2, ',', '.').'</h3>
                                <p class="text-muted">A Receber Próprio</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card mt-large">
                    
                    <div class="section-header-row" style="flex-wrap: wrap; gap: 15px;">
                        <div class="toggle-buttons" style="display: flex; background: #222; padding: 5px; border-radius: 8px; border: 1px solid #333;">
                            <button id="btn-tab-admin" onclick="alternarVisaoFinanceiro(\'admin\')" style="background: var(--gold); color: #000; border: none; padding: 8px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                                <i class="fa-solid fa-user-shield"></i> Admin
                            </button>
                            <button id="btn-tab-global" onclick="alternarVisaoFinanceiro(\'global\')" style="background: transparent; color: #888; border: none; padding: 8px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                                <i class="fa-solid fa-globe"></i> Global
                            </button>
                        </div>

                        <button class="btn-gold" onclick="document.getElementById(\'modalLancamentoAdm\').style.display=\'flex\'" style="background: var(--gold); border: none;">
                            <i class="fa-solid fa-plus"></i> NOVO LANÇAMENTO
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>PAGADOR</th>
                                    <th>TIPO</th>
                                    <th>DESCRIÇÃO</th>
                                    <th>DATA</th>
                                    <th>VALOR</th>
                                    <th>STATUS</th>
                                    <th style="text-align:right;">AÇÃO</th>
                                </tr>
                            </thead>
                            
                            <tbody id="tbody-admin">
                                ';
                                if (count($lista_admin) > 0) {
                                    foreach ($lista_admin as $t) {
                                        $statusClass = ($t['status'] == 'pago') ? 'pago' : 'pendente';
                                        $dataShow = ($t['status'] == 'pago' && !empty($t['data_pagamento'])) ? $t['data_pagamento'] : $t['data_vencimento'];
                                        $dataExibicao = date('d/m/y', strtotime($dataShow));
                                        $foto = !empty($t['foto_pagador']) ? $t['foto_pagador'] : 'assets/img/user-default.png';
                                        $nomePagador = $t['nome_pagador'] ?: 'Desconhecido';
                                        $tipoConta = ($t['tipo_conta'] == 'coach' || $t['tipo_conta'] == 'personal') ? '<span style="color:var(--color-coach); font-size:0.7rem; font-weight:bold;">COACH</span>' : '<span style="color:#888; font-size:0.7rem;">ALUNO</span>';

                                        echo '<tr>
                                            <td><div class="user-cell"><img src="'.$foto.'" class="table-avatar"><span>'.$nomePagador.'</span></div></td>
                                            <td>'.$tipoConta.'</td>
                                            <td>'.$t['descricao'].'</td>
                                            <td>'.$dataExibicao.'</td>
                                            <td><strong>R$ '.number_format($t['valor'], 2, ',', '.').'</strong></td>
                                            <td><span class="status-badge '.$statusClass.'">'.strtoupper($t['status']).'</span></td>
                                            <td style="text-align:right;">
                                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                                    '. ($t['status'] == 'pendente' ? 
                                                        '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'pagar\')" class="btn-action-icon btn-confirm"><i class="fa-solid fa-check"></i></button>' : 
                                                        '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'estornar\')" class="btn-action-icon btn-undo"><i class="fa-solid fa-rotate-left"></i></button>'
                                                    ) .'
                                                    <button onclick="atualizarStatusFinanceiro('.$t['id'].', \'excluir\')" class="btn-action-icon btn-delete"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" style="text-align:center; padding: 20px;">Nenhum lançamento no caixa do Admin.</td></tr>';
                                }
                                echo '
                            </tbody>

                            <tbody id="tbody-global" style="display: none;">
                                ';
                                if (count($lista_global) > 0) {
                                    foreach ($lista_global as $t) {
                                        $statusClass = ($t['status'] == 'pago') ? 'pago' : 'pendente';
                                        $dataShow = ($t['status'] == 'pago' && !empty($t['data_pagamento'])) ? $t['data_pagamento'] : $t['data_vencimento'];
                                        $dataExibicao = date('d/m/y', strtotime($dataShow));
                                        $foto = !empty($t['foto_pagador']) ? $t['foto_pagador'] : 'assets/img/user-default.png';
                                        $nomePagador = $t['nome_pagador'] ?: 'Desconhecido';
                                        $tipoConta = ($t['tipo_conta'] == 'coach' || $t['tipo_conta'] == 'personal') ? '<span style="color: var(--color-coach); font-size:0.7rem; font-weight:bold;">COACH</span>' : '<span style="color:#888; font-size:0.7rem;">ALUNO</span>';
                                        
                                        $infoCoach = '';
                                        if(!empty($t['nome_coach'])) {
                                            $infoCoach = '<br><span style="color:#666; font-size:0.7rem;">Coach: '.$t['nome_coach'].'</span>';
                                        }

                                        echo '<tr>
                                            <td><div class="user-cell"><img src="'.$foto.'" class="table-avatar"><div><span>'.$nomePagador.'</span>'.$infoCoach.'</div></div></td>
                                            <td>'.$tipoConta.'</td>
                                            <td>'.$t['descricao'].'</td>
                                            <td>'.$dataExibicao.'</td>
                                            <td><strong>R$ '.number_format($t['valor'], 2, ',', '.').'</strong></td>
                                            <td><span class="status-badge '.$statusClass.'">'.strtoupper($t['status']).'</span></td>
                                            <td style="text-align:right;">
                                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                                    '. ($t['status'] == 'pendente' ? 
                                                        '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'pagar\')" class="btn-action-icon btn-confirm"><i class="fa-solid fa-check"></i></button>' : 
                                                        '<button onclick="atualizarStatusFinanceiro('.$t['id'].', \'estornar\')" class="btn-action-icon btn-undo"><i class="fa-solid fa-rotate-left"></i></button>'
                                                    ) .'
                                                    <button onclick="atualizarStatusFinanceiro('.$t['id'].', \'excluir\')" class="btn-action-icon btn-delete"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" style="text-align:center; padding: 20px;">Nenhum lançamento no sistema.</td></tr>';
                                }
                                echo '
                            </tbody>

                        </table>
                    </div>
                </div>
            </section>
            
            <div id="modalLancamentoAdm" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="overflow:visible;"> 
                    <button class="modal-close" onclick="document.getElementById(\'modalLancamentoAdm\').style.display=\'none\'">&times;</button>
                    
                    <h3 class="section-title" style="color: var(--gold); margin-bottom: 20px; text-align: center;">
                        <i class="fa-solid fa-cash-register"></i> Lançamento Admin
                    </h3>
                    
                    <form id="formLancamentoFinanceiro">
                        <div style="margin-bottom:15px; position:relative;">
                            <label class="input-label">Usuário (Aluno ou Personal)</label>
                            
                            <input type="text" id="busca-user-adm" class="admin-input" placeholder="Buscar..." autocomplete="off" onkeyup="filtrarUsuariosAdm()">
                            <input type="hidden" name="usuario_id" id="id-user-adm" required>
                            
                            <div id="dropdown-users-adm" class="custom-dropdown-list" style="display:none; position:absolute; width:100%; max-height:200px; overflow-y:auto; background:#222; border:1px solid #444; z-index:1000;">';
                                
                                // OTIMIZAÇÃO: Limita a 300 usuários para não travar. Em produção grande, use AJAX no input.
                                foreach($usuarios_list as $u) {
                                    $ft = !empty($u['foto']) ? $u['foto'] : 'assets/img/user-default.png';
                                    $nomeSafe = addslashes($u['nome']);
                                    $tipoLabel = strtoupper($u['tipo_conta']);
                                    $corTipo = ($u['tipo_conta'] == 'coach' || $u['tipo_conta'] == 'personal') ? 'var(--color-coach)' : '#888';
                                    echo '<div class="dropdown-item" style="padding:10px; border-bottom:1px solid #333; cursor:pointer; display:flex; align-items:center; gap:10px;" onclick="selecionarUsuarioAdm('.$u['id'].', \''.$nomeSafe.'\')">
                                            <img src="'.$ft.'" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                                            <div><span style="display:block; color:#fff;">'.$u['nome'].'</span><span style="display:block; font-size:0.7rem; color:'.$corTipo.';">'.$tipoLabel.'</span></div>
                                          </div>';
                                }
        echo '              </div>
                        </div>
                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex:2;"><label class="input-label">Descrição</label><input type="text" name="descricao" class="admin-input" placeholder="Ex: Taxa de Adesão" required></div>
                            <div style="flex:1;"><label class="input-label">Valor (R$)</label><input type="number" name="valor" step="0.01" class="admin-input" placeholder="0.00" required></div>
                        </div>
                        <div class="row-flex" style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <div style="flex:1;"><label class="input-label">Vencimento</label><input type="date" name="data_vencimento" class="admin-input" required value="'.date('Y-m-d').'"></div>
                            <div style="flex:1;"><label class="input-label">Status</label><select name="status" class="admin-input"><option value="pago">Pago</option><option value="pendente">Pendente</option></select></div>
                        </div>
                        <button type="submit" class="btn-gold" style="width:100%; padding:15px; background: var(--gold); border:none;">REGISTRAR (ADMIN)</button>
                    </form>
                </div>
            </div>
        ';

        break;

    
    case 'treino_painel':
        require_once '../config/db_connect.php';
        require_once '../helpers/tradutor_treino.php'; // Chama o helper de tradução
        
        $treino_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $idioma_sessao = $_SESSION['pref_idioma'] ?? 'pt'; // Preferência de idioma do Admin/Coach

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
            // A. Busca TODOS os exercícios dessas divisões
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

                // B. Busca TODAS as séries desses exercícios
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

        // 3. BUSCAR PERIODIZAÇÃO (Mantido igual)
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
                    <button class="btn-action-icon" onclick="carregarConteudo(\'treinos_editor\')"><i class="fa-solid fa-arrow-left"></i></button>
                    <div>
                        <h2 style="color:#fff; font-family:Orbitron; margin:0;">'.$treino['nome'].'</h2>
                        <p style="color:#888; font-size:0.9rem;">Aluno: <strong style="color:var(--gold);">'.$treino['nome_aluno'].'</strong> • '.strtoupper($treino['nivel_plano']).'</p>
                    </div>
                </div>';
                
                // TIMELINE
                if (!empty($microciclos)) {
                    echo '<h3 class="section-title" style="font-size:1rem; margin-bottom:10px;">PERIODIZAÇÃO (12 SEMANAS)</h3>
                          <div class="timeline-wrapper">';
                    foreach ($microciclos as $m) {
                        $inicio = date('d/m', strtotime($m['data_inicio_semana']));
                        $fim = date('d/m', strtotime($m['data_fim_semana']));
                        $hoje = date('Y-m-d');
                        $activeClass = ($hoje >= $m['data_inicio_semana'] && $hoje <= $m['data_fim_semana']) ? 'active' : '';
                        $m_json = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');

                        echo '<div class="micro-card '.$activeClass.'" onclick=\'openMicroModal('.$m_json.', '.$treino_id.')\'>
                                <span class="micro-week">SEMANA '.$m['semana_numero'].' <i class="fa-solid fa-pen" style="font-size:0.6rem; margin-left:5px;"></i></span>
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
                        
                        // Pega exercícios da memória (Otimização)
                        $exercicios_raw = $exercicios_por_divisao[$div['id']] ?? [];

                        // AGRUPAMENTO (Bi-set / Tri-set)
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
                                            // Pega as séries da memória
                                            $series = $series_por_exercicio[$ex['id']] ?? [];
                                            
                                            // Prepara JSON para edição
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
                                                            
                                                            // --- AQUI ACONTECE A TRADUÇÃO DAS SÉRIES ---
                                                            $label = traduzirTermo($s['categoria'] ?? 'normal', $idioma_sessao);
                                                            $extra = $reps ? "(".$reps.")" : "";

                                                            if ($tecnica === 'dropset') {
                                                                $style = 'background:rgba(255, 64, 129, 0.1); color:#ff4081; border:1px solid rgba(255, 64, 129, 0.3);';
                                                                $label = traduzirTermo('dropset', $idioma_sessao);
                                                                $extra = "<small style='opacity:0.8; font-size:0.85em; margin-left:2px;'>({$valor} drops)</small>";
                                                            } elseif ($tecnica === 'restpause') {
                                                                $style = 'background:rgba(0, 188, 212, 0.1); color:#00bcd4; border:1px solid rgba(0, 188, 212, 0.3);';
                                                                $label = traduzirTermo('restpause', $idioma_sessao);
                                                                $extra = "<small style='opacity:0.8; font-size:0.85em; margin-left:2px;'>({$valor}s)</small>";
                                                            } elseif ($tecnica === 'clusterset') {
                                                                $style = 'background:rgba(255, 145, 0, 0.1); color:#ff9100; border:1px solid rgba(255, 145, 0, 0.3);';
                                                                $label = traduzirTermo('clusterset', $idioma_sessao);
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

                                        if ($bloco['tipo'] === 'grupo') echo '</div>'; // Fecha wrapper
                                    }
                                }
                        echo '</div></div>';
                        $firstContent = false;
                    }

        echo '  </div>
            </section>';
            
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
                                <input type="text" id="inp_nome" class="admin-input" placeholder="Ex: Supino Reto" required>
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Mecânica</label>
                                <select id="inp_mecanica" class="admin-input">
                                    <option value="livre">Livre / Máquina</option>
                                    <option value="composto">Composto</option>
                                    <option value="isolador">Isolador</option>
                                </select>
                            </div>
                        </div>

                        <div class="row-flex" style="display:flex; gap:15px; margin-bottom:15px;">
                            <div style="flex:1;">
                                <label class="input-label">Vídeo URL</label>
                                <input type="text" id="inp_video" class="admin-input" placeholder="https://youtube...">
                            </div>
                            <div style="flex:1;">
                                <label class="input-label">Observação</label>
                                <input type="text" id="inp_obs" class="admin-input" placeholder="Dica técnica...">
                            </div>
                        </div>

                        <hr style="border:0; border-top:1px solid #333; margin:20px 0;">

                        <h4 style="color:#fff; font-size:0.9rem; margin-bottom:10px;">Séries</h4>
                        <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px;">
                            <div class="set-inputs-row" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                                <div style="flex:0 0 60px;"><label class="input-label" style="font-size:0.7rem;">Qtd</label><input type="number" id="set_qtd" class="admin-input" value="1" style="padding:8px;"></div>
                                <div style="flex:1; min-width:140px;">
                                    <label class="input-label" style="font-size:0.7rem;">Tipo</label>
                                    <select id="set_tipo" class="admin-input" style="padding:8px;" onchange="toggleTechniqueFields()">
                                        <option value="work">'.traduzirTermo('work', $idioma_sessao).'</option>
                                        <option value="warmup">'.traduzirTermo('warmup', $idioma_sessao).'</option>
                                        <option value="feeder">'.traduzirTermo('feeder', $idioma_sessao).'</option>
                                        <option value="top">'.traduzirTermo('topset', $idioma_sessao).'</option>
                                        <option value="backoff">'.traduzirTermo('backoff', $idioma_sessao).'</option>
                                        <option value="falha">Falha</option>
                                        <option value="dropset" style="color:#ff4d4d;">'.traduzirTermo('dropset', $idioma_sessao).'</option>
                                        <option value="restpause" style="color:#00e676;">'.traduzirTermo('restpause', $idioma_sessao).'</option>
                                        <option value="clusterset" style="color:#00bfff;">'.traduzirTermo('clusterset', $idioma_sessao).'</option>
                                    </select>
                                </div>
                                <div style="flex:0 0 70px;"><label class="input-label" style="font-size:0.7rem;">Reps</label><input type="text" id="set_reps" class="admin-input" placeholder="10" style="padding:8px;"></div>
                                <div style="flex:0 0 70px;"><label class="input-label" style="font-size:0.7rem;">Desc</label><input type="text" id="set_desc" class="admin-input" placeholder="60s" style="padding:8px;"></div>
                                <button type="button" class="btn-gold" onclick="addSetToList()" style="padding:8px 15px; height:38px;"><i class="fa-solid fa-plus"></i></button>
                            </div>

                            <div id="div_special_inputs" style="margin-top:10px;">
                                <div id="div_drops" style="display:none; padding:10px; border:1px dashed #ff4d4d; border-radius:4px;"><label style="color:#ff4d4d;">Qtd Drops:</label> <input type="number" id="set_drops_qtd" class="admin-input" style="width:60px;" placeholder="2"></div>
                                <div id="div_pause" style="display:none; padding:10px; border:1px dashed #00e676; border-radius:4px;"><label style="color:#00e676;">Pausa (s):</label> <input type="number" id="set_rest_time" class="admin-input" style="width:60px;" placeholder="15"></div>
                                <div id="div_cluster" style="display:none; padding:10px; border:1px dashed #00bfff; border-radius:4px;">
                                    <label style="color:#00bfff;">Blocos:</label> <input type="number" id="set_cluster_blocos" class="admin-input" style="width:50px;"> X <input type="text" id="set_cluster_reps" class="admin-input" style="width:50px;" placeholder="reps"> <input type="number" id="set_cluster_rest" class="admin-input" style="width:60px;" placeholder="s">
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
                    <h3 class="section-title" style="color:var(--gold); margin:bottom:20px;">
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

    // Garante que não dê erro se o usuário ainda não tiver a preferência salva
    $pref_idioma = isset($user['pref_idioma']) ? $user['pref_idioma'] : 'pt';

    // --- LÓGICA DE CORES BASEADA EM 'tipo_conta' ---
    $tipo = $user['tipo_conta']; // Coluna correta do banco

    if ($tipo === 'coach') {
        $corTema = 'var(--color-coach)'; 
        $sombraBtn = 'rgba(0, 230, 118, 0.3)'; // Sombra Verde
    } else {
        // Padrão para admin
        $corTema = 'var(--color-admin)';
        $sombraBtn = 'rgba(255, 66, 66, 0.3)'; // Sombra Vermelha
    }

    echo '
        <section id="perfil-section">
            <header class="dash-header">
                <h1>MEU <span class="highlight-text" style="color: '.$corTema.';">PERFIL</span></h1>
            </header>

            <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
                <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data" class="form-profile">
                    
                    <div class="profile-photo-section">
                        <div class="photo-wrapper">
                            <img src="'.$foto.'" alt="Foto Perfil" id="preview-img" style="border: 3px solid '.$corTema.';">
                            
                            <label for="foto-upload" class="upload-btn-float" style="background: '.$corTema.'; color: #fff;">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" name="foto" id="foto-upload" style="display: none;" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </div>

                    <div class="input-grid">
                        <div>
                            <label class="input-label">Nome de Exibição</label>
                            <input type="text" name="nome" value="'.$user['nome'].'" class="input-field" required>
                        </div>
                        <div>
                            <label class="input-label">Telefone</label>
                            <input type="text" name="telefone" value="'.$user['telefone'].'" class="input-field">
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <label class="input-label">E-mail de Acesso</label>
                        <input type="email" name="email" value="'.$user['email'].'" class="input-field" required>
                    </div>

                    <hr class="form-divider">

                    <div>
                        <h3 class="password-section-title" style="color: '.$corTema.';">Segurança</h3>
                        <p class="password-section-desc">Preencha apenas se quiser alterar sua senha de acesso.</p>
                    </div>

                    <div class="input-grid">
                        <div>
                            <label class="input-label">Nova Senha</label>
                            <input type="password" name="nova_senha" class="input-field" placeholder="********">
                        </div>
                        <div>
                            <label class="input-label">Confirmar Senha</label>
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

                    <div style="text-align: center; margin-top: 30px; margin-bottom: 10px;">
                        <button type="submit" class="btn-gold" style="background: '.$corTema.'; color: #fff; border: none; box-shadow: 0 4px 15px '.$sombraBtn.';">
                            <i class="fa-solid fa-floppy-disk" style="margin-right: 8px;"></i> SALVAR ALTERAÇÕES
                        </button>
                    </div>
                </form>
            </div>
        </section>
    ';
    break;

    case 'dieta_editor':
        require_once '../config/db_connect.php';
        $aluno_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // --- CORREÇÃO: DETECTA QUEM ESTÁ VENDO (ADMIN OU COACH) ---
        // Se for personal/coach, define a origem como 'coach'. Senão, 'admin'.
        $tipo_logado = $_SESSION['tipo_conta'] ?? 'admin';
        $origem_form = ($tipo_logado === 'coach' || $tipo_logado === 'personal') ? 'coach' : 'admin';

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
                        <input type="hidden" name="origem" value="'.$origem_form.'">
                        
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
                            <a href="actions/dieta_save.php?acao=excluir_dieta&id='.$dieta['id'].'&aluno_id='.$aluno_id.'&origem='.$origem_form.'" class="btn-action-icon btn-delete" onclick="return confirm(\'Apagar toda a dieta?\')"><i class="fa-solid fa-trash"></i></a>
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
                                <a href="actions/dieta_save.php?acao=excluir_refeicao&id='.$ref['id'].'&aluno_id='.$aluno_id.'&origem='.$origem_form.'" class="btn-action-icon btn-delete"><i class="fa-solid fa-trash"></i></a>
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
                                        <a href="actions/dieta_save.php?acao=excluir_item&id='.$it['id'].'&aluno_id='.$aluno_id.'&origem='.$origem_form.'" style="color:#666; margin-left:10px;"><i class="fa-solid fa-xmark"></i></a>
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
                    <input type="hidden" name="origem" value="'.$origem_form.'">
                    
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
                    <input type="hidden" name="origem" value="'.$origem_form.'">
                    
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
                    <input type="hidden" name="origem" value="'.$origem_form.'">
                    
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

    case 'mensagens':
        require_once '../config/db_connect.php';

        // Marca como lida se vier ID na URL
        if(isset($_GET['marcar_lida'])) {
            $pdo->prepare("UPDATE mensagens_contato SET lida = 1 WHERE id = ?")->execute([$_GET['marcar_lida']]);
        }
        
        // Deleta se vier ID na URL
        if(isset($_GET['excluir_msg'])) {
            $pdo->prepare("DELETE FROM mensagens_contato WHERE id = ?")->execute([$_GET['excluir_msg']]);
        }

        // Busca Mensagens
        $msgs = $pdo->query("SELECT * FROM mensagens_contato ORDER BY data_envio DESC")->fetchAll(PDO::FETCH_ASSOC);

        echo '<section id="admin-mensagens">
                <header class="dash-header">
                    <h1>CAIXA DE <span class="highlight-text">MENSAGENS</span></h1>
                    <p class="text-desc">Contatos recebidos pelo site.</p>
                </header>

                <div class="glass-card mt-large">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>DATA</th>
                                    <th>NOME</th>
                                    <th>CONTATO</th>
                                    <th>MENSAGEM</th>
                                    <th style="text-align:right;">AÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                            if (empty($msgs)) {
                                echo '<tr><td colspan="5" style="text-align:center; padding:30px; color:#666;">Nenhuma mensagem recebida.</td></tr>';
                            } else {
                                foreach ($msgs as $m) {
                                    $data = date('d/m/y H:i', strtotime($m['data_envio']));
                                    $statusClass = $m['lida'] ? 'opacity:0.5;' : 'font-weight:bold; color:#fff;';
                                    $iconLida = $m['lida'] ? '<i class="fa-regular fa-envelope-open"></i>' : '<i class="fa-solid fa-envelope" style="color:var(--gold);"></i>';
                                    
                                    echo '<tr style="'.$statusClass.'">
                                            <td style="font-size:0.8rem; color:#888;">'.$data.'</td>
                                            <td>'.$m['nome'].'</td>
                                            <td>
                                                <div style="font-size:0.8rem;">
                                                    <i class="fa-solid fa-envelope"></i> '.$m['email'].'<br>
                                                    <i class="fa-brands fa-whatsapp"></i> '.$m['telefone'].'
                                                </div>
                                            </td>
                                            <td style="max-width:300px; white-space:normal;">'.nl2br($m['mensagem']).'</td>
                                            <td style="text-align:right;">
                                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                                    <button onclick="carregarConteudo(\'mensagens&marcar_lida='.$m['id'].'\')" class="btn-action-icon" title="Marcar como lida">'.$iconLida.'</button>
                                                    
                                                    <button onclick="if(confirm(\'Excluir mensagem?\')) carregarConteudo(\'mensagens&excluir_msg='.$m['id'].'\')" class="btn-action-icon btn-delete"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </td>
                                          </tr>';
                                }
                            }
        echo '              </tbody>
                        </table>
                    </div>
                </div>
              </section>';
        break;

    case 'gerar_pdf':
        require_once '../config/db_connect.php';
        
        // Verifica se um aluno já foi selecionado
        $aluno_id = isset($_GET['aluno_id']) ? intval($_GET['aluno_id']) : 0;

        // =========================================================================
        // PASSO 1: TELA DE SELEÇÃO DE ALUNO (Caso o admin ainda não tenha escolhido)
        // =========================================================================
        if ($aluno_id === 0) {
            $stmt = $pdo->query("SELECT id, nome, email, foto, plano_atual FROM usuarios WHERE tipo_conta = 'atleta' ORDER BY nome ASC");
            $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<section id="selecionar-aluno-pdf" class="fade-in">
                    <header class="dash-header-clean">
                        <div>
                            <h1 class="greeting-clean">Selecionar <span class="text-gold">Aluno</span></h1>
                            <p class="date-clean">Escolha de qual aluno deseja gerar os relatórios em PDF</p>
                        </div>
                    </header>
                    
                    <div class="alunos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 20px;">';
            
            if(empty($atletas)) {
                echo '<p style="color:#888;">Nenhum aluno encontrado.</p>';
            } else {
                foreach($atletas as $at) {
                    $foto = $at['foto'] ? $at['foto'] : 'assets/img/user-default.png';
                    echo '<div class="aluno-card" onclick="abrirGeradorPdfAluno('.$at['id'].')" style="background: #161616; border: 1px solid #333; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: 0.2s;">
                            <img src="'.$foto.'" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                            <div>
                                <h4 style="margin: 0; color: #fff; font-size: 1rem;">'.$at['nome'].'</h4>
                                <span style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase;">PLANO '.$at['plano_atual'].'</span>
                            </div>
                          </div>';
                }
            }
            
            echo '  </div>
                    <script>
                        function abrirGeradorPdfAluno(id) {
                            fetch(`ajax/get_admin_conteudo.php?pagina=gerar_pdf&aluno_id=${id}`)
                                .then(res => res.text())
                                .then(html => {
                                    document.getElementById("selecionar-aluno-pdf").parentElement.innerHTML = html;
                                })
                                .catch(err => console.error("Erro ao carregar:", err));
                        }
                    </script>
                  </section>';
            break; 
        }

        // =========================================================================
        // PASSO 2: TELA DO GERADOR DE PDF
        // =========================================================================

        // AQUI ESTÁ A MÁGICA: Puxamos a pref_idioma do aluno diretamente do banco
        $stmt_user = $pdo->prepare("SELECT nome, plano_atual, pref_idioma FROM usuarios WHERE id = ?");
        $stmt_user->execute([$aluno_id]);
        $aluno_selecionado = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$aluno_selecionado) {
            echo '<section class="empty-state"><h2>Aluno não encontrado</h2></section>';
            break;
        }

        $nome_aluno = $aluno_selecionado['nome'];
        // Se a coluna estiver vazia, o padrão é pt
        $idioma_do_aluno_para_pdf = $aluno_selecionado['pref_idioma'] ?? 'pt';

        // 1. Busca o Plano Ativo
        $stmt = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY criado_em DESC LIMIT 1");
        $stmt->execute([$aluno_id]);
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plano) {
            echo '<section class="empty-state">
                    <h2>Sem plano ativo</h2>
                    <p style="color:#888;">Este aluno ainda não possui treinos cadastrados.</p>
                    <button class="btn-gold-outline" onclick="carregarConteudo(\'gerar_pdf\')" style="margin-top:20px;">Voltar</button>
                  </section>';
            break;
        }

        // -------------------------------------------------------------
        // BLOCO DE PERIODIZAÇÃO
        // -------------------------------------------------------------
        $micros_para_pdf = [];
        $stmt_per = $pdo->prepare("SELECT id FROM periodizacoes WHERE treino_id = ?");
        $stmt_per->execute([$plano['id']]);
        $per = $stmt_per->fetch(PDO::FETCH_ASSOC);

        if ($per) {
            $stmt_micro = $pdo->prepare("SELECT * FROM microciclos WHERE periodizacao_id = ? ORDER BY semana_numero ASC");
            $stmt_micro->execute([$per['id']]);
            $micros_para_pdf = $stmt_micro->fetchAll(PDO::FETCH_ASSOC);
        }
        $json_micros = htmlspecialchars(json_encode($micros_para_pdf), ENT_QUOTES, 'UTF-8');

        // -------------------------------------------------------------
        // BLOCO DE AVALIAÇÕES FÍSICAS
        // -------------------------------------------------------------
        $stmt_av = $pdo->prepare("SELECT * FROM avaliacoes WHERE aluno_id = ? ORDER BY data_avaliacao DESC LIMIT 5");
        $stmt_av->execute([$aluno_id]);
        $avaliacoes_lista = $stmt_av->fetchAll(PDO::FETCH_ASSOC);
        $json_avaliacoes = htmlspecialchars(json_encode($avaliacoes_lista), ENT_QUOTES, 'UTF-8');
        // -------------------------------------------------------------

        // 2. Busca Divisões e Exercícios
        $stmt_div = $pdo->prepare("SELECT * FROM treino_divisoes WHERE treino_id = ? ORDER BY letra ASC");
        $stmt_div->execute([$plano['id']]);
        $divisoes = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

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

        // RENDERIZAÇÃO DO PAINEL DE PDF
        echo '<section id="area-relatorios" class="fade-in">
                
                <header class="dash-header-clean" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h1 class="greeting-clean">Gerador de <span class="text-gold">Fichas</span></h1>
                        <p class="date-clean">Aluno: <strong style="color:#fff;">'.$nome_aluno.'</strong> | Plano: <strong>'.$plano['nome'].'</strong></p>
                    </div>
                    <button class="btn-outline btn-gold" onclick="carregarConteudo(\'gerar_pdf\')" style="padding: 8px 15px; border-radius: 8px; font-size: 0.8rem;">
                        <i class="fa-solid fa-arrow-left"></i> Trocar Aluno
                    </button>
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
                            <input type="text" id="pdf_aluno_nome" class="modal-input" value="'.$nome_aluno.'">
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
                            
                            <input type="hidden" name="pref_idioma_pdf" value="'.$idioma_do_aluno_para_pdf.'">

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
                                <span id="render-plano-nome">'.$plano['nome'].'</span>
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
                    <div class="pdf-sheet landscape" style="padding: 10px; box-sizing: border-box; display: flex; flex-direction: column;">
                        
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

                        <div id="pdf-container-micros"></div>

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
                        <div id="pdf-container-avaliacao" style="padding: 20px;"></div>
                        <div class="sheet-footer">
                            <p>Metodologia <strong>RYAN COACH</strong></p>
                        </div>
                    </div>
                </div>

              </section>';
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
                        <img src="'.$foto.'" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-admin);">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #fff; font-size: 1.1rem;">'.$admin['nome'].'</h3>
                            <span class="usuario-level" style="color: var(--color-admin); background: var(--bg-admin);">SUPER ADMIN</span>
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

                    <div class="menu-card" onclick="carregarConteudo(\'gerar_pdf\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-file-pdf" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Gerar PDF</span>
                    </div>

                    <div class="menu-card" onclick="carregarConteudo(\'mensagens\')" style="background: #161616; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
                        <i class="fa-solid fa-envelope" style="font-size: 1.5rem; color: #fff; margin-bottom: 10px; display: block;"></i>
                        <span style="color: #ccc; font-size: 0.9rem;">Mensagens Contato</span>
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
                            <i class="fa-solid fa-right-from-bracket" style="color: var(--color-admin);"></i>
                            <span style="color: var(--color-admin);">Sair do Sistema</span>
                        </div>
                    </div>

                </div>

              </section>';
        break;

    default:
        echo '<section><h1>Página não encontrada</h1></section>';
        break;
}
$pdo = null;
?>