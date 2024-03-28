var merge = require('util.merge-packages').default;
var fs = require('fs');

var dst = fs.readFileSync('modules/Core/package.json');

var folders = fs.readdirSync('modules');
for (let module of folders) {

    try {
        if(fs.existsSync('modules/' + module + '/package.json')) {
            var src = fs.readFileSync('modules/' + module + '/package.json');
            dst = merge(dst, src);
        }
    } catch (ex) {
        console.log(ex)
    }
}
fs.writeFileSync('package.json', dst);
//fontello
let glyphs = [];
for (let module of folders) {

    try{
        let fontelloExists= fs.existsSync('modules/'+module+'/fontello.json');
        if(fontelloExists) {
            let fontello = JSON.parse(fs.readFileSync('modules/' + module + '/fontello.json'));
            glyphs = glyphs.concat(fontello.glyphs);

        }
    }catch (ex){
        console.log(ex)
    }
}
glyphs=glyphs.map((g,i)=>({...g,code:0xe800+i}));
let fontelloCodesScss=glyphs.map(x=>`.icon-${x.css}:before { content: '\\${x.code.toString(16)}'; }`).join("\r\n");
fs.mkdirSync('modules/Common/scss',{recursive:true});
fs.mkdirSync('modules/Common/dist',{recursive:true});
fs.writeFileSync('modules/Common/scss/fontello-codes.scss',fontelloCodesScss);

const {Font} =require('fonteditor-core');
const path2contours=require('fonteditor-core/lib/ttf/svg/path2contours').default;
const font = Font.create("",{type:'ttf',name:'fontello'});
const fontObject = font.get();
fontObject.glyf=glyphs.map(g=>{
    let contours=[];
    if(g.svg){
        contours=path2contours(g.svg.path);
    }
    return {
        name: g.css,
        unicode: [g.code],
        xMin: 0,
        yMin: 0,
        xMax: 1000,
        yMax: 1000,
        contours,
        advanceWidth: 1000,
        leftSideBearing: 0,
    };
})
font.set(fontObject);
fs.writeFileSync('modules/Common/dist/fontello.woff', font.write({    type: 'woff',}));
fs.writeFileSync('modules/Common/dist/fontello.ttf', font.write({    type: 'ttf',}));
fs.writeFileSync('modules/Common/dist/fontello.svg', font.write({    type: 'svg',}));
