<?php
// --- AJUSTE DE FUSO HORÁRIO ---
date_default_timezone_set('America/Recife');

// --- IMPORTANTE: Carrega a função de segurança ---
require_once __DIR__ . '/../helpers/security.php';

// 1. DEFINIÇÃO DE VARIÁVEIS (PRIMEIRO PASSO)
$whitelist = array('127.0.0.1', '::1', 'localhost');

if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    // --- AMBIENTE LOCAL (DEV) ---
    $host = 'localhost';
    $dbname = 'ryancoach_saas';
    $username = 'root';
    $password = ''; 
    $is_dev = true; 
} else {
    // --- AMBIENTE PRODUÇÃO (ONLINE) ---
    $host = 'localhost'; 
    $dbname = 'u231438946_ryancoach_saas'; 
    $username = 'u231438946_ryan_admin';
    $password = '@Ry206443218';
    $is_dev = false;
}

// 2. CONEXÃO COM OTIMIZAÇÃO (SEGUNDO PASSO)
try {
    // String de Conexão (DSN)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    // Opções de Performance e Erro
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true // Otimização: Mantém a conexão ativa
    ];

    // Cria a conexão
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Força o fuso horário no MySQL
    $pdo->exec("SET time_zone = '-03:00';");
    
    // Em produção, silencia erros técnicos para não vazar info
    if(!$is_dev) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    }

} catch(PDOException $e) {
    // Tratamento de Erro
    if($is_dev) {
        die("Erro de Conexão Local: " . $e->getMessage());
    } else {
        // Log do erro (opcional) e mensagem amigável
        error_log($e->getMessage()); 
        die("O sistema está passando por manutenção momentânea.");
    }
}
?>