import { computed, ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { OCSResponse, ServerUser, UserResponse } from '~/type'

export default function useServerUserList(cid: string) {
	const loading = ref(false)
	const usersAll = ref<ServerUser[]>([])
	const usersRegistered = ref<ServerUser[]>([])
	const usersAvailable = computed<ServerUser[]>(() => usersAll.value.filter(user => usersRegistered.value.every(u => u.id !== user.id)))
	const usersUrl = generateOcsUrl(`/apps/stalwart/config/${cid}/users`)
	const userUrl = (uid: string) => generateOcsUrl(`/apps/stalwart/config/${cid}/users/${uid}`)

	const reload = async () => {
		if (!loading.value) {
			loading.value = true
			try {
				usersAll.value = await axios.get<OCSResponse<UserResponse>>('/ocs/v2.php/cloud/users/details')
					.then(r => Object.values(r.data.ocs.data.users).map(user => ({
						id: user.id,
						displayName: user.displayname,
						email: user.email,
					})))
				usersRegistered.value = await axios.get<OCSResponse<ServerUser[]>>(usersUrl).then(r => r.data.ocs.data)
			} catch (error) {
				// showError(error)
			} finally {
				loading.value = false
			}
		}
	}

	const addUser = async (uid: string) => {
		if (!loading.value) {
			loading.value = true
			try {
				const user = await axios.post<OCSResponse<ServerUser>>(userUrl(uid)).then(r => r.data.ocs.data)
				usersRegistered.value.push(user)
				// showSuccess(t('User added to server'))
			} catch (error) {
				// showError(error)
			} finally {
				loading.value = false
			}
		}
	}

	const removeUser = async (uid: string) => {
		if (!loading.value) {
			loading.value = true
			try {
				await axios.delete(userUrl(uid))
				usersRegistered.value = usersRegistered.value.filter(user => user.id !== uid)
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
