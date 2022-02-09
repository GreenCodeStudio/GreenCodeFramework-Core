import {pageManager} from "./pageManager";
import {AjaxTask} from './ajaxTask';
import {setEvent} from "./events";
import {Ajax} from "./ajax";
import {modal} from "./modal";

addEventListener('focus', () => {
    AjaxTask.refresh();
});
AjaxTask.refresh();

setTimeout(() => pageManager.initPage(window.controllerInitInfo, document.querySelector('.page'), true));
setEvent('click', 'a:not(.nativeLink)', function (e) {
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
    if (new URL(e.filename).origin == document.location.origin) {
        Ajax.Log.addFrontError(obj).catch(() => void 0);
        modal("Wystąpił błąd", "error")
    }
});
addEventListener('unhandledrejection', e => {
    let obj = {
        message: e.reason.message,
        stack: e.reason.stack
    };
    Ajax.Log.addFrontError(obj).catch(() => {
    });
    modal("Wystąpił błąd", "error")
});
window.dbgAjax = Ajax;
