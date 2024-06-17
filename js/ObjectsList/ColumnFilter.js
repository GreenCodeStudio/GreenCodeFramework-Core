
import {t} from '../../i18n.xml';
export class ColumnFilter extends HTMLElement {
    constructor(props) {
        super(props);
        this.select = this.addChild('select')
        this.select.addChild('option', {value: 'none', text: t('filter.none')})
        this.select.addChild('option', {value: 'equals', text: t('filter.equals')})
        this.select.addChild('option', {value: 'less', text: t('filter.lessThan')})
        this.select.addChild('option', {value: 'more', text: t('filter.greaterThan')})
        this.select.addChild('option', {value: 'contains', text: t('filter.contains')})
        this.select.addChild('option', {value: 'oneOf', text: t('filter.oneOf')})
        this.select.addChild('option', {value: 'empty', text: t('filter.empty')})
        this.input = this.addChild('input')
        this.input.oninput = () => this.emit();
        this.select.onchange = () => {
            this.updateInput();
            this.emit();
        }
        this.updateInput()
    }

    updateInput() {
        if (this.select.value == 'none' || this.select.value == 'empty') {
            this.input.style.display = 'none'
        } else if (this.select.value == 'less' || this.select.value == 'more') {
            this.input.style.display = ''
            this.input.type = 'number';
        } else {
            this.input.style.display = ''
            this.input.type = 'text';
        }
    }

    emit() {
        this.dispatchEvent(new CustomEvent('x-filter', {detail: {type: this.select.value, value: this.input.value}}))
    }
}


customElements.define('column-filter', ColumnFilter);
