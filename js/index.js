import {pageManager} from "./pageManager";
import {AjaxTask} from './ajaxTask';
import {setEvent} from "./events";
import {Ajax} from "./ajax";
import {modal} from "./modal";

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
    modal("Wystąpił błąd", "error")
});
window.dbgAjax = Ajax;
