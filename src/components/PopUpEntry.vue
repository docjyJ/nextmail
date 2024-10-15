<script setup lang="ts">
import { defineEmits, defineProps } from 'vue'
import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiServer from '@mdi/svg/svg/server.svg?raw'
import mdiAccount from '@mdi/svg/svg/account.svg?raw'
import mdiCloseOctagon from '@mdi/svg/svg/close-octagon.svg?raw'
import SectionConfig from '~/components/SectionServer.vue'
import SectionStatus from '~/components/SectionStatus.vue'
import type { MailServer, MailServerForm } from '~/type'
import { useNextmailTranslate } from '~/composable'
import SectionDanger from '~/components/SectionDanger.vue'
import SectionUser from '~/components/SectionUser.vue'

defineProps<{
  server: MailServer | null,
  loading: boolean
}>()

defineEmits<{
  (e: 'edit', server: MailServerForm): void,
  (e: 'delete', id: string): void
  (e: 'create'): void
  (e: 'close'): void
}>()

const { t } = useNextmailTranslate()

</script>

<template>
	<NcAppSettingsDialog :open="server !== null"
		:show-navigation="true"
		:name="t('Server Management')"
		@update:open="e => e ? undefined : $emit('close')">
		<NcAppSettingsSection id="server" :name="t('Stalwart server configuration ')">
			<template #icon>
				<NcIconSvgWrapper :svg="mdiServer" :size="20" />
			</template>
			<SectionStatus v-if="server !== null" :server="server" />
			<SectionConfig v-if="server !== null"
				:server="server"
				:loading="loading"
				@submit="e => $emit('edit', e)" />
		</NcAppSettingsSection>
		<NcAppSettingsSection :name="t('Users')">
			<template #icon>
				<NcIconSvgWrapper id="account" :svg="mdiAccount" :size="20" />
			</template>
			<SectionUser v-if="server !== null" :server="server" />
		</NcAppSettingsSection>
		<NcAppSettingsSection id="danger" :name="t('Danger Zone')">
			<template #icon>
				<NcIconSvgWrapper :svg="mdiCloseOctagon" :size="20" />
			</template>
			<SectionDanger v-if="server !== null"
				:server="server"
				:loading="loading"
				@delete="e => $emit('delete', e)" />
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>
