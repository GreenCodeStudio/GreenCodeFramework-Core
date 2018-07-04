export const formManager = {
    load(form, data) {
        let formElements = form.elements;
        for (var elem of formElements) {
            if (data[elem.name]) {
                elem.value = data[elem.name];
            } else {
                elem.value = '';
            }
        }
    }
};