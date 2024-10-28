import { ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { MailServer, MailServerForm, OCSResponse } from '~/type'

const loading = ref(false)
const entries = ref<MailServer[]>([])

const serversUrl = () => generateOcsUrl('/apps/nextmail/servers')
const serverIdUrl = (id: string) => generateOcsUrl(`/apps/nextmail/servers/${id}`)
const reload = async () => {
	if (!loading.value) {
		loading.value = true
		entries.value = await axios.get<OCSResponse<MailServer[]>>(serversUrl())
			.then(r => r.data.ocs.data)
			.catch(() => [] as MailServer[])
		loading.value = false
	}
}

const create = async () => {
	if (!loading.value) {
		loading.value = true
		const a = await axios.post<OCSResponse<MailServer>>(serversUrl())
			.then(r => r.data.ocs.data)
			.catch(() => null)
		if (a !== null) {
			entries.value.push(a)
		}
		loading.value = false
	}
}

const edit = async ({ id, ...data }: MailServerForm) => {
	if (!loading.value) {
		loading.value = true
		const a = await axios.put<OCSResponse<MailServer>>(serverIdUrl(id), data)
			.then(r => r.data.ocs.data)
			.catch(() => null)
		if (a !== null) {
			entries.value = entries.value.map(b => b.id === a.id ? a : b)
		}
		loading.value = false
	}
}

const remove = async (id: string) => {
	if (!loading.value) {
		loading.value = true
		await axios.delete(serverIdUrl(id))
			.catch(() => null)
		entries.value = entries.value.filter(a => a.id !== id)
		loading.value = false
	}
}

const find = (id: string | null) => id === null ? undefined : entries.value.find(a => a.id === id)

export default function useServersList() {
	reload().then(() => {})
	return {
		entries,
		loading,
		reload,
		create,
		edit,
		remove,
		find,
	}
}
