import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'

/** @typedef {{endpoint: string, username: string, password: string}} StalwartServer */
/** @typedef {{type: string, text: string}} StalwartServerStatus */

/**
 * Get the configuration of stalwart server number `id`.
 * @param {number} id The number of the server to get.
 * @return {Promise<StalwartServer | null>}
 */
export function getServer(id) {
	return axios.get(generateOcsUrl('/apps/stalwart/servers/' + id.toString()))
		.then(response => {
			return response.data.ocs.data
		})
		.catch(error => {
			showError(error)
			return null
		})
}

/**
 * Set the configuration of stalwart server number `id`.
 * @param {number} id The number of the server to set.
 * @param {StalwartServer} server The configuration to set.
 * @return {Promise<boolean>}
 */
export function setServers(id, server) {
	return axios.post(generateOcsUrl('/apps/stalwart/servers/' + id.toString()), server)
		.then(() => {
			showSuccess(t('Stalwart server saved'))
			return true
		})
		.catch(error => {
			showError(error)
			return false
		})
}

/**
 * Get the status of the configuration of stalwart server number `id`.
 * @param {number} id The number of the server to get the status of.
 * @return {Promise<{type: string, text: string}>}
 */
export function getStatu(id) {
	return axios.get(generateOcsUrl('/apps/stalwart/servers/' + id.toString() + '/status'))
		.then(response => {
			return response.data.ocs.data
		})
		.catch(error => {
			showError(error)
			return null
		})
}
