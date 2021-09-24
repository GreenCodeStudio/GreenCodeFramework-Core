var merge = require('util.merge-packages').default;
var fs = require('fs');

var dst = fs.readFileSync('modules/Core/package.json');

var folders = fs.readdirSync('modules');
for (let module of folders) {

    try {
        var src = fs.readFileSync('modules/' + module + '/package.json');
        dst = merge(dst, src);
    } catch (ex) {
    }
}
fs.writeFileSync('package.json', dst);