<script setup lang="ts">
import { watch, defineProps } from 'vue'
import { NcNoteCard, NcLoadingIcon } from '~/components'
import { useServerStatus } from '~/composable'

const props = defineProps<{
  configId: number
}>()

const { status, reload } = useServerStatus(props.configId)

watch(
	() => props.configId,
	() => reload(),
	{ immediate: true },
)

</script>

<template>
	<NcNoteCard v-if="status === null" type="info">
		<template #icon>
			<NcLoadingIcon />
		</template>
		Loading server status...
	</NcNoteCard>
	<NcNoteCard v-else :type="status.type">
		{{ status.text }}
	</NcNoteCard>
</template>
