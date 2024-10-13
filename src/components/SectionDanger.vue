<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiDelete from '@mdi/svg/svg/delete.svg?raw'
import { useNextmailTranslate } from '~/composable'
import type { ServerConfig } from '~/type'

defineProps<{
  config: ServerConfig,
  loading: boolean
}>()

defineEmits<{
  (e: 'delete', id: string): void
}>()

const { t } = useNextmailTranslate()

</script>

<template>
	<NcSettingsSection
		:name="t('Danger Zone')"
		:description="t('This is the danger zone. Be careful with the actions you take here.')">
		<NcButton :disabled="loading" :type="ButtonType.Error" @click="() => $emit('delete', config.id)">
			<template #icon>
				<NcIconSvgWrapper :svg="mdiDelete" name="Delete" />
			</template>
			{{ t('Delete Server') }}
		</NcButton>
	</NcSettingsSection>
</template>
