import Admin from './Admin.vue'
import { createApp } from 'vue'

if (!OCA.Stalwart) {
	/**
	 * @namespace OCA.Stalwart
	 */
	OCA.Stalwart = {}
}

const Stalwart = createApp(Admin)
	.mount('#main')

OCA.Stalwart.App = Stalwart
