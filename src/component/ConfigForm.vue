<script setup lang="ts">
import { ref, defineProps, defineEmits } from 'vue'
import { NcTextField, NcPasswordField, NcButton } from '@nextcloud/vue'
import ContentSaveIcon from 'vue-material-design-icons/ContentSave.vue'
import type { ServerConfig } from '../api_controller'
import t from '../l10n'

const props = defineProps<{
  server: ServerConfig,
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'submit', value: ServerConfig): void
}>()

const form = ref({ ...props.server })

const handleSubmit = () => {
	emit('submit', form.value)
}
</script>

<template>
	<form @submit.prevent="handleSubmit">
		<NcTextField v-model="form.endpoint"
			:label="t('Stalwart API endpoint URL')"
			placeholder="https://mail.example.com:443/api"
			:disabled="props.loading" />
		<NcTextField v-model="form.username"
			:label="t('Username')"
			placeholder="admin"
			:disabled="props.loading" />
		<NcPasswordField v-model="form.password"
			:label="t('Password')"
			placeholder="****************"
			:disabled="props.loading" />

		<NcButton native-type="submit"
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
