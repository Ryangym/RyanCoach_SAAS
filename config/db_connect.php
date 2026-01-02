<?php
// --- AJUSTE DE FUSO HORÁRIO ---
// Usamos 'America/Recife' pois é o mesmo fuso de Brasília (UTC-3),
// mas não sofre com bugs de "Horário de Verão Fantasma" em servidores desatualizados.
date_default_timezone_set('America/Recife');

require_once __DIR__ . '/../helpers/security.php';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // ADICIONE ESTA LINHA:
        PDO::ATTR_PERSISTENT => true 
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    
    // ... seu comando de time_zone ...
    $pdo->exec("SET time_zone = '-03:00';");

}

// Detecta se está rodando no seu computador (Localhost)
$whitelist = array('127.0.0.1', '::1', 'localhost');

if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
    // --- CONFIGURAÇÃO DO VERTRIGO (LOCAL) ---
    $host = 'localhost';
    $dbname = 'ryancoach_saas';
    $username = 'root';
    $password = 'vertrigo'; 
    $is_dev = true; 
} else {
    // --- CONFIGURAÇÃO DA HOSTINGER (ONLINE) ---
    $host = 'localhost'; 
    $dbname = 'u231438946_ryancoach_saas'; 
    $username = 'u231438946_ryan_admin';
    $password = '@Ry206443218';
    $is_dev = false;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configura para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // --- PULO DO GATO PARA O BANCO DE DADOS ---
    // Força o MySQL a trabalhar no fuso -03:00 (Brasília), ignorando o fuso do servidor
    $pdo->exec("SET time_zone = '-03:00';");
    
    // Se NÃO for ambiente de desenvolvimento (ou seja, é produção), esconde erros técnicos
    if(!$is_dev) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    }

} catch(PDOException $e) {
    if($is_dev) {
        // Mostra o erro real se estivermos testando localmente
        die("Erro de Conexão Local: " . $e->getMessage());
    } else {
        // Mensagem genérica para o usuário final
        die("O sistema está passando por manutenção momentânea.");
    }
}
?>