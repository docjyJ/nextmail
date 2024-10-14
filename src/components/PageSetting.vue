<script setup lang="ts">
import { onMounted } from 'vue'
import ServerSelection from '~/components/ViewSelection.vue'
import ServerView from '~/components/ViewServer.vue'

import { useServerList } from '~/composable'

const { servers, loading, active, to, edit, reload, remove, create } = useServerList()

onMounted(reload)

</script>

<template>
	<div id="nextmail">
		<ServerSelection :config-list="servers" :config="active" @update:config="e => to(e? e.id : null)" />
		<ServerView
			:config="active"
			:loading="loading"
			@edit="edit"
			@delete="remove"
			@create="create" />
	</div>
</template>
