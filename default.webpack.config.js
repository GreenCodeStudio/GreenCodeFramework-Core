const MiniCssExtractPlugin = require('mini-css-extract-plugin');
var path = require('path');
module.exports = {
    entry: {
        main: './jsBuild.js',
        serviceWorker: './modules/Core/js/serviceWorker.js',
    }, output: {
        path: path.resolve(__dirname, './public_html') + '/dist/',
        publicPath: "/dist/",
        filename: '[name].js',
        chunkFilename: '[name].[id].js'
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: [
                MiniCssExtractPlugin.loader,
                //"style-loader", // creates style nodes from JS strings
                "css-loader", // translates CSS into CommonJS
                "sass-loader" // compiles Sass to CSS, using Node Sass by default
            ]

        }, {
            test: /\.(woff(2)?|ttf|eoty)(\?v=\d+\.\d+\.\d+)?$/,
            use: [{
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]',
                    outputPath: '/fonts/'
                }
            }]
        }, {
            test: /i18n\.xml$/,
            use: ["./modules/Core/js/Internationalization/I18nWebpackLoader"]
        }]
    },
    plugins: [new MiniCssExtractPlugin()],
};