import { createAppConfig } from '@nextcloud/vite-config'

export default createAppConfig(
	{ main: 'src/main.js' },
	{ inlineCSS: true },
)
