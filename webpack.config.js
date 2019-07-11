var Encore = require('@symfony/webpack-encore');

Encore.setOutputPath('web/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild() //new
    .addEntry('app', './web/src/js/app.js')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .splitEntryChunks()
    .autoProvidejQuery()
    .enableSassLoader();
// .enablePostCssLoader()
// .configureBabel(function(babelConfig) {
//     const preset = babelConfig.presets.find(
//         ([name]) => name === '@babel/preset-env',
//     );
//     if (preset !== undefined) {
//         preset[1].useBuiltIns = 'usage';
//         preset[1].corejs = '2.0.0';
//         preset[1].debug = true;
//     }
// })
// .configureBabel(() => {}, {
//     useBuiltIns: 'usage',
//     corejs: { version: '3.1.4' },
// });

if (Encore.isProduction()) {
    // Enable post css loader
    Encore.enablePostCssLoader();
}

module.exports = Encore.getWebpackConfig();
