<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import { ConfigForm, NcButton, NcSettingsSection, DeleteIcon } from '~/components'
import { useStalwartTranslate } from '~/composable'
import type { ServerConfig } from '~/type'

defineProps<{
  config: ServerConfig,
  loading: boolean
}>()

const { t } = useStalwartTranslate()

defineEmits<{
  (e: 'edit', config: ServerConfig): void,
  (e: 'delete', id: number): void
}>()

</script>

<template>
	<div>
		<NcSettingsSection :name="t('Stalwart server configuration')"
			:description="t('Configure the Stalwart server connection. The Salwart URL is http or https. Don\'t forget to include the port number if is not a standard port. Add api at the end of the URL.')">
			<ConfigForm :config="config" :loading="loading" @submit="e => $emit('edit', e)" />
		</NcSettingsSection>
		<NcSettingsSection
			:name="t('Danger Zone')"
			:description="t('This is the danger zone. Be careful with the actions you take here.')">
			<NcButton :disabled="loading" type="error" @click="() => $emit('delete', config.id)">
				<template #icon>
					<DeleteIcon />
				</template>
				{{ t('Delete Server') }}
			</NcButton>
		</NcSettingsSection>
	</div>
</template>
