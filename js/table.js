import './domExtensions';

export class tableManager {
    constructor(table, datasource) {
        this.table = table;
        this.datasource = datasource;
        this.start = 0;
        this.limit = 50;
        this.search = '';
        this.sort = '';
    }

    async refresh() {
        let data = await this.datasource.get(this);
        this.loadData(data);
    }

    loadData(data) {
        let tbody = this.table.tBodies[0];
        if (!tbody) {
            tbody = this.table.add('tbody');
        }
        tbody.children.removeAll();

        for (let row of data.rows) {
            let tr = tbody.add('tr');
            for (let th of this.table.tHead.firstElementChild.children) {
                let td = tr.add('td');
                td.textContent = row[th.dataset.value];
            }
        }
    }
}