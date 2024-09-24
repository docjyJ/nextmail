const webpackConfig = require('@nextcloud/webpack-vue-config')
const ESLintPlugin = require('eslint-webpack-plugin')
const StyleLintPlugin = require('stylelint-webpack-plugin')
const path = require('path')

function mergeConfig(webpackConfig) {
	webpackConfig.plugins.push(new ESLintPlugin({
		extensions: ['js', 'vue'],
		files: 'src',
	}))
	webpackConfig.plugins.push(new StyleLintPlugin({
		files: 'src/**/*.{css,scss,vue}',
	}))
	webpackConfig.module.rules.push({
		test: /\.svg$/i,
		type: 'asset/source',
	})
	webpackConfig.entry.main = path.resolve(path.join('src', 'main.js'))
	return webpackConfig
}

function getConfig() {
	return mergeConfig(webpackConfig)
}

module.exports = getConfig
