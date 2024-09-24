import Vue from 'vue'
import Admin from './Admin.vue'
import { translate } from '@nextcloud/l10n'

function translateStalwart(key) {
	return translate('stalwart', key)
}

Vue.prototype.t = translateStalwart
window.t = translateStalwart

const View = Vue.extend(Admin)
new View({}).$mount('#main')
