import { ref } from 'vue'
import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import type { OCSResponse, ServerStatus } from '~/type'

export default function useServerStatus(id: number) {
	const status = ref<ServerStatus | null>(null)
	const statusIdUrl = generateOcsUrl(`/apps/stalwart/status/${id}`)
	return {
		status,
		reload: async () => {
			status.value = null
			try {
				status.value = await axios.get<OCSResponse<ServerStatus>>(statusIdUrl).then(r => r.data.ocs.data)
			} catch (error) {
				// showError(t('Failed to load server status'))
			}
		},
	}
}
