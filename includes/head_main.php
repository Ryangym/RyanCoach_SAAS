<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="assets/img/icones/favicon3.png">

<!-- AJAX CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<!-- _____________________________FONTES____________________________________ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ms+Madi&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Copse&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Story+Script&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cherry+Cream+Soda&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Ms+Madi&family=Orbitron:wght@700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<!-- _____________________________ FIM FONTES____________________________________ -->

<!-- _____________________________ APP ____________________________________ -->
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#FFBA42"> <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Ryan Coach">
<link rel="apple-touch-icon" href="assets/img/icones/favicon.png">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('service-worker.js')
                .then(reg => console.log('App pronto! Service Worker registrado.', reg))
                .catch(err => console.log('Erro ao registrar App:', err));
        });
    }
</script>
<!-- _____________________________ FIM APP ____________________________________ -->