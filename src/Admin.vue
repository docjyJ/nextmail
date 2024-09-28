<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getServerConfig, getStatus, type ServerConfig, setServers } from './api_controller'
import { NcNoteCard, NcSettingsSection } from '@nextcloud/vue'
import t from './l10n'
import ConfigForm from './component/ConfigForm.vue'

const server = ref<ServerConfig>({
	id: 0,
	endpoint: '',
	username: '',
	password: '',
})

const status = ref({
	type: 'info',
	text: t('Stalwart server not loaded'),
})

const disabled = ref(false)

const onLoad = async () => {
	if (!disabled.value) {
		disabled.value = true
		server.value = await getServerConfig(server.value.id) ?? server.value
		status.value = await getStatus(server.value.id) ?? status.value
		disabled.value = false
	}
}

const onSave = async (serverConfig: ServerConfig) => {
	if (!disabled.value) {
		disabled.value = true
		await setServers(serverConfig)
		status.value = await getStatus(server.value.id) ?? status.value
		disabled.value = false
	}
}

onMounted(onLoad)
</script>

<template>
	<div id="stalwart">
		<NcNoteCard :type="status.type" :text="status.text" />
		<NcSettingsSection :name="t('Stalwart server configuration')"
			:description="t('Configure the Stalwart server connection. The Salwart URL is http or https. Don\'t forget to include the port number if is not a standard port. Add api at the end of the URL.')"
			doc-url="#todo">
			<ConfigForm :server="server" :loading="disabled.value" @submit="onSave" />
		</NcSettingsSection>
	</div>
</template>

<style scoped>
</style>
