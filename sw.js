// FinansAI Service Worker - Çevrimdışı destek ve cache yönetimi
const CACHE_ADI = 'finansai-v1';

// İlk yüklemede cache'e alınacak dosyalar
const STATIK_DOSYALAR = [
    './manifest.json',
    './assets/icons/icon.svg',
];

// Service worker kurulumu
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_ADI).then((cache) => {
            return cache.addAll(STATIK_DOSYALAR);
        })
    );
    self.skipWaiting();
});

// Eski cache temizleme
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((anahtarlar) => {
            return Promise.all(
                anahtarlar
                    .filter((anahtar) => anahtar !== CACHE_ADI)
                    .map((anahtar) => caches.delete(anahtar))
            );
        })
    );
    self.clients.claim();
});

// Ağ isteği yakalama — Network-first, cache fallback stratejisi
self.addEventListener('fetch', (event) => {
    // PHP API isteklerini cache'leme, her zaman ağdan al
    if (event.request.url.includes('process_ai.php') ||
        event.request.url.includes('ai_optimization.php') ||
        event.request.method === 'POST') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((yanit) => {
                // Başarılı yanıtı cache'e koy
                if (yanit && yanit.status === 200 && yanit.type === 'basic') {
                    const kopyaYanit = yanit.clone();
                    caches.open(CACHE_ADI).then((cache) => {
                        cache.put(event.request, kopyaYanit);
                    });
                }
                return yanit;
            })
            .catch(() => {
                // Ağ yoksa cache'den döndür
                return caches.match(event.request);
            })
    );
});
