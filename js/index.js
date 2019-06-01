import {pageManager} from "./pageManager";
import {AjaxTask} from './ajaxTask';
import {setEvent} from "./events";
import {Ajax} from "./ajax";

pageManager.onLoad(async (page, data) => {
    let forms = document.querySelectorAll('.dataForm');
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

    let tables = document.querySelectorAll('.dataTable');
    for (let table of tables) {
        let [{TableManager}, {datasourceAjax}] = await Promise.all([import("./table"), import( "./datasourceAjax")]);
        let datasource = new datasourceAjax(table.dataset.controller, table.dataset.method);
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
        navigator.serviceWorker.register('/serviceWorker.js').then(function (registration) {
            // Registration was successful
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
            setTimeout(() => navigator.serviceWorker.controller.postMessage("installOffline"), 20000);
        }, function (err) {
            // registration failed :(
            console.log('ServiceWorker registration failed: ', err);
        });
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