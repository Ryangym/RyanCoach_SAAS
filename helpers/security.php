<?php
/**
 * Função para limpar dados de entrada (Sanitização)
 * Remove tags HTML, espaços extras e converte caracteres especiais.
 */
function limparInput($data) {
    // Se for um array (ex: $_POST['exercicios']), limpa cada item dentro dele
    if (is_array($data)) {
        return array_map('limparInput', $data);
    }

    // 1. Remove espaços em branco do início e fim
    $data = trim($data);

    // 2. Remove barras invertidas adicionadas automaticamente (slashes)
    $data = stripslashes($data);

    // 3. Converte caracteres especiais em entidades HTML
    // Ex: converte <script> para &lt;script&gt; (o navegador mostra o texto mas não executa)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    return $data;
}
?>