import {pageManager} from "./pageManager";

pageManager.onLoad(async (page, data) => {
    let forms = document.querySelectorAll('.dataForm');
    for (var form of forms) {
        if (data[form.dataset.name]) {
            console.log('loadForm');
            let {formManager} = await import("./form");
            formManager.load(form, data[form.dataset.name]);
        }
    }
});

pageManager.initPage(window.controllerInitInfo);