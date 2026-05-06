<?php
// helpers/tradutor_treino.php

function traduzirTermo($termo, $idioma = 'pt') {
    // Se o usuário preferir inglês, mostramos o termo original do banco de dados em maiúsculas
    if ($idioma === 'en') {
        return strtoupper(trim($termo));
    }

    $dicionario = [
        // Categorias Padrão
        'warmup'     => 'AQUECIMENTO',
        'feeder'     => 'PREPARAÇÃO',
        'work'       => 'SÉRIE DE TRABALHO',
        'topset'     => 'SÉRIE MÁXIMA',
        'backoff'    => 'SÉRIE DE RETORNO',
        
        // Técnicas (Pode ser que você nem queira traduzir, mas fica a opção)
        'dropset'    => 'DROP-SET',
        'restpause'  => 'REST-PAUSE',
        'clusterset' => 'CLUSTER-SET'
    ];

    $termo_clean = strtolower(trim($termo));
    
    // Se a palavra existir no dicionário, retorna a tradução em PT. Se não, retorna ela mesma maiúscula.
    return isset($dicionario[$termo_clean]) ? $dicionario[$termo_clean] : strtoupper($termo_clean);
}
?>