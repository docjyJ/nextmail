<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import type { ServerConfig, ServerConfigForm } from '~/type'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiServerOutline from '@mdi/svg/svg/server-outline.svg?raw'
import { useStalwartTranslate } from '~/composable'
import SectionDanger from '~/components/SectionDanger.vue'
import SectionConfig from '~/components/SectionConfig.vue'
import SectionStatus from '~/components/SectionStatus.vue'
import SectionUser from '~/components/SectionUser.vue'

defineProps<{
  config: ServerConfig | null,
  loading: boolean
}>()

defineEmits<{
  (e: 'edit', config: ServerConfigForm): void,
  (e: 'delete', id: number): void
  (e: 'create'): void
}>()

const { t } = useStalwartTranslate()

</script>

<template>
	<div v-if="config !== null">
		<SectionStatus :config="config" />
		<SectionConfig :config="config" :loading="loading" @submit="e => $emit('edit', e)" />
		<SectionUser :server-id="config.id" />
		<SectionDanger :config="config" :loading="loading" @delete="e => $emit('delete', e)" />
	</div>
	<NcEmptyContent
		v-else
		:name="t('No mail server selected...')"
		:description="t('Chose a mail server from the list or create a new one by clicking the button below.')">
		<template #icon>
			<NcIconSvgWrapper :svg="mdiServerOutline" name="ServerOutline" />
		</template>
		<template #action>
			<NcButton :type="ButtonType.Primary" @click="() => $emit('create')">
				{{ t('Create a new config!') }}
			</NcButton>
		</template>
	</NcEmptyContent>
</template>
