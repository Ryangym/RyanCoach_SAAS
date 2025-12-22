<?php
session_start(); // Inicia a sessão para poder destruí-la
session_unset(); // Limpa todas as variáveis
session_destroy(); // Destrói a sessão no servidor

// Redireciona para a Home
header("Location: ../index.php");
exit;
?>