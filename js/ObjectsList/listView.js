import {ContextMenu} from "../contextMenu";
import {pageManager} from "../pageManager";
import {AbstractView} from "./abstractView";

export class ListView extends AbstractView {
    constructor(objectsList) {
        super();
        this.objectsList = objectsList;
        this.init();
        window.dbgList = this;
    }

    init() {
        this.body = this.addChild('.bodyContainer').addChild('.body');
    }

    calcMaxVisibleItems(height) {
        return Math.floor((height) / this.rowHeight);
    }

    get rowHeight() {
        return Math.max(63, this.objectsList.columns.length * 21) + 20;
    }

    generateRow(data) {
        let tr = this.body.querySelector(`.tr[data-row="${data.id}"]`);
        if (!tr) {
            tr = document.create('.tr');
            tr.draggable = true;
        }
        tr.classList.toggle('selected', this.objectsList.selected.has(data.id))
        this.fillRowContent(tr, data);
        return tr;
    }

    fillRowContent(tr, data) {
        tr.children.removeAll();
        tr.addChild('.icon', {className: this.objectsList.icon});
        for (let column of this.objectsList.columns) {
            let keyValue = tr.addChild('.keyValue');
            let key = keyValue.addChild('strong', {text: column.name + ': '});
            let value = keyValue.addChild('span');
            value.append(column.content(data));
        }
        let actionsTd = tr.addChild('.td.actions');
        let actions = this.objectsList.generateActions([data], 'row');
        for (let action of actions) {
            let actionButton = actionsTd.addChild(action.href ? 'a.button' : 'button', {
                title: action.name
            });
            if (action.href) {
                actionButton.href = action.href;
            }
            if (action.command) {
                actionButton.onclick = action.command;
            }
            if (action.icon) {
                actionButton.addChild('span', {classList: [action.icon]});
                actionButton.append(action.name);
            } else {
                actionButton.textContent = action.name;
            }
        }


        tr.dataset.row = data.id;
        // tr.oncontextmenu = this.contextMenu.bind(this, tr);
        // tr.onclick = this.trOnClick.bind(this, data);
        // tr.ondblclick = this.trOnDblClick.bind(this, data, tr);
        // tr.onkeydown = this.trOnKeyDown.bind(this, data, tr);
        // tr.ondragstart = this.trOnDragStart.bind(this, data, tr);
    }
}

customElements.define('list-view', ListView);