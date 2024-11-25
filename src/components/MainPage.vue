<script setup lang="ts">
import { ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiServer from '@mdi/svg/svg/server.svg?raw'
import mdiAccount from '@mdi/svg/svg/account.svg?raw'
import mdiGroup from '@mdi/svg/svg/group.svg?raw'
import ServerTable from '~/components/ServersTable.vue'
import { useNextmailTranslate } from '~/composable'
import UsersTable from '~/components/UsersTable.vue'
import GroupsTable from '~/components/GroupsTable.vue'

const { t } = useNextmailTranslate()

const tab = ref<string>('server')

</script>

<template>
	<div style="display: flex; flex-direction: column; align-items: stretch;">
		<div style="display: inline-flex; justify-content: center; padding: 1em;">
			<NcCheckboxRadioSwitch
				v-model="tab"
				:button-variant="true"
				value="server"
				name="tab_radio"
				type="radio"
				button-variant-grouped="horizontal">
				{{ t('Server') }}
				<template #icon>
					<NcIconSvgWrapper :svg="mdiServer" :size="20" />
				</template>
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				v-model="tab"
				:button-variant="true"
				value="user"
				name="tab_radio"
				type="radio"
				button-variant-grouped="horizontal">
				{{ t('Users') }}
				<template #icon>
					<NcIconSvgWrapper :svg="mdiAccount" :size="20" />
				</template>
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				v-model="tab"
				:button-variant="true"
				value="group"
				name="tab_radio"
				type="radio"
				button-variant-grouped="horizontal">
				{{ t('Groups') }}
				<template #icon>
					<NcIconSvgWrapper :svg="mdiGroup" :size="20" />
				</template>
			</NcCheckboxRadioSwitch>
		</div>
		<ServerTable v-if="tab === 'server'" />
		<UsersTable v-else-if="tab === 'user'" />
		<GroupsTable v-else-if="tab === 'group'" />
	</div>
</template>
