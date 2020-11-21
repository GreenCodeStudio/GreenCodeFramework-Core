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
    }

    async refresh() {
        if (!this.insideView)
            this.initInsideView();

        const refreshSymbol = Symbol();
        this.lastRefreshSymbol = refreshSymbol;
        let data = await this.datasource.get(this);
        if (this.lastRefreshSymbol == refreshSymbol)
            this.insideView.loadData(data);
    }

    initInsideView() {
        this.insideView = new TableView(this);
        this.append(this.insideView);
    }
}

customElements.define('data-view', ObjectsList);