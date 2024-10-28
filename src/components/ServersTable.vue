<script setup lang="ts">
import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiPlus from '@mdi/svg/svg/plus.svg?raw'
import ServerItem from '~/components/ServerItem.vue'
import { useNextmailTranslate, useServersList } from '~/composable'

const { entries, create, loading } = useServersList()

const { t } = useNextmailTranslate()

const addServer = () => {
	create()
}

</script>

<template>
	<table>
		<thead>
			<tr>
				<th colspan="2" style="padding: 1em">
					<NcButton
						:type="ButtonType.Primary"
						:disabled="loading"
						@click="addServer">
						<template #icon>
							<NcIconSvgWrapper :svg="mdiPlus" :size="20" />
						</template>
						{{ t('Add new mail server') }}
					</NcButton>
				</th>
				<th style="padding: 1em">
					<strong>{{ t('Stalwart API endpoint') }}</strong>
				</th>
				<th style="padding: 1em">
					<strong>{{ t('Username') }}</strong>
				</th>
				<th style="padding: 1em">
					<strong>{{ t('Password') }}</strong>
				</th>
				<th />
			</tr>
		</thead>
		<tbody>
			<ServerItem v-for="server in entries" :key="server.id" :server="server" />
		</tbody>
	</table>
</template>
