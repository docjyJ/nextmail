// eslint-disable-next-line n/no-unpublished-import
import { createAppConfig } from '@nextcloud/vite-config'
import path from 'node:path'

export default createAppConfig(
	{ main: path.resolve(__dirname, 'src', 'main.ts') },
	{
		inlineCSS: true,
		config: {
			resolve: {
				alias: {
					'~': path.resolve(__dirname, 'src'),
				},
			},
			build: {
				outDir: path.resolve(__dirname),
				chunkSizeWarningLimit: 2048,
			},
		},
	},
)
