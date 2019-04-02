var Encore = require('@symfony/webpack-encore');

Encore
	.setOutputPath('web/build/')
	.setPublicPath('/build')
	.addEntry('app', './web/assets/js/app.js')
	.enableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableSourceMaps(!Encore.isProduction())
	.enableVersioning(Encore.isProduction())
	.splitEntryChunks()
	.autoProvidejQuery()
	.enableSassLoader();

module.exports = Encore.getWebpackConfig();