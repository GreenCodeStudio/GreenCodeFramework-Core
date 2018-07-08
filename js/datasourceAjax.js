import {Ajax} from "./ajax";

export class datasourceAjax{
    constructor(controller,method){
        this.controller=controller;
        this.method=method;
    }
    get(options){
        Ajax(this.controller,this.method);
    }
}