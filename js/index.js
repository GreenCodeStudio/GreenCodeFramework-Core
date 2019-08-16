import {pageManager} from "./pageManager";
import {AjaxTask} from './ajaxTask';
import {setEvent} from "./events";
import {Ajax} from "./ajax";

pageManager.onLoad(async (page, data) => {
    let forms = page.querySelectorAll('.dataForm');
    if (forms.length) {
        let {formManager} = await import("./form");
        formManager.initEvents();

        for (let form of forms) {
            if (data && data.selects)
                formManager.loadSelects(data.selects);
            if (data && data[form.dataset.name]) {
                console.log('loadForm');
                formManager.load(form, data[form.dataset.name]);
            }
        }
    }

    let tables = page.querySelectorAll('.dataTable');
    for (let table of tables) {
        let [{TableManager}, {datasourceAjax}] = await Promise.all([import("./table"), import( "./datasourceAjax")]);
        let webSocketPath = [];
        if (table.dataset.webSocketPath)
            webSocketPath = table.dataset.webSocketPath.split('/');
        let datasource = new datasourceAjax(table.dataset.controller, table.dataset.method, webSocketPath);
        table.datatable = new TableManager(table, datasource);
        table.datatable.refresh();
    }

});
addEventListener('focus', () => {
    AjaxTask.refresh();
});
AjaxTask.refresh();
if ('serviceWorker' in navigator && !window.DEBUG) {
    window.addEventListener('load', function () {
        window.swRegistratonPromise = navigator.serviceWorker.register('/dist/serviceWorker.js', {scope: '/'});
    });
}
var deferredPwaPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    // Stash the event so it can be triggered later.
    deferredPwaPrompt = e;
    e.prompt();
});

setTimeout(() => pageManager.initPage(window.controllerInitInfo, document.querySelector('.page')));
setEvent('click', 'a', function (e) {
    e.preventDefault();
    pageManager.goto(this.href);
});
window.dbgAjax = Ajax;
