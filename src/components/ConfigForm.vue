<script setup lang="ts">
import { ref, defineProps, defineEmits } from 'vue'
import { NcTextField, NcButton, NcLoadingIcon, ContentSaveIcon, NcPasswordField, StatusNote } from '~/components'
import { useStalwartTranslate } from '~/composable'
import type { ServerConfig } from '~/type'

const { t } = useStalwartTranslate()

const props = defineProps<{
  config: ServerConfig,
  loading: boolean
}>()

defineEmits<{
  (e: 'submit', value: ServerConfig): void
}>()

const form = ref(props.config)
</script>

<template>
	<StatusNote :config-id="config.id" />
	<form @submit.prevent="() => $emit('submit', form)">
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
