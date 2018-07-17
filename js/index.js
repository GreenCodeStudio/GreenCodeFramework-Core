import {pageManager} from "./pageManager";

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

pageManager.initPage(window.controllerInitInfo);