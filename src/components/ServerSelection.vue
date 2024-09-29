<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import { NcListItem, NcAvatar } from '~/components'
import type { ServerConfig } from '~/type'

defineProps<{
  servers: ServerConfig[],
  active: number | null,
}>()

defineEmits<{
  (e: 'select', id: number | null): void,
}>()

</script>

<template>
	<ul style="width: 350px;">
		<NcListItem v-for="server in servers"
			:key="server.id"
			:name="server.endpoint.replace(/^.*:\/\//, '').replace(/\/.*$/, '') || 'untitled'"
			:active="server.id === active"
			compact
			@click="() => $emit('select', server.id === active ? null : server.id)">
			<template #icon>
				<NcAvatar :display-name="server.id.toString()" :is-no-user="true" />
			</template>
		</NcListItem>
	</ul>
</template>
