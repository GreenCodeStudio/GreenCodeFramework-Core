const ExtractTextPlugin = require("extract-text-webpack-plugin");
var path = require('path');
module.exports = {
    entry: {
        main: './js/build.js',
        style: './scss/build.scss'
    }, output: {
        path: path.resolve(__dirname, './public_html')+'/dist/',
        publicPath: "/dist"
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: ExtractTextPlugin.extract({
                use: [
                    //"style-loader", // creates style nodes from JS strings
                    "css-loader", // translates CSS into CommonJS
                    "sass-loader" // compiles Sass to CSS, using Node Sass by default
                ]
            })
        }   ,         {
            test: /\.(woff(2)?|ttf|eoty)(\?v=\d+\.\d+\.\d+)?$/,
            use: [{
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]',
                    outputPath: '/fonts/'
                }
            }]
        }]
    },
    plugins: [
        new ExtractTextPlugin("styles.css"),
    ]

};