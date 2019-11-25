var Encore = require('@symfony/webpack-encore');
var webpack = require('webpack');

Encore.setOutputPath('web/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild() //new
    .addEntry('app', './web/src/js/app.js')
    .addEntry('bootstrap-filestyle', './node_modules/bootstrap-filestyle/src/bootstrap-filestyle.js')
    .addStyleEntry('test', './web/src/scss/test.scss')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .splitEntryChunks()
    .autoProvidejQuery()
    .enableSassLoader()
    // .addPlugin(
    //     new webpack.ProvidePlugin({
    //         // identifier: ['module1', 'property1'],
    //         datepicker: 'bootstrap-datepicker',
    //     }),
    //     5,
    // )
    .addPlugin(new webpack.ProvidePlugin({ moment: 'moment' }), 11)
    .addPlugin(new webpack.ProvidePlugin({ select2: 'select2' }), 10)
    .addPlugin(new webpack.ProvidePlugin({ tagit: 'tag-it' }), 9)
    // .addPlugin(new webpack.ProvidePlugin({ bootstrap: 'bootstrap-sass' }), 9)
    .configureBabel(function(babelConfig) {
        const preset = babelConfig.presets.find(
            ([name]) => name === '@babel/preset-env',
        );
        if (preset !== undefined) {
            preset[1].useBuiltIns = 'usage';
            preset[1].corejs = '3.1.4';
            preset[1].debug = true;
        }
    });
// .configureBabel(() => {}, {
//     useBuiltIns: 'usage',
//     corejs: { version: '3.1.4' },
// });

if (Encore.isProduction()) {
    // Enable post css loader
    Encore.enablePostCssLoader();
}

module.exports = Encore.getWebpackConfig();
