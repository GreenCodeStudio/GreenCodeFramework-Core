export function listenDropUrl(page, filter, callback) {
    page.addEventListener('dragover', e => {
        if(e.dataTransfer.types.includes('text/uri-list')) {
            e.preventDefault();
        }
    });
    page.addEventListener('drop', e => {
        let url = e.dataTransfer.getData('text/uri-list');
        let filtered=url.split(/[\n#]/).map(filter).filter(x=>x);
        if(filtered.length>0){
            callback(filtered);
            e.preventDefault();
            e.stopPropagation();
        }
    });
}

export function listenDropUrlBySourceController(page, sourceController, callback) {
    listenDropUrl(page, url => {
        let start = document.location.origin + '/' + sourceController + '/show/';
        if (url.startsWith(start)) {
            let id = +url.substring(start.length)
            return id;
        }
    }, callback)
}
