// var Encore = require('@symfony/webpack-encore');

// Encore
// 	.setOutputPath('web/build/')
// 	.setPublicPath('/build')
// 	.cleanupOutputBeforeBuild() //new
// 	.addEntry('app', './web/assets/js/app.js')
// 	.enableSingleRuntimeChunk()
// 	.cleanupOutputBeforeBuild()
// 	.enableSourceMaps(!Encore.isProduction())
// 	.enableVersioning(Encore.isProduction())
// 	.splitEntryChunks()
// 	.autoProvidejQuery()
// 	.enableSassLoader();

// module.exports = Encore.getWebpackConfig();


var Encore = require('@symfony/webpack-encore');

Encore
	.setOutputPath('web/build/')
	.setPublicPath('/build')
	.cleanupOutputBeforeBuild() //new
	.addEntry('app', './web/assets/js/app.js')
	.enableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableSourceMaps(!Encore.isProduction())
	.enableVersioning(Encore.isProduction())
	.splitEntryChunks()
	.autoProvidejQuery()
	.enableSassLoader();

var config = Encore.getWebpackConfig();

config.devServer.watchOptions = {
	poll: true,
	ignored: /node_modules/,
	inline: true,
	contentBase: './',
	port: 8000,
	historyApiFallback: true
};


module.exports = config;