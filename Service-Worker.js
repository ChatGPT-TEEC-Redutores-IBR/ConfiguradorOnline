const CACHE_NAME = 'versao.3.0.1';
const OFFLINE_URL = '/Paginas/PaginaErros/PaginaErros.html';
const STATIC_DESTINATIONS = ['style', 'script', 'image'];

const PRECACHE_URLS = [
    OFFLINE_URL,
    'Manifest.json',
    '/Paginas/CarregarPagina.min.js',
    '/Layout/Imagens/Logotipos/Logotipo.svg',
    '/Layout/Imagens/Logotipos/Icone.ico',
    '/Layout/Imagens/Logotipos/Cookies.svg',
    '/Layout/Imagens/Icones/linkedin.svg',
    '/Layout/Imagens/Icones/facebook.svg',
    '/Layout/Imagens/Icones/youtube.svg',
    '/Layout/Externos/Filtros.min.css',
    'favicon.ico'
];

const DB_NAME = 'pwa-sync';
const STORE_NAME = 'requests';

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = () => {
            request.result.createObjectStore(STORE_NAME, { autoIncrement: true });
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function savePostRequest(request) {
    const db = await openDatabase();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);
    const headers = [];
    for (const [key, value] of request.headers.entries()) {
        headers.push([key, value]);
    }
    const body = await request.clone().text();
    store.add({ url: request.url, method: request.method, headers, body });
    return new Promise((resolve, reject) => {
        tx.oncomplete = resolve;
        tx.onerror = () => reject(tx.error);
    });
}

async function listQueuedRequests() {
    const db = await openDatabase();
    const tx = db.transaction(STORE_NAME, 'readonly');
    const store = tx.objectStore(STORE_NAME);
    const getAll = (req) => new Promise((res, rej) => { req.onsuccess = () => res(req.result); req.onerror = () => rej(req.error); });
    const requests = await getAll(store.getAll());
    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve(requests);
        tx.onerror = () => reject(tx.error);
    });
}

async function replayQueuedRequests() {
    const db = await openDatabase();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);

    const getAll = (req) => new Promise((res, rej) => { req.onsuccess = () => res(req.result); req.onerror = () => rej(req.error); });
    const requests = await getAll(store.getAll());
    const keys = await getAll(store.getAllKeys());

    await Promise.all(requests.map((req, i) => {
        const key = keys[i];
        return fetch(req.url, { method: req.method, headers: new Headers(req.headers), body: req.body })
            .then(() => store.delete(key))
            .catch(() => { });
    }));

    return new Promise((resolve, reject) => {
        tx.oncomplete = resolve;
        tx.onerror = () => reject(tx.error);
    });
}

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('message', event => {
    if (event.data && (event.data === 'skipWaiting' || event.data.type === 'skipWaiting')) {
        self.skipWaiting();
    } else if (event.data && event.data.type === 'getQueuedRequests') {
        event.waitUntil(
            listQueuedRequests().then(requests => {
                event.ports[0]?.postMessage({ requests });
            })
        );
    }
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
            .then(() => self.clients.matchAll({ type: 'window', includeUncontrolled: true }))
            .then(clients => {
                clients.forEach(client => client.postMessage({ type: 'updated', version: CACHE_NAME }));
            })
    );
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    if (cached) {
        fetch(request).then(response => cache.put(request, response.clone())).catch(() => { });
        return cached;
    }
    try {
        const response = await fetch(request);
        if (request.method === 'GET') {
            cache.put(request, response.clone());
        } return response;
    } catch {
        return caches.match(OFFLINE_URL);
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    const network = fetch(request).then(response => {
        cache.put(request, response.clone());
        return response;
    }).catch(() => { });
    return cached || network.then(r => r).catch(() => caches.match(OFFLINE_URL));
}

async function networkFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    try {
        const response = await fetch(request);
        cache.put(request, response.clone());
        return response;
    } catch {
        const cached = await cache.match(request);
        return cached || caches.match(OFFLINE_URL);
    }
}

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    if (url.origin !== location.origin) {
        event.respondWith(fetch(request));
        return;
    }
    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request));
    } else if (request.method === 'POST') {
        event.respondWith(
            fetch(request.clone()).catch(() =>
                savePostRequest(request)
                    .then(() => {
                        if ('sync' in self.registration) {
                            self.registration.sync.register('sync-requests').catch(() => { });
                        }
                        self.clients.matchAll({ type: 'window' }).then(clients => {
                            clients.forEach(c => c.postMessage({ type: 'offline-request', url: request.url }));
                        });
                        return new Response(JSON.stringify({ offline: true }), {
                            headers: { 'Content-Type': 'application/json' }
                        });
                    })
            )
        );
    } else if (request.method === 'GET') {
        if (url.pathname.startsWith('/Paginas/Seletores/')) {
            event.respondWith(staleWhileRevalidate(request));
        } else if (['style', 'script'].includes(request.destination)) {
            event.respondWith(staleWhileRevalidate(request));
        } else if (request.destination === 'image') {
            event.respondWith(cacheFirst(request));
        } else {
            event.respondWith(networkFirst(request));
        }
    }
});

self.addEventListener('sync', event => {
    if (event.tag === 'sync-requests') {
        event.waitUntil(replayQueuedRequests());
    }
});

self.addEventListener('periodicsync', event => {
    if (event.tag === 'update-content') {
        event.waitUntil(
            fetch('/').then(r => caches.open(CACHE_NAME).then(c => c.put('/', r.clone()))).catch(() => { })
        );
    }
});