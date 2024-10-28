import { ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { MailUser, MailUserForm, OCSResponse } from '~/type'

const loading = ref(false)
const entries = ref<MailUser[]>([])

const serversUrl = () => generateOcsUrl('/apps/nextmail/users')
const serverIdUrl = (id: string) => generateOcsUrl(`/apps/nextmail/users/${id}`)
const reload = async () => {
	if (!loading.value) {
		loading.value = true
		return axios.get<OCSResponse<MailUser[]>>(serversUrl())
			.then(r => {
				entries.value = r.data.ocs.data
			})
			.catch(() => {})
			.finally(() => {
				loading.value = false
			})
	}
}

const edit = async ({ id, ...data }: MailUserForm) => {
	if (!loading.value) {
		loading.value = true
		return axios.put<OCSResponse<MailUser>>(serverIdUrl(id), data)
			.then(r => {
				const a = r.data.ocs.data
				entries.value = entries.value.map(b => b.id === a.id ? a : b)
			})
			.catch(() => {})
			.finally(() => {
				loading.value = false
			})
	}
}

export default function useServerList() {
	reload().then(() => {})
	return {
		entries,
		loading,
		reload,
		edit,
	}
}
