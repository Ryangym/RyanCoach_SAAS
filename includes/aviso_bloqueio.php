<?php
// includes/aviso_bloqueio.php

// Define valores padrão caso não sejam passados antes do include
$titulo_bloqueio = $titulo_bloqueio ?? 'Recurso Premium';
$texto_bloqueio  = $texto_bloqueio ?? 'Este recurso é exclusivo para alunos PRO. Faça o upgrade para desbloquear.';
$link_whats      = "https://wa.me/5535999928473?text=Oi,%20quero%20fazer%20upgrade%20para%20o%20PRO!";
?>

<section class="fade-in" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; width: 100%;">
    
    <div style="background: rgba(20, 20, 20, 0.95); padding: 40px 25px; border-radius: 20px; text-align: center; border: 1px solid #333; max-width: 400px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        
        <div style="width: 80px; height: 80px; background: rgba(255, 66, 66, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; border: 1px solid rgba(255, 66, 66, 0.3);">
            <i class="fa-solid fa-lock" style="font-size: 2rem; color: #ff4242;"></i>
        </div>

        <h2 style="font-size: 1.5rem; margin-bottom: 10px; color: #fff;"><?php echo $titulo_bloqueio; ?></h2>
        
        <p style="color: #aaa; margin-bottom: 25px; line-height: 1.5; font-size: 0.95rem;">
            <?php echo $texto_bloqueio; ?>
        </p>

        <a href="<?php echo $link_whats; ?>" target="_blank" 
           style="background: #0fa144ff; color: #fff; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 10px; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);">
            <i class="fa-brands fa-whatsapp"></i> Fazer Upgrade
        </a>

    </div>
</section>