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
	.enableSassLoader()
	.enablePostCssLoader()
	.configureBabel(function (babelConfig) {
		const preset = babelConfig.presets.find(([name]) => name === "@babel/preset-env");
		if (preset !== undefined) {
			preset[1].useBuiltIns = "usage";
			preset[1].debug = true;
		}
	});

module.exports = Encore.getWebpackConfig();


// var Encore = require('@symfony/webpack-encore');

// Encore
// 	.setOutputPath('web/build/')
// 	//.setPublicPath('/build')
// 	.setPublicPath('/build') //aktywny_dyrektor\web\build
// 	//.cleanupOutputBeforeBuild() //new
// 	//.setManifestKeyPrefix('/build') //new
// 	.addEntry('app', './web/assets/js/app.js')
// 	.enableSingleRuntimeChunk()
// 	//.cleanupOutputBeforeBuild()
// 	.enableSourceMaps(!Encore.isProduction())
// 	.enableVersioning(Encore.isProduction())
// 	.splitEntryChunks()
// 	.autoProvidejQuery()
// 	.enableSassLoader();

// var config = Encore.getWebpackConfig();

// config.devServer = {
// 	//poll: true,
// 	//ignored: /node_modules/,
// 	//inline: true,
// 	contentBase: './build', //   ./
// 	port: 8009,
// 	historyApiFallback: true
// };


// module.exports = config;