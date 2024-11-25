import { ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { MailGroup, MailGroupForm, OCSResponse } from '~/type'

const loading = ref(false)
const entries = ref<MailGroup[]>([])

const serversUrl = () => generateOcsUrl('/apps/nextmail/groups')
const serverIdUrl = (id: string) => generateOcsUrl(`/apps/nextmail/groups/${id}`)
const reload = async () => {
	if (!loading.value) {
		loading.value = true
		return axios.get<OCSResponse<MailGroup[]>>(serversUrl())
			.then(r => {
				entries.value = r.data.ocs.data
			})
			.catch(() => {
			})
			.finally(() => {
				loading.value = false
			})
	}
}

const edit = async ({ id, email, ...data }: MailGroupForm) => {
	if (!loading.value) {
		loading.value = true
		const parsedEmail = email.match(/@/g)?.length === 1 ? email : null
		return axios.put<OCSResponse<MailGroup>>(serverIdUrl(id), { ...data, email: parsedEmail })
			.then(r => {
				const a = r.data.ocs.data
				entries.value = entries.value.map(b => b.id === a.id ? a : b)
			})
			.catch(() => {
			})
			.finally(() => {
				loading.value = false
			})
	}
}

export default function useServerList() {
	reload().then(() => {
	})
	return {
		entries,
		loading,
		reload,
		edit,
	}
}
