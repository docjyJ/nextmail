<script setup lang="ts">
import { computed, defineProps, ref } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import mdiPencil from '@mdi/svg/svg/pencil.svg?raw'
import mdiContentSave from '@mdi/svg/svg/content-save.svg?raw'
import type { MailUser, MailUserForm } from '~/type'
import { useNextmailTranslate, useServersList, useUsersList } from '~/composable'
import { formatFileSize, parseFileSize } from '@nextcloud/files'

const { t } = useNextmailTranslate()

const { user } = defineProps<{
  user: MailUser
}>()

const { loading, edit } = useUsersList()
const { find, entries } = useServersList()

const form = ref<MailUserForm | null>(null)

const startEditing = () => {
	if (form.value === null) {
		form.value = {
			id: user.id,
			admin: user.admin,
			quota: user.quota,
			server_id: user.server_id,
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

const validateQuota = (input: { label: string } | string) => {
	if (typeof input === 'object') {
		input = input.label
	}
	const validQuota = parseFileSize(input, true)
	return validQuota === null ? null : { id: validQuota, label: formatFileSize(validQuota) }
}

const serverOptions = computed(() => {
	return entries.value.map(s => ({ id: s.id, label: s.name }))
})

const quotaOptions = [
	{ id: null, label: t('Unlimited') },
	validateQuota('1 GB')!,
	validateQuota('5 GB')!,
	validateQuota('10 GB')!,
]

</script>

<template>
	<tr>
		<td style="padding: 1em">
			<NcAvatar :user="user.id" :display-name="user.name" />
		</td>
		<td style="padding: 1em">
			<strong>{{ user.name }}</strong>
		</td>
		<td style="padding: 1em">
			<span>{{ user.email }}</span>
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ find(user.server_id)?.name ?? '' }}</span>
			<NcSelect v-else
				v-model="form.server_id"
				:options="serverOptions"
				:clearable="true"
				:reduce="o => o.id"
				:disable="loading" />
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ user.admin ? t('Administrator') : t('Individual') }}</span>
			<NcCheckboxRadioSwitch v-else
				v-model="form.admin"
				type="switch"
				:disable="loading">
				{{ form.admin ? t('Administrator') : t('Individual') }}
			</NcCheckboxRadioSwitch>
		</td>
		<td style="padding: 1em">
			<span v-if="form === null">{{ user.quota !== null ? formatFileSize(user.quota) : t('Unlimited') }}</span>
			<NcSelect v-else
				v-model="form.quota"
				:options="quotaOptions"
				:clearable="true"
				:create-option="validateQuota"
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
