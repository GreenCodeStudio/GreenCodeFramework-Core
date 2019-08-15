let CACHE_NAME = 'C1';
let CACHE_OFFLINE_NAME = 'CO1';
let cachePromise = caches.open(CACHE_NAME);
let cacheOfflinePromise = caches.open(CACHE_OFFLINE_NAME);
const debug = false;
self.addEventListener('fetch', function (event) {
    // console.log('sw fetch', event.request);

    event.respondWith((async () => {
        if (debug)
            return fetch(event.request);
        if (event.request.headers.get('x-json')) {
            let cache = await cacheOfflinePromise;
            if (navigator.onLine === true) {
                try {
                    let response = await fetch(event.request.clone());
                    return response;
                } catch (ex) {
                    var cacheResult = await cache.match(event.request);
                    return cacheResult;
                }
            } else {
                var cacheResult = await cache.match(event.request);
                return cacheResult;
            }
        } else {
            let cache = await cachePromise;
            if (navigator.onLine === true) {
                var cacheResult = await cache.match(event.request);
                if (cacheResult) {
                    console.log('sw cache', cacheResult);
                    return cacheResult;
                }
                try {
                    let response = await fetch(event.request.clone());
                    //console.log('sw fetch', response);
                    if (response.headers.get('x-sw-cache') == 1)
                        cache.put(event.request, response.clone());
                    return response;
                } catch (ex) {
                    var cacheResult = await cache.match(event.request);
                    if (cacheResult)
                        return cacheResult;
                    return await cache.match('/cache/offline');
                }
            } else {
                var cacheResult = await cache.match(event.request);
                if (cacheResult)
                    return cacheResult;
                let cachenffline = await caches.open(CACHE_OFFLINE_NAME);
                return await cache.match('/cache/offline');
            }
        }
    })());

});
self.addEventListener('message', function (event) {
    if (event.data == 'clear')
        clear();
    else if (event.data == 'installOffline')
        installOffline();
});
self.addEventListener('install', function (event) {

});

/**
 * Nowa wersja
 */
async function clear() {
    caches.delete(CACHE_NAME);
    cachePromise = caches.open(CACHE_NAME);

}

async function installOffline() {
    var response = await fetch('/ajax/cache/list')
    var list = await response.json();
    let cache = await cachePromise;
    for (let filePath of list.data.normal) {
        cache.match(filePath).then(matches => {
            if (!matches) {
                cache.add(filePath);
            }
        });
    }

    let cacheoffline = await caches.open(CACHE_OFFLINE_NAME);
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