<script setup lang="ts">
import { defineProps, defineModel } from 'vue'
import type { MailServer } from '~/type'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import ListItemConfig from '~/components/ListItemConfig.vue'

defineProps<{
  configList: MailServer[],
}>()
const config = defineModel<MailServer | null>('config')

</script>

<template>
	<NcSelect
		v-model="config"
		:no-wrap="false"
		:options="configList"
		label="endpoint">
		<template #option="configSelected">
			<ListItemConfig :config="configSelected" />
		</template>
		<template #selected-option="configSelected">
			<ListItemConfig :config="configSelected" />
		</template>
	</NcSelect>
</template>
