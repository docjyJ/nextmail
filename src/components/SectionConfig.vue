<script setup lang="ts">
import { ref, defineProps, defineEmits, watch } from 'vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcButton, { ButtonType, ButtonNativeType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiContentSave from '@mdi/svg/svg/content-save.svg?raw'
import { useNextmailTranslate } from '~/composable'
import type { ServerConfig, ServerConfigForm } from '~/type'

const { t } = useNextmailTranslate()

const props = defineProps<{
  config: ServerConfig,
  loading: boolean
}>()

defineEmits<{
  (e: 'submit', value: ServerConfigForm): void
}>()

const form = ref<ServerConfigForm>({
	id: props.config.id,
	endpoint: props.config.endpoint,
	username: props.config.username,
	password: '',
})

watch(() => props.config, (config) => {
	form.value = {
		id: config.id,
		endpoint: config.endpoint,
		username: config.username,
		password: '',
	}
}, { deep: true })

</script>

<template>
	<NcSettingsSection
		:name="t('Stalwart server configuration')"
		:description="t('Configure the Stalwart server connection. The Salwart URL is http or https. Don\'t forget to include the port number if is not a standard port. Add api at the end of the URL.')">
		<form @submit.prevent="() => $emit('submit', form)">
			<NcTextField
				v-model="form.endpoint"
				:label="t('Stalwart API endpoint URL')"
				placeholder="https://mail.example.com:443/api"
				:disabled="props.loading" />
			<NcTextField
				v-model="form.username"
				:label="t('Username')"
				placeholder="admin"
				:disabled="props.loading" />
			<NcPasswordField
				v-model="form.password"
				:label="t('Password')"
				placeholder="****************"
				:disabled="props.loading" />

			<NcButton
				:native-type="ButtonNativeType.Submit"
				:disabled="props.loading"
				:type="ButtonType.Primary">
				<template #icon>
					<NcLoadingIcon v-if="props.loading" />
					<NcIconSvgWrapper v-else :svg="mdiContentSave" name="ContentSave" />
				</template>
				{{ t('Save') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<style scoped>
form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1em;
}

form > :first-child {
  grid-column: span 2;
}
</style>
