<script setup lang="ts">
import { defineProps, ref } from 'vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import mdiPencil from '@mdi/svg/svg/pencil.svg?raw'
import mdiDelete from '@mdi/svg/svg/delete.svg?raw'
import mdiContentSave from '@mdi/svg/svg/content-save.svg?raw'
import type { MailServer, MailServerForm } from '~/type'
import { useNextmailTranslate, useServersList } from '~/composable'
import HealthAvatarServer from '~/components/HealthAvatarServer.vue'

const { t } = useNextmailTranslate()

const { server } = defineProps<{
  server: MailServer
}>()

const { loading, edit, remove } = useServersList()

const form = ref<MailServerForm | null>(null)

const startEditing = () => {
	if (form.value === null) {
		form.value = {
			id: server.id,
			endpoint: server.endpoint,
			username: server.username,
			password: '',
		}
	}
}

const endEditing = () => {
	if (form.value !== null) {
		edit(form.value).then(() => {
			form.value = null
		}).catch(() => {
			form.value = null
		})
	}
}

const removeServer = () => {
	remove(server.id)
}

</script>

<template>
	<tr>
		<td style="padding: 1em">
			<HealthAvatarServer :server="server" />
		</td>
		<td style="padding: 1em">
			<strong>{{ server.name }}</strong>
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ server.endpoint }}</span>
			<NcTextField v-else
				v-model="form.endpoint"
				:trailing-button-label="t('Submit')"
				:show-trailing-button="false"
				:disabled="loading"
				:label="t('Change Stalwart API endpoint')"
				autocapitalize="off"
				autocomplete="off"
				spellcheck="false" />
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ server.username }}</span>
			<NcTextField v-else
				v-model="form.username"
				:label="t('Username')"
				placeholder="admin" />
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">****************</span>
			<NcPasswordField v-else
				v-model="form.password"
				:label="t('Password')"
				placeholder="****************" />
		</td>
		<td style="padding: 1em">
			<NcActions :inline="1">
				<NcActionButton v-if="form === null" @click="startEditing">
					<template #icon>
						<NcIconSvgWrapper :svg="mdiPencil" />
					</template>
					{{ t('Edit') }}
				</NcActionButton>
				<NcActionButton v-else @click="endEditing">
					<template #icon>
						<NcIconSvgWrapper :svg="mdiContentSave" />
					</template>
					{{ t('Save') }}
				</NcActionButton>
				<NcActionButton @click="removeServer">
					<template #icon>
						<NcIconSvgWrapper :svg="mdiDelete" name="Delete" />
					</template>
					{{ t('Delete') }}
				</NcActionButton>
			</NcActions>
		</td>
	</tr>
</template>
