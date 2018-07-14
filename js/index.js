import {pageManager} from "./pageManager";

pageManager.onLoad(async (page, data) => {
    let forms = document.querySelectorAll('.dataForm');
    for (let form of forms) {
        if (data[form.dataset.name]) {
            console.log('loadForm');
            let {formManager} = await import("./form");
            formManager.load(form, data[form.dataset.name]);
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