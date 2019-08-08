import "prototype-extensions";
import {modal} from '../../CommonBase/js/modal'

export const pageManager = {
    _constrollers: {},
    initPage(initInfo, page) {
        console.log(initInfo);
        if (initInfo.code == 403) {
            modal('Brak uprawnień', 'error');
        }
        if (initInfo.code == 404) {
            modal('Nie znaleziono', 'error');
        }
        if (initInfo.code == 500) {
            modal('Wystąpił błąd', 'error');
        }
        let controller = this._constrollers[initInfo.controllerName];
        if (controller) {
            if (typeof controller == 'function')
                controller = this._constrollers[controller] = controller();
            controller.then(x => {
                let obj = new x.default(page, initInfo.data);
                if (obj[initInfo.methodName]) obj[initInfo.methodName]();
            })
        }
        this._loadedEvent(page, initInfo.data, initInfo.controllerName, initInfo.methodName);
    },
    _onLoad: {},
    onLoad(callback, controller = null, method = null) {
        if (!this._onLoad[controller]) {
            this._onLoad[controller] = {};
        }
        if (!this._onLoad[controller][method]) {
            this._onLoad[controller][method] = [];
        }
        this._onLoad[controller][method].push(callback);
    },
    _loadedEvent(page, data, controller = null, method = null) {
        if (this._onLoad[controller] && this._onLoad[controller][method])
            for (let callback of this._onLoad[controller][method])
                callback(page, data);
        if (this._onLoad[controller] && this._onLoad[controller][null])
            for (let callback of this._onLoad[controller][null])
                callback(page, data);
        if (this._onLoad[null] && this._onLoad[null][null])
            for (let callback of this._onLoad[null][null])
                callback(page, data);

    },
    goto(url, options = {}) {
        return new Promise((resolve, reject) => {
            document.querySelectorAll('[data-views="main"] > .page').forEach(x => x.classList.add('removing'));
            setTimeout(() => {
                document.querySelectorAll('[data-views="main"] > .page.removing').forEach(x => x.remove());
            }, 500);
            let startDate = new Date();
            let xhr = new XMLHttpRequest();
            xhr.open('get', url);
            xhr.setRequestHeader('x-json', 1);
            xhr.onload = () => {
                let data = JSON.parse(xhr.responseText);
                if (options.ignoreHistory) {
                    if (data.needFullReload)
                        document.location.reload();
                } else {
                    if (data.needFullReload) {
                        document.location = url;
                        return;
                    }
                    history.pushState(data, '', url);
                }
                if (xhr.status == 403) {
                    modal('Brak uprawnień', 'error');
                    reject(data.error);
                } else if (xhr.status == 404) {
                    modal('Nie znaleziono', 'error');
                    reject(data.error);
                } else if (xhr.status == 500) {
                    modal('Wystąpił błąd', 'error');
                    reject(data.error);
                } else {
                    let page;
                    let viewsContainers = document.querySelectorAll('[data-views]');
                    for (let viewsContainer of viewsContainers) {
                        let viewName = viewsContainer.dataset.views;
                        if (viewName === 'main') {
                            viewsContainer = viewsContainer.addChild('div', {classList: ['page']});
                            page = viewsContainer;
                            let diffTime = new Date() - startDate;
                            if (diffTime < 200) {//dla animacji
                                viewsContainer.classList.add('stillLoading')
                                setInterval(viewsContainer.classList.remove.bind(viewsContainer.classList, 'stillLoading'), 200 - diffTime);
                            }
                        }
                        if (data.views[viewName]) {
                            viewsContainer.innerHTML = '';
                            for (let html of data.views[viewName]) {
                                viewsContainer.innerHTML += html;
                            }
                        }
                    }
                    document.querySelectorAll('.debugOutput').forEach(x => x.remove());

                    this.initPage(data.data, page);
                    this._updateBreadcrumb(data.breadcrumb);
                    resolve();
                }
                if (data.debug) {
                    let debugOutput = document.createElement('div');
                    debugOutput.className = 'debugOutput';
                    debugOutput.innerHTML = data.debug;
                    let main = document.querySelector('[data-views="main"]');
                    main.prepend(debugOutput);
                }
            };
            xhr.onerror = (ex) => {
                reject(ex);
            };
            xhr.send();
        });
    },
    _updateBreadcrumb(breadcrumb) {
        let existingBreadcrumb = document.querySelector('.breadcrumb ul');
        while (existingBreadcrumb.children.length > breadcrumb.length) {
            existingBreadcrumb.lastChild.remove();
        }
        for (let i = 0; i < breadcrumb.length; i++) {
            let crumb = breadcrumb[i];
            let existing = existingBreadcrumb.children[i];
            if (existing) {
                let existingA = existing.firstElementChild;
                if (existingA.textContent == crumb.title && (existingA.attributes['href'] || {}).value == crumb.url)
                    continue;//nie zmieniamy, jest ten sam
            }
            while (existingBreadcrumb.children.length > i) {
                existingBreadcrumb.lastChild.remove();
            }
            let li = existingBreadcrumb.addChild('li');
            if (crumb.url)
                li.addChild('a', {href: crumb.url, text: crumb.title});
            else
                li.addChild('span', {text: crumb.title});
        }
        let last = breadcrumb[breadcrumb.length - 1];
        document.title = last.title;
    },
    registerController(name, controller) {
        this._constrollers[name] = controller;
    }
};
addEventListener('popstate', e => pageManager.goto(location.href, {ignoreHistory: true}))