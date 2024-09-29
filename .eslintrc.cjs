/** @type {import('eslint').Linter.Config} */
const config = {
	extends: [
		'@nextcloud/eslint-config/vue3',
	],
	rules: {
		'jsdoc/require-jsdoc': 'off',
		'vue/first-attribute-linebreak': 'off',
	},
	settings: {
		'import/resolver': {
			typescript: {
				alwaysTryTypes: true,
				project: './tsconfig.json',
			},
		},
	},
}

module.exports = config
