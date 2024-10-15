import { computed, ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { OCSResponse, MailUser, UserResponse } from '~/type'

export default function useServerUserList(srv: string) {
	const loading = ref(false)
	const usersAll = ref<MailUser[]>([])
	const usersRegistered = ref<MailUser[]>([])
	const usersAvailable = computed<MailUser[]>(() => usersAll.value.filter(user => usersRegistered.value.every(u => u.id !== user.id)))
	const usersUrl = generateOcsUrl(`/apps/nextmail/servers/${srv}/users`)
	const userUrl = (id: string) => generateOcsUrl(`/apps/nextmail/servers/${srv}/users/${id}`)

	const reload = async () => {
		if (!loading.value) {
			loading.value = true
			try {
				usersAll.value = await axios.get<OCSResponse<UserResponse>>('/ocs/v2.php/cloud/users/details')
					.then(r => Object.values(r.data.ocs.data.users).map(user => ({
						id: user.id,
						name: user.displayname,
						email: user.email,
						admin: false,
						quota: null,
					})))
				usersRegistered.value = await axios.get<OCSResponse<MailUser[]>>(usersUrl).then(r => r.data.ocs.data)
			} catch (error) {
				// showError(error)
			} finally {
				loading.value = false
			}
		}
	}

	const addUser = async (id: string) => {
		if (!loading.value) {
			loading.value = true
			try {
				const user = await axios.post<OCSResponse<MailUser>>(userUrl(id), { admin: false, quota: null }).then(r => r.data.ocs.data)
				usersRegistered.value.push(user)
				// showSuccess(t('User added to server'))
			} catch (error) {
				// showError(error)
			} finally {
				loading.value = false
			}
		}
	}

	const removeUser = async (id: string) => {
		if (!loading.value) {
			loading.value = true
			try {
				await axios.delete(userUrl(id))
				usersRegistered.value = usersRegistered.value.filter(user => user.id !== id)
				// showSuccess(t('User removed from server'))
			} catch (error) {
				// showError(error)
			} finally {
				loading.value = false
			}
		}
	}

	return {
		usersRegistered,
		usersAvailable,
		loading,
		reload,
		addUser,
		removeUser,
	}
}
