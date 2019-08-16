let CACHE_NAME = 'C1';
let CACHE_VIEV_NAME = 'CO1';
let cachePromise = caches.open(CACHE_NAME);
let cacheViewPromise = caches.open(CACHE_VIEV_NAME);
let currentVersion = null;
let minimalCacheDate = new Date();
global.fetchCounter = 0;
const debug = false;
console.log('Service worker started');

async function LoadJsonView(event) {
    let cache = await cacheViewPromise;
    if (navigator.onLine === true) {
        try {
            return await fetch(event.request.clone());
        } catch (ex) {
            return await cache.match(event.request);
        }
    } else {
        return await cache.match(event.request);
    }
}

async function LoadFileOffline(event) {
    let cache = await cachePromise;
    let cacheResult = await cache.match(event.request);
    if (!cacheResult)
        cacheResult = await cache.match('/Cache/offline');
    console.log('fileRequestOffline', event.request, cacheResult)
    return cacheResult;
}

async function LoadFile(event) {
    let cache = await cachePromise;
    if (navigator.onLine === true) {
        if (currentVersion !== null) {
            let cacheResult = await cache.match(event.request);
            if (cacheResult) {
                let cachedDate = new Date(cacheResult.headers.get("date"));
                if (cacheResult.headers.get('x-sw-version') === currentVersion && cachedDate >= minimalCacheDate && new Date() - cachedDate <= 24 * 3600 * 1000) {
                    console.log('sw cache', cacheResult);
                    return cacheResult;
                }
            }
        }
        try {
            let response = await fetch(event.request.clone());
            checkCacheVersion(response.headers.get('x-sw-version'))
            //console.log('sw fetch', response);
            if (response.headers.get('x-sw-cache') === '1')
                cache.put(event.request, response.clone());
            return response;
        } catch (ex) {
            return await LoadFileOffline(event);
        }
    } else {
        return await LoadFileOffline(event);
    }
}

self.addEventListener('fetch', function (event) {
    global.fetchCounter++;
    event.respondWith((async () => {
        if (debug)
            return fetch(event.request);
        //if (event.request.cache !== "default") update();
        if (event.request.headers.get('x-json')) {
            return await LoadJsonView(event);
        } else {
            return await LoadFile(event);
        }
    })());
});
self.addEventListener('message', function (event) {
    if (event.data === 'update')
        update();
});
self.addEventListener('install', function (event) {

});

/**
 * Nowa wersja
 */
function update() {
    minimalCacheDate = new Date();
}

async function installOffline() {
    var response = await fetch('/ajax/Cache/list', {headers: {'x-js-origin': 'true'}});
    var list = await response.json();
    let cache = await cachePromise;
    for (let filePath of list.data.normal) {
        cache.match(filePath).then(matches => {
            if (!matches) {
                cache.add(filePath);
            }
        });
    }

    let cacheoffline = await caches.open(CACHE_VIEV_NAME);
    for (let filePath of list.data.json) {
        cacheoffline.match(filePath).then(matches => {
            if (!matches) {
                let req = new Request(filePath);
                req.headers.append('x-json', '1')
                cacheoffline.add(req);
            }
        });
    }
}

function checkCacheVersion(version) {
    if (version && currentVersion != version) {
        console.log('changingCache version ', currentVersion, 'to', version)
        currentVersion = version;
    }
}
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');
    console.log(`[Service Worker] Push had this data: "${event.data.text()}"`);

    const title = 'Push Codelab';
    const options = {
        body: 'Yay it works.',
        icon: 'images/icon.png',
        badge: 'images/badge.png'
    };

    event.waitUntil(self.registration.showNotification(title, options));
});
setTimeout(installOffline, 20000);