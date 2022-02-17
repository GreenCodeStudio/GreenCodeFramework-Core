export class AbstractView extends HTMLElement {
    loadData(data, start, limit, infiniteScrollEnabled) {
        this.style.setProperty('--height', (data.total * this.rowHeight) + 'px');
        let top = 0;
        let isOdd = true;
        if (infiniteScrollEnabled) {
            top = this.rowHeight * start;
            isOdd = start % 2 == 0;
        }
        let newChildren = [];
        for (let row of data.rows) {
            let tr = this.generateRow(row);
            tr.style.top = `${top}px`;
            tr.classList.toggle('odd', isOdd)
            tr.classList.toggle('even', !isOdd)
            newChildren.push(tr);
            top += this.rowHeight;
            isOdd = !isOdd;
        }
        let oldChildren = Array.from(this.body.children);
        for (let tr of oldChildren.filter(tr => !newChildren.includes(tr) && !tr.matches(':focus'))) {
            tr.remove();
        }

        for (let tr of newChildren.filter(tr => !oldChildren.includes(tr))) {
            this.body.appendChild(tr);
        }
    }

    onScroll(e) {
        if (this.onPaginationChanged) {
            let start = Math.round(this.scrollTop / this.rowHeight);
            let passedStart = Math.floor(start / 20) * 20 - 20;
            if (passedStart < 0)
                passedStart = 0;
            this.onPaginationChanged(passedStart);
        }
    }
}