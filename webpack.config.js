const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		wizard: path.resolve( __dirname, 'src/wizard/index.js' ),
		dashboard: path.resolve( __dirname, 'src/dashboard/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
