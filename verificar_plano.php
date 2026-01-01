<?php
// includes/verificar_plano.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Se for a página de bloqueio ou logout, não faz a verificação para evitar loop infinito
$pagina_atual = basename($_SERVER['PHP_SELF']);
if ($pagina_atual == 'bloqueado.php' || $pagina_atual == 'logout.php') {
    return;
}

require_once __DIR__ . '/config/db_connect.php';

$user_id = $_SESSION['user_id'];

// 2. Busca os dados atualizados do usuário no banco
// É importante buscar do banco e não da sessão, pois o admin pode ter renovado o plano agora
$stmt = $pdo->prepare("SELECT tipo_conta, data_expiracao_plano FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // ADMIN nunca é bloqueado
    if ($user['tipo_conta'] === 'admin') {
        return; 
    }

    $data_expiracao = $user['data_expiracao_plano'];
    $hoje = date('Y-m-d');

    // 3. Lógica de Bloqueio
    // Se for NULL OU se a data for menor que hoje (Vencido)
    if (is_null($data_expiracao) || $data_expiracao < $hoje) {
        
        // Redireciona para a página de bloqueio
        header("Location: bloqueado.php");
        exit;
    }
} else {
    // Usuário não encontrado no banco (foi deletado?)
    session_destroy();
    header("Location: login.php");
    exit;
}
?>