const CACHE_NAME = 'ryan-coach-v1';
const ASSETS_TO_CACHE = [
  '/',
  'login.php',
  'assets/css/style.css',
  'assets/css/usuario.css',
  'assets/css/menu.css',
  'assets/img/icones/favicon.png',
  'assets/js/navbar.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/chart.js'
];

// 1. Instalação: Cacheia arquivos estáticos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// 2. Ativação: Limpa caches antigos se mudar a versão
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    })
  );
});

// 3. Interceptação (Fetch): Estratégia Network First (Rede Primeiro)
// Tenta pegar da internet (para ter dados frescos do banco). Se falhar (offline), tenta o cache.
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});