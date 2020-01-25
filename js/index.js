import {pageManager} from "./pageManager";
import {AjaxTask} from './ajaxTask';
import {setEvent} from "./events";
import {Ajax} from "./ajax";

pageManager.onLoad(async (page, data) => {
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
    window.swRegistratonPromise = navigator.serviceWorker.register('/dist/serviceWorker.js', {scope: '/'});
}
window.addEventListener('beforeinstallprompt', (e) => {
    let btn = document.create('button.installPWA span.icon-install');
    document.querySelector('body > header').insertBefore(btn, document.querySelector('body > header .tasks'));
    btn.onclick = () => {
        e.prompt();
        btn.remove();
    }
});
setTimeout(() => pageManager.initPage(window.controllerInitInfo, document.querySelector('.page'), true));
setEvent('click', 'a', function (e) {
    e.preventDefault();
    pageManager.goto(this.href);
});
addEventListener('error', e => {
    let obj = {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error,
        stack: (new Error()).stack
    };
    Ajax.Log.addFrontError(obj);
});
window.dbgAjax = Ajax;
