import {TableView} from "./tableView";
import {ListView} from "./listView";
import {t} from "../../i18n.xml";
import {PaginationButtons} from "./paginationButtons";
import {ContextMenu} from "../contextMenu";
import {ConcurencyLimiter} from "../utils/concurencyLimiter";
import {IdsSet} from "./idsSet";
import {ConfigPopup} from "./ConfigPopup";

export class ObjectsList extends HTMLElement {
    constructor(datasource) {
        super();
        this.columns = [];
        this.hiddenColumns=new Set()
        this.generateActions = () => [];
        this.insideViewClass = TableView;
        this.icon = 'icon-document';
        this.loadConcurencyLimiter = new ConcurencyLimiter();
        this.datasource = datasource;
        this.datasource.onchange = () => this.refresh();
        this.start = 0;
        this.limit = 10
        this.total = 0;
        this.selected = new IdsSet();
        this.selectedMain = null;
        this.dataById = new Map();
        this.initFoot();
        this.addEventListener('contextmenu', e => this.showGlobalContextMenu(e));
        addEventListener('resize', e => this.resize());
        this.infiniteScrollEnabled = false;
    }
    get visibleColumns(){
        return this.columns.filter(x=>!this.hiddenColumns.has(x.dataName))
    }

    refreshLimit() {
        this.limit = this.insideView.calcMaxVisibleItems(this.clientHeight - this.foot.clientHeight - 2);
        if (this.limit < 1) this.limit = 1;
        if (this.infiniteScrollEnabled) {
            this.limit = Math.ceil(this.limit / 20) * 20 + 40;
        } else {
            this.start = Math.floor(Math.min(this.total, this.start) / this.limit) * this.limit;
        }
    }

    async refresh() {
        this.classList.toggle('infiniteScrollEnabled', this.infiniteScrollEnabled);

        if (!this.insideView || !(this.insideView instanceof this.insideViewClass))
            this.initInsideView();

        this.refreshLimit();
        const refreshSymbol = Symbol();
        this.lastRefreshSymbol = refreshSymbol;
        await this.loadConcurencyLimiter.run(async () => {
            if (this.lastRefreshSymbol != refreshSymbol) return;

            let data = await this.datasource.get(this);
            if (this.lastRefreshSymbol != refreshSymbol) return;

            this.currentRows = data.rows;
            this.fillDataById(data.rows);
            this.total = data.total;
            this.insideView.loadData(data, this.start, this.limit, this.infiniteScrollEnabled);
            this.pagination.currentPage = Math.floor(this.start / this.limit);
            this.pagination.totalPages = Math.ceil(this.total / this.limit);
            this.pagination.render();
        });
    }

    initInsideView() {
        if (this.insideView) {
            this.insideView.remove();
        }
        this.insideView = new this.insideViewClass(this);
        this.insertBefore(this.insideView, this.foot);
        this.insideView.refresh = () => this.refresh();
        this.insideView.onPaginationChanged = (start, limit) => {
            if (this.start != start) {
                this.start = start;
                //this.limit=limit;
                this.refresh();
            }
        }
    }

    fillDataById(rows) {
        for (let row of rows) {
            this.dataById.set(parseInt(row.id), row);
        }
    }

    getSelectedData() {
        let data = Array.from(this.dataById).filter(([id, row]) => this.selected.has(id)).map(([id, row]) => row);
        if (data.length > 0) return data;
        else if (this.dataById[this.selectedMain])
            return [this.dataById[this.selectedMain]]
        else return [];
    }

    initFoot() {
        this.foot = this.addChild('.foot');
        let menuButton = this.foot.addChild('button.menuButton span.icon-settings');
        menuButton.onclick = e => this.showConfigPopup();
        this.pagination = new PaginationButtons();
        this.pagination.onpageclick = (page) => {
            this.start = page * this.limit;
            this.refresh();
        }
        this.foot.append(this.pagination);
        this.searchForm = this.foot.addChild('form', {className: 'search'});
        this.searchForm.onsubmit = e => e.preventDefault();
        const searchInput = this.searchForm.addChild('input', {
            name: 'search',
            type: 'search',
            placeholder: t('objectList.search')
        });
        searchInput.oninput = e => {
            this.start = 0;
            this.refresh();
        }

    }

    get search() {
        if (!this.foot) return '';
        const searchForm = this.foot.querySelector('.search');
        if (!searchForm) return '';
        return searchForm.search.value;
    }

    showGlobalContextMenu(e) {
        let elements = [{
            text: t('objectList.paginationMode'),
            icon: 'icon-pagination',
            onclick: () => {
                this.infiniteScrollEnabled = false;
                this.refresh();
            }
        }, {
            text: t('objectList.scrollMode'),
            icon: 'icon-scroll',
            onclick: () => {
                this.infiniteScrollEnabled = true;
                this.refresh();
            }
        }, {
            text: t('objectList.tableView'),
            icon: 'icon-table',
            onclick: () => {
                this.insideViewClass = TableView;
                this.refresh();
            }
        }, {
            text: t('objectList.listView'),
            icon: 'icon-table',
            onclick: () => {
                this.insideViewClass = ListView;
                this.refresh();
            }
        }];
        if (this.allowTableEdit) {
            elements.push({
                text: t('objectList.startMultiEdit'),
                icon: 'icon-edit',
                onclick: () => {
                    this.insideViewClass = TableView;
                    this.refresh();
                    this.insideView.startMultiEdit();
                }
            });
        }
        ContextMenu.openContextMenu(e, elements);
    }

    resize() {
        let limit = this.limit;
        this.refreshLimit();
        if (limit != this.limit) {
            this.refresh();
        }
    }

    multiEditChanged(id, data, save = false) {
        this.datasource.multiEditChanged(id, data, save);
    }

    showConfigPopup() {
        const popup = new ConfigPopup(this)
        this.append(popup)
    }
}

customElements.define('data-view', ObjectsList);
