<script setup lang="ts">
import { computed, defineProps, ref } from 'vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import mdiPencil from '@mdi/svg/svg/pencil.svg?raw'
import mdiContentSave from '@mdi/svg/svg/content-save.svg?raw'
import type { MailGroup, MailGroupForm } from '~/type'
import { useNextmailTranslate, useServersList, useGroupsList } from '~/composable'

const { t } = useNextmailTranslate()

const { group } = defineProps<{
  group: MailGroup
}>()

const { loading, edit } = useGroupsList()
const { find, entries } = useServersList()

const form = ref<MailGroupForm | null>(null)

const startEditing = () => {
	if (form.value === null) {
		form.value = {
			id: group.id,
			email: group.email ?? '',
			server_id: group.server_id,
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

const serverOptions = computed(() => {
	return entries.value.map(s => ({ id: s.id, label: s.name }))
})

</script>

<template>
	<tr>
		<td style="padding: 1em">
			<NcAvatar :is-no-user="true" :display-name="group.name" />
		</td>
		<td style="padding: 1em">
			<strong>{{ group.name }}</strong>
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ group.email }}</span>
			<NcTextField v-else
				v-model="form.email"
				:disabled="loading"
				:label="t('Email')"
				placeholder="admin@example.com"
				type="email" />
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ find(group.server_id)?.name ?? '' }}</span>
			<NcSelect v-else
				v-model="form.server_id"
				:options="serverOptions"
				:clearable="true"
				:reduce="o => o.id"
				:disable="loading" />
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
			</NcActions>
		</td>
	</tr>
</template>
