let CACHE_NAME = 'C1';
let CACHE_VIEV_NAME = 'CO1';
let cachePromise = caches.open(CACHE_NAME);
let cacheViewPromise = caches.open(CACHE_VIEV_NAME);
let currentVersion = null;
let minimalCacheDate = new Date();
const debug = false;

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
    console.log('fileRequestOffline', event.request)
    let cache = await cachePromise;
    let cacheResult = await cache.match(event.request);
    if (cacheResult)
        return cacheResult;
    return await cache.match('/cache/offline');
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
    else if (event.data === 'installOffline')
        installOffline();
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