<?php
function uploadEComprimirImagem($arquivo, $destino_pasta, $novo_nome_base) {
    // 1. Configurações
    $qualidade = 80; // 0 a 100 (80 é excelente custo-benefício)
    $max_largura = 1200; // Redimensiona se for maior que isso (HD)

    // 2. Verifica tipo
    $info = getimagesize($arquivo['tmp_name']);
    if ($info === false) return false;

    $tipo = $info[2]; // 2 = JPG, 3 = PNG, etc.
    
    // 3. Carrega a imagem na memória
    switch ($tipo) {
        case IMAGETYPE_JPEG: 
            $imagem = imagecreatefromjpeg($arquivo['tmp_name']); 
            break;
        case IMAGETYPE_PNG: 
            $imagem = imagecreatefrompng($arquivo['tmp_name']); 
            // Converte transparência para branco (JPG não tem fundo transparente)
            $bg = imagecreatetruecolor(imagesx($imagem), imagesy($imagem));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagealphablending($bg, true);
            imagecopy($bg, $imagem, 0, 0, 0, 0, imagesx($imagem), imagesy($imagem));
            imagedestroy($imagem);
            $imagem = $bg;
            break;
        case IMAGETYPE_WEBP:
            $imagem = imagecreatefromwebp($arquivo['tmp_name']);
            break;
        default: return false; // Formato não aceito
    }

    // 4. Redimensionar se necessário
    $largura_original = imagesx($imagem);
    $altura_original = imagesy($imagem);

    if ($largura_original > $max_largura) {
        $razao = $max_largura / $largura_original;
        $nova_altura = $altura_original * $razao;
        
        $nova_imagem = imagecreatetruecolor($max_largura, $nova_altura);
        imagecopyresampled($nova_imagem, $imagem, 0, 0, 0, 0, $max_largura, $nova_altura, $largura_original, $altura_original);
        
        imagedestroy($imagem); // Limpa a memória da grande
        $imagem = $nova_imagem;
    }

    // 5. Salvar como JPG Comprimido
    $nome_final = $novo_nome_base . '.jpg';
    $caminho_completo = $destino_pasta . '/' . $nome_final;
    
    // Cria pasta se não existir
    if (!is_dir($destino_pasta)) mkdir($destino_pasta, 0777, true);

    imagejpeg($imagem, $caminho_completo, $qualidade);
    imagedestroy($imagem); // Limpa memória final

    return $caminho_completo; // Retorna o caminho para salvar no banco
}
?>