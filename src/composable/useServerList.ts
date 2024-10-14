import { ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { OCSResponse, MailServer, MailServerForm } from '~/type'

export default function useServerList() {
	const loading = ref(false)
	const servers = ref<MailServer[]>([])
	const configUrl = generateOcsUrl('/apps/nextmail/servers')
	const configIdUrl = (id: string) => generateOcsUrl(`/apps/nextmail/servers/${id}`)
	const active = ref<MailServer | null>(null)

	return {
		entries: servers,
		loading,
		active,
		to: (id: string | null) => {
			if (!loading.value) {
				active.value = id === null ? null : servers.value.find(a => a.id === id) ?? null
			}
		},
		reload: async () => {
			if (!loading.value) {
				loading.value = true
				try {
					servers.value = await axios.get<OCSResponse<MailServer[]>>(configUrl).then(r => r.data.ocs.data)
					const id = active.value?.id
					if (id !== undefined) {
						active.value = servers.value.find(a => a.id === id) ?? null
					}
				} catch (error) {
					// showError(error)
					servers.value = []
				} finally {
					loading.value = false
				}
			}
		},
		create: async () => {
			if (!loading.value) {
				loading.value = true
				try {
					const a = await axios.post<OCSResponse<MailServer>>(configUrl).then(r => r.data.ocs.data)
					servers.value.push(a)
					if (active.value === null || active.value.id === a.id) {
						active.value = a
					}
					// showSuccess(t('Stalwart server added'))
				} catch (error) {
					// showError(error)
				} finally {
					loading.value = false
				}
			}
		},
		edit: async ({ id, ...data }: MailServerForm) => {
			if (!loading.value) {
				loading.value = true
				try {
					const a = await axios.put<OCSResponse<MailServer>>(configIdUrl(id), data).then(r => r.data.ocs.data)
					servers.value = servers.value.map(b => b.id === a.id ? a : b)
					if (active.value?.id === a.id) {
						active.value = a
					}
					// showSuccess(t('Stalwart server saved'))
				} catch (error) {
					// showError(error)
				} finally {
					loading.value = false
				}
			}
		},
		remove: async (id: string) => {
			if (!loading.value) {
				loading.value = true
				try {
					await axios.delete(configIdUrl(id))
					servers.value = servers.value.filter(a => a.id !== id)
					if (active.value?.id === id) {
						active.value = null
					}
					// showSuccess(t('Stalwart server removed'))
				} catch (error) {
					// showError(error)
				} finally {
					loading.value = false
				}
			}
		},
	}
}
