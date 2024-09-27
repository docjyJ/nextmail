import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showError, showSuccess } from './nextcloud/dialogs.js'
import t from './l10n'

type StalwartServer = { endpoint: string; username: string; password: string }
type StalwartServerStatus = { type: string; text: string }

export async function getServer(id: { toString: () => string }): Promise<StalwartServer | null> {
	try {
		const response = await axios.get(generateOcsUrl('/apps/stalwart/servers/' + id.toString()))
		return response.data.ocs.data
	} catch (error) {
		showError(error)
		return null
	}
}

export async function setServers(id: number, server: StalwartServer): Promise<boolean> {
	try {
		await axios.post(generateOcsUrl('/apps/stalwart/servers/' + id.toString()), server)
		showSuccess(t('Stalwart server saved'))
		return true
	} catch (error) {
		showError(error)
		return false
	}
}

export async function getStatu(id: number): Promise<StalwartServerStatus | null> {
	try {
		const response = await axios.get(generateOcsUrl('/apps/stalwart/servers/' + id.toString() + '/status'))
		return response.data.ocs.data
	} catch (error) {
		showError(error)
		return null
	}
}
