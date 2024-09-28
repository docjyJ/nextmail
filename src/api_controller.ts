import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showError, showSuccess } from './nextcloud/dialogs.js'
import t from './l10n'

export type ServerConfig = { id: number, endpoint: string; username: string; password: string }
type StalwartServerStatus = { type: string; text: string }

export async function listServers(): Promise<ServerConfig[] | null> {
	try {
		const response = await axios.get(generateOcsUrl('/apps/stalwart/config'))
		return response.data.ocs.data
	} catch (error) {
		showError(error)
		return null
	}
}

export async function pushServers(): Promise<ServerConfig | null> {
	try {
		const response = await axios.post(generateOcsUrl('/apps/stalwart/config'))
		showSuccess(t('Stalwart server added'))
		return response.data.ocs.data
	} catch (error) {
		showError(error)
		return null
	}
}

export async function getServerConfig(id: number): Promise<ServerConfig | null> {
	try {
		const response = await axios.get(generateOcsUrl('/apps/stalwart/config/' + id.toString()))
		return response.data.ocs.data
	} catch (error) {
		showError(error)
		return null
	}
}

export async function setServers({id, ...data}: ServerConfig): Promise<boolean> {
	try {
		await axios.post(generateOcsUrl('/apps/stalwart/config/' + id.toString()), data)
		showSuccess(t('Stalwart server saved'))
		return true
	} catch (error) {
		showError(error)
		return false
	}
}

export async function getStatus(id: number): Promise<StalwartServerStatus | null> {
	try {
		const response = await axios.get(generateOcsUrl('/apps/stalwart/status/' + id.toString()))
		return response.data.ocs.data
	} catch (error) {
		showError(error)
		return null
	}
}
