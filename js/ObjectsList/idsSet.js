export class IdsSet {
    constructor() {
        this.selected = new Set();
        this.notSelected = new Set();
        this.isSelectedAll = false;
    }

    clear() {
        this.selected.clear();
        this.notSelected.clear();
        this.isSelectedAll = false;
    }
    has(id){
        if(this.isSelectedAll){
            return !this.notSelected.has(id.toString())
        }else{
            return this.selected.has(id.toString())
        }
    }
    add(id){
        if(this.isSelectedAll){
            this.notSelected.delete(id.toString())
        }else{
            this.selected.add(id.toString())
        }
    }
    delete(id){
        if(this.isSelectedAll){
            this.notSelected.add(id.toString())
        }else{
            this.selected.delete(id.toString())
        }
    }
    selectAll(){
        this.clear();
        this.isSelectedAll=true;
    }
}