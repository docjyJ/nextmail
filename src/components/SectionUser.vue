<script setup lang="ts">
import { ref, defineProps, onMounted } from 'vue'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon.js'
import mdiContentSave from '@mdi/svg/svg/content-save.svg?raw'
import useServerUserList from '~/composable/useServerUserList'

const props = defineProps<{
  serverId: number
}>()

const { users, usersServer, loading, reload, addUser } = useServerUserList(props.serverId)
const newUserId = ref()

onMounted(() => {
	reload()
})
</script>

<template>
	<NcSettingsSection
		name="Server Users"
		description="Manage the users for the selected server.">
		<NcSelect
			v-model="newUserId"
			:options="usersServer.map(e => ({
				id: e.uid,
				displayName: e.displayName,
				isNoUser: false,
				user: e.uid,
			}))"
			:user-select="true" />
		<NcButton :disabled="loading" :type="ButtonType.Primary" @click="addUser(newUserId)">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<NcIconSvgWrapper v-else :svg="mdiContentSave" name="ContentSave" />
			</template>
			Add User
		</NcButton>
		{{ newUserId }}
		<NcButton :disabled="loading" :type="ButtonType.Primary" @click="reload">
			Reload Users
		</NcButton>
		<ul>
			<NcListItemIcon
				v-for="user in users"
				:id="user.uid"
				:key="user.uid"
				:name="user.displayName"
				:display-name="user.displayName" />
		</ul>
	</NcSettingsSection>
</template>
