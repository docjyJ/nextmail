<script setup lang="ts">
import { ref, defineProps, onMounted } from 'vue'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import mdiDelete from '@mdi/svg/svg/delete.svg?raw'
import mdiContentSave from '@mdi/svg/svg/content-save.svg?raw'
import useServerUserList from '~/composable/useServerUserList'

const props = defineProps<{
  serverId: string
}>()

const { usersRegistered, usersAvailable, loading, reload, addUser, removeUser } = useServerUserList(props.serverId)
const newUser = ref<{ id: string } | null>()

onMounted(() => {
	reload()
})
</script>

<template>
	<NcSettingsSection
		name="Server Users"
		description="Manage the users for the selected server.">
		<div style="display: inline-flex; gap:8px; width:100%">
			<NcSelect
				v-model="newUser"
				style="flex-grow: 1"
				:options="usersAvailable.map(e => ({
					id: e.id,
					displayName: e.displayName,
					user: e.id,
					subname: e.email || undefined,
				}))"
				:user-select="true"
				:disabled="loading" />
			<NcButton :disabled="loading" :type="ButtonType.Primary" @click="newUser ? addUser(newUser.id) : undefined">
				<template #icon>
					<NcLoadingIcon v-if="loading" />
					<NcIconSvgWrapper v-else :svg="mdiContentSave" name="ContentSave" />
				</template>
				Add User
			</NcButton>
		</div>
		<ul>
			<NcListItemIcon
				v-for="user in usersRegistered"
				:id="user.id"
				:key="user.id"
				:name="user.displayName"
				:display-name="user.displayName"
				:subname="user.email || undefined">
				<NcActions>
					<NcActionButton :disabled="loading" @click="() => removeUser(user.id)">
						<template #icon>
							<NcIconSvgWrapper :svg="mdiDelete" name="Save" />
						</template>
						Delete
					</NcActionButton>
				</NcActions>
			</NcListItemIcon>
		</ul>
	</NcSettingsSection>
</template>
