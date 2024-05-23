import template from './ConfigPopup.mpts';
import {t} from '../../i18n.xml';
import {TableView} from "./tableView";
import {ListView} from "./listView";

export class ConfigPopup extends HTMLElement {
    constructor(objectsList) {
        super();
        this.append(template({t, columns: objectsList.columns}))
        this.querySelector('.mode').onchange = () => {
            objectsList.infiniteScrollEnabled = this.querySelector('.mode').value == 'scrollMode'
            objectsList.refresh()
        }
        this.querySelector('.view').onchange = () => {
            if (this.querySelector('.view').value == 'tableView')
                objectsList.insideViewClass = TableView
            else
                objectsList.insideViewClass = ListView
            objectsList.refresh()
        }
        this.addEventListener('blur', () => this.remove())
        this.querySelector('select').focus()
        for (const checkbox of this.querySelectorAll('table input[type="checkbox"]')) {
            checkbox.onchange = () => {
                if (checkbox.checked)
                    objectsList.hiddenColumns.delete(checkbox.dataset.name)
                else
                    objectsList.hiddenColumns.add(checkbox.dataset.name)

                objectsList.refresh()
            }
        }
    }
}

customElements.define('config-popup', ConfigPopup);
