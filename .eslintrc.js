module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
		es2022: true,
	},
	globals: {
		taxPilotData: 'readonly',
	},
	rules: {
		// Allow console.warn and console.error for debugging.
		'no-console': [ 'error', { allow: [ 'warn', 'error' ] } ],
		// JSX is fine without importing React (WordPress element handles it).
		'react/react-in-jsx-scope': 'off',
		// Allow template literals even when not needed (cleaner code).
		'prefer-template': 'off',
		// Enforce correct text domain.
		'@wordpress/i18n-text-domain': [
			'error',
			{ allowedTextDomain: 'taxpilot' },
		],
	},
	settings: {
		react: {
			version: 'detect',
		},
	},
};
