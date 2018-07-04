import {pageManager} from "./pageManager";
import {formManager} from "./form";

pageManager.onLoad((page, data) => {
    let forms = document.querySelectorAll('.dataForm');
    for (var form of forms) {
        if (data[form.dataset.name]) {
            console.log('loadForm');
        }
    }
    formManager.load()
});

pageManager.initPage(window.controllerInitInfo);