<script setup lang="ts">
import { ref, defineProps, defineEmits, watch } from 'vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import ContentSaveIcon from 'vue-material-design-icons/ContentSave.vue'
import { useStalwartTranslate } from '~/composable'
import type { ServerConfig, ServerConfigForm } from '~/type'

const { t } = useStalwartTranslate()

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
			native-type="submit"
			:disabled="props.loading"
			type="primary">
			<template #icon>
				<NcLoadingIcon v-if="props.loading" />
				<ContentSaveIcon v-else />
			</template>
			{{ t('Save') }}
		</NcButton>
	</form>
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
