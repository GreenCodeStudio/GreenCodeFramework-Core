const parseString = require('xml2js').parseString;
module.exports = function loader(xml) {
    //var callback = this.async();
    var DOMParser = require('xmldom').DOMParser;
    var doc = new DOMParser().parseFromString(xml);
    return `
import {I18nNode} from "../Core/js/Internationalization/i18nNode.js";
import {I18nTextValue} from "../Core/js/Internationalization/i18nTextValue.js";
import {Translator} from "../Core/js/Internationalization/translator.js";
console.log(I18nNode);
export const node = ${xmlToNode(doc.documentElement)};
export const translator=new Translator(node);
export function t(q){return translator.translate(q).toString();}
`;
}

function xmlToNode(xml) {
    let childNodes = Array.from(xml.childNodes).filter(x => x.tagName == 'node').map(x => xmlToNode(x));
    let values = Array.from(xml.childNodes).filter(x => x.tagName == 'value').map(x => xmlToValue(x));
    var name = xml.getAttribute("name")
    return `new I18nNode(${JSON.stringify(name)},[${values.join(',')}],[${childNodes.join(',')}])`;
}

function xmlToValue(xml) {
    var lang = xml.getAttribute("lang")
    var value = xml.textContent;
    return `new I18nTextValue(${JSON.stringify(lang)}, ${JSON.stringify(value)})`;
}