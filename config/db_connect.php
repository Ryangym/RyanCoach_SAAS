<?php
// Detecta se está rodando no seu computador (Localhost)
$whitelist = array('127.0.0.1', '::1', 'localhost');

if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    // --- CONFIGURAÇÃO DO VERTRIGO (LOCAL) ---
    $host = 'localhost';
    $dbname = 'ryan_coach_db';
    $username = 'root';
    $password = 'vertrigo'; 
    $is_dev = true; // Flag para mostrar erros na tela se precisar
} else {
    // --- CONFIGURAÇÃO DA HOSTINGER (ONLINE) ---
    $host = 'localhost'; 
    $dbname = 'u231438946_ryan_coach_bd'; 
    $username = 'u231438946_ryanborges';
    $password = '@Ry206443218';
    $is_dev = false;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Se for local, mostra erros. Se for produção, esconde para segurança.
    if(!$is_dev) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    }
} catch(PDOException $e) {
    if($is_dev) {
        die("Erro de Conexão Local: " . $e->getMessage());
    } else {
        // Em produção não mostramos o erro técnico para o usuário
        die("O sistema está passando por manutenção momentânea.");
    }
}
?>