<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiDelete from '@mdi/svg/svg/delete.svg?raw'
import { useNextmailTranslate } from '~/composable'
import type { MailServer } from '~/type'

defineProps<{
  server: MailServer,
  loading: boolean
}>()

defineEmits<{
  (e: 'delete', id: string): void
}>()

const { t } = useNextmailTranslate()

</script>

<template>
	<div style="height: 100vh">
		<NcButton :disabled="loading" :type="ButtonType.Error" @click="() => $emit('delete', server.id)">
			<template #icon>
				<NcIconSvgWrapper :svg="mdiDelete" name="Delete" />
			</template>
			{{ t('Delete Server') }}
		</NcButton>
	</div>
</template>
