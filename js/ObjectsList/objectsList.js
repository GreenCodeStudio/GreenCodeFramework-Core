import {TableView} from "./tableView";

export class ObjectsList extends HTMLElement {
    constructor(datasource) {
        super();
        this.columns = [];
        this.generateActions = () => {
        };
        this.datasource = datasource;
        this.datasource.onchange = () => this.refresh();
        this.start = 0;
        this.limit = 10
        this.selected = new Set();
        this.selectedMain = null;
        this.dataById = new Map();
    }

    async refresh() {
        if (!this.insideView)
            this.initInsideView();

        const refreshSymbol = Symbol();
        this.lastRefreshSymbol = refreshSymbol;
        let data = await this.datasource.get(this);
        if (this.lastRefreshSymbol == refreshSymbol) {
            this.currentRows = data.rows;
            this.fillDataById(data.rows);
            this.insideView.loadData(data);
        }
    }

    initInsideView() {
        this.insideView = new TableView(this);
        this.append(this.insideView);
    }

    fillDataById(rows) {
        for (let row of rows) {
            this.dataById.set(parseInt(row.id), row);
        }
    }

    getSelectedData() {
        let ids = [];
        if (this.selectedMain)
            ids.push(this.selectedMain);
        ids = [...ids, ...Array.from(this.selected).filter(id => id != this.selectedMain)];
        return ids.map(id => this.dataById.get(parseInt(id)));
    }
}

customElements.define('data-view', ObjectsList);