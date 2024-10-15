<script setup lang="ts">
import { onMounted } from 'vue'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import HealthAvatarServer from '~/components/HealthAvatarServer.vue'
import PopUpEntry from '~/components/PopUpEntry.vue'
import { useNextmailTranslate, useServerList } from '~/composable'

const { entries, loading, active, to, edit, reload, remove, create } = useServerList()

onMounted(reload)

const { t } = useNextmailTranslate()

</script>

<template>
	<div id="nextmail">
		<NcSettingsSection :name="t('List of servers')"
			:description="t('Select a server to view or edit its configuration.')"
			doc-url="#todo">
			<NcButton :type="ButtonType.Primary" @click="() => create()">
				{{ t('Create a new config!') }}
			</NcButton>
			<ul>
				<NcListItem
					v-for="server in entries"
					:key="server.id"
					:name="/^[a-z0-9-]+:\/*([a-z0-9-.]+).*$/.exec(server.endpoint)?.at(1) || server.endpoint || '?????'"
					:subname="server.endpoint"
					@click="to(server.id)">
					<template #icon>
						<HealthAvatarServer :server="server" />
					</template>
				</NcListItem>
			</ul>
		</NcSettingsSection>
		<PopUpEntry
			:server="active"
			:loading="loading"
			@edit="edit"
			@delete="remove"
			@create="create"
			@close="() => to(null)" />
	</div>
</template>
