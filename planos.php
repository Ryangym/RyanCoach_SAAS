<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Planos</title>
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/planos.css">

    <?php include 'includes/head_main.php'; ?>

</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <section class="planos" id="planos">
        <h1 class="section-title">Nossos Planos</h1>
        <div class="planos-container">

            <div class="card" id="card">
                <h2>Plano Básico</h2>
                <img src="assets/img/planos/img.planobasico.png">
                <h3 class="price"><strong>De R$15</strong> Por R$9,90</h3>
                <ul class="textbeneficios">
                    <li class="have">Ficha de treino personalizada</li>
                    <li class="nohave">Periodização de Treino</li>
                    <li class="nohave">Avaliação Física</li>
                </ul>
                <a href="https://api.whatsapp.com/send?phone=5535999928473&text=Ol%C3%A1,%20Quero%20adquirir%20o%20Plano%20B%C3%A1sico%20do%20seu%20programa%20de%20treinamento." 
                class="contact-btn">Assinar Agora</a>
            </div>

            <div class="card" id="card">
                <h2>Plano Avançado</h2>
                <img src="assets/img/planos/img.planoavançado.png">
                <h3 class="price"><strong>De R$25</strong> Por R$19,90:</h3>
                <ul class="textbeneficios">
                    <li class="have">Ficha de treino personalizada</li>
                    <li class="have">Periodização de Treino</li>
                    <li class="have">Avaliação Física</li>
                </ul>
                <a href="https://api.whatsapp.com/send?phone=5535999928473&text=Ol%C3%A1,%20Quero%20adquirir%20o%20Plano%20Avan%C3%A7ado%20do%20seu%20programa%20de%20treinamento." 
                class="contact-btn">Assinar agora</a>
            </div>

            <div class="card card-premium" id="card">
                <h2><span class="premium">Plano Premium</span></h2>
                <img src="assets/img/planos/img.planopremium.jpg">
                <h3 class="price"><strong>De R$65</strong> Por R$49,90:</h3>
                <ul class="textbeneficios">
                    <li class="have">Ficha de treino personalizada</li>
                    <li class="have">Periodização de Treino</li>
                    <li class="have">Avaliação Física</li>
                    <li id="dieta">Planejamento e acompanhamento de Dieta</li>
                </ul>
                <a href="https://api.whatsapp.com/send?phone=5535999928473&text=Ol%C3%A1,%20Quero%20adquirir%20o%20Plano%20Premium%20do%20seu%20programa%20de%20treinamento." 
                class="contact-btn">Assinar Agora</a>
            </div>

        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>