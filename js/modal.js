export function modal(text, type = 'info', buttons = [{text: 'ok', value: true}]) {
    return;//tmp for regulus
    return new Promise((resolve, reject) => {
        console.log(text);
        let modalContainer=document.body.addChild('div.modalContainer');
        let modal = modalContainer.addChild('div.modal', {data:{type}});
        let modalText=modal.addChild('div.modal-text');
        for (const line of text.split('\r\n')) {
            modalText.append(line);
            modalText.addChild('br');
        }

        for (let button of buttons) {
            let buttonElem = modal.addChild('button.modal-button', {text:button.text});
            buttonElem.onclick = () => {
                modalContainer.classList.add('closing');
                setTimeout(() => modalContainer.remove(), 1000);
                resolve(button.value);
            };
            buttonElem.focus();
        }
    });
}