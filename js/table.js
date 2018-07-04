export class tableManager {
    constructor(table, datasource) {
        this.table = table;
        this.datasource = datasource;
    }

    async refresh() {
        let data = await this.datasource(this);
        this.loadData(data);
    }

    loadData(data) {
        let tbody = this.form.tBodies[0];
        if (!tbody) {
            tbody = this.form.add('tbody');
        }
        tbody.children.removeAll();
    }
}