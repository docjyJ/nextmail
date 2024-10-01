import { ref } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import type { OCSResponse, ServerUser, UserResponse } from '~/type'

export default function useServerUserList(id: number) {
	const loading = ref(false)
	const users = ref<ServerUser[]>([])
	const usersServer = ref<ServerUser[]>([])
	const usersUrl = generateOcsUrl(`/apps/stalwart/config/${id}/users`)

	const reload = async () => {
		if (!loading.value) {
			loading.value = true
			try {
				usersServer.value = await axios.get<OCSResponse<UserResponse>>('/ocs/v2.php/cloud/users/details')
					.then(r => Object.values(r.data.ocs.data.users).map(user => ({
						uid: user.id,
						displayName: user.displayname,
						email: user.email,
					})))
				users.value = await axios.get<OCSResponse<ServerUser[]>>(usersUrl).then(r => r.data.ocs.data)
			} catch (error) {
				// showError(error)
				users.value = []
			} finally {
				loading.value = false
			}
		}
	}

	const addUser = async (uid: string) => {
		if (!loading.value) {
			loading.value = true
			try {
				const user = await axios.post<OCSResponse<ServerUser>>(usersUrl, { uid }).then(r => r.data.ocs.data)
				users.value.push(user)
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
				await axios.delete(`${usersUrl}/${uid}`)
				users.value = users.value.filter(user => user.uid !== uid)
				// showSuccess(t('User removed from server'))
			} catch (error) {
				// showError(error)
			} finally {
				loading.value = false
			}
		}
	}

	return {
		users,
		usersServer,
		loading,
		reload,
		addUser,
		removeUser,
	}
}
