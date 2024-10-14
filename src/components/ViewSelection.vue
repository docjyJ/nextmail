<script setup lang="ts">
import { defineProps, defineModel } from 'vue'
import type { MailServer } from '~/type'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import ListItemConfig from '~/components/ListItemServer.vue'

defineProps<{
  servers: MailServer[],
}>()
const server = defineModel<MailServer | null>('server')

</script>

<template>
	<NcSelect
		v-model="server"
		:no-wrap="false"
		:options="servers"
		label="endpoint">
		<template #option="selected">
			<ListItemConfig :server="selected" />
		</template>
		<template #selected-option="selected">
			<ListItemConfig :server="selected" />
		</template>
	</NcSelect>
</template>
