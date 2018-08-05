import {pageManager} from "./pageManager";
import {AjaxTask} from './ajaxTask';
import {setEvent} from "./events";

pageManager.onLoad(async (page, data) => {
    let forms = document.querySelectorAll('.dataForm');
    if (forms.length) {
        let {formManager} = await import("./form");
        formManager.initEvents();

        for (let form of forms) {
            if (data && data[form.dataset.name]) {
                console.log('loadForm');
                formManager.load(form, data[form.dataset.name]);
            }
        }
    }

    let tables = document.querySelectorAll('.dataTable');
    for (let table of tables) {
        let [{tableManager}, {datasourceAjax}] = await Promise.all([import("./table"), import( "./datasourceAjax")]);
        let datasource = new datasourceAjax(table.dataset.controller, table.dataset.method);
        table.datatable = new tableManager(table, datasource);
        table.datatable.refresh();
    }

});
addEventListener('focus', () => {
    AjaxTask.refresh();
});
if ('serviceWorker' in navigator) {
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
pageManager.initPage(window.controllerInitInfo);

setEvent('click', 'a', function (e) {
    e.preventDefault();
    pageManager.goto(this.href);
});
import {Ajax} from "./ajax";
window.dbgAjax=Ajax;