<script setup lang="ts">
import { onMounted } from 'vue'
import ServerSelection from '~/components/ViewSelection.vue'
import ServerView from '~/components/ViewServer.vue'

import { useServerConfigList } from '~/composable'

const { servers, loading, active, to, edit, reload, remove, create } = useServerConfigList()

onMounted(reload)

</script>

<template>
	<div id="stalwart">
		<ServerSelection :config-list="servers" :config="active" @update:config="e => to(e? e.id : null)" />
		<ServerView
			:config="active"
			:loading="loading"
			@edit="edit"
			@delete="remove"
			@create="create" />
	</div>
</template>
