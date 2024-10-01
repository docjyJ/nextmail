<script setup lang="ts">
import { defineProps, defineModel } from 'vue'
import type { ServerConfig } from '~/type'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import ListItemConfig from '~/components/ListItemConfig.vue'

defineProps<{
  configList: ServerConfig[],
}>()
const config = defineModel<ServerConfig | null>('config')

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
