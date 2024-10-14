<script setup lang="ts">
import { onMounted } from 'vue'
import ServerSelection from '~/components/ViewSelection.vue'
import ServerView from '~/components/ViewServer.vue'

import { useServerList } from '~/composable'

const { entries, loading, active, to, edit, reload, remove, create } = useServerList()

onMounted(reload)

</script>

<template>
	<div id="nextmail">
		<ServerSelection :servers="entries" :server="active" @update:server="e => to(e? e.id : null)" />
		<ServerView
			:server="active"
			:loading="loading"
			@edit="edit"
			@delete="remove"
			@create="create" />
	</div>
</template>
