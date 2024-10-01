<script setup lang="ts">
import { defineProps } from 'vue'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiAccessPoint from '@mdi/svg/svg/access-point.svg?raw'
import mdiFingerprintOff from '@mdi/svg/svg/fingerprint-off.svg?raw'
import mdiServerNetworkOff from '@mdi/svg/svg/server-network-off.svg?raw'
import mdiNetworkStrengthOffOutline from '@mdi/svg/svg/network-strength-off-outline.svg?raw'
import mdiTextBoxOutline from '@mdi/svg/svg/text-box-outline.svg?raw'
import { useStalwartTranslate } from '~/composable'
import type { ServerConfig } from '~/type'

defineProps<{
  config: ServerConfig
}>()

const { t } = useStalwartTranslate()

</script>

<template>
	<NcNoteCard
		v-if="config.health === 0"
		type="success"
		style="margin: calc(var(--default-grid-baseline) * 7)">
		<template #icon>
			<NcIconSvgWrapper :svg="mdiAccessPoint"
				name="AccessPoint"
				style="color: var(--note-theme)" />
		</template>
		<p style="font-size: var(--note-card-icon-size); font-weight: 600;">
			{{ t('Connected and healthy') }}
		</p>
	</NcNoteCard>
	<NcNoteCard
		v-else-if="config.health === 1"
		type="warning"
		style="margin: calc(var(--default-grid-baseline) * 7)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiAccessPoint"
				name="AccessPoint"
				style="color: var(--note-theme)" />
		</template>
		<p style="font-size: var(--note-card-icon-size); font-weight: 600;">
			{{ t('The account is not administrator') }}
		</p>
		<p>{{ t('Use an administrator account to prevent any issues with sending configuration') }}</p>
	</NcNoteCard>
	<NcNoteCard
		v-else-if="config.health === 2"
		type="error"
		style="margin: calc(var(--default-grid-baseline) * 7)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiFingerprintOff"
				name="FingerprintOff"
				style="color: var(--note-theme)" />
		</template>
		<p style="font-size: var(--note-card-icon-size); font-weight: 600;">
			{{ t('Invalid credentials') }}
		</p>
		<p>{{ t('The credentials you provided are invalid. Please check them and try again.') }}</p>
	</NcNoteCard>
	<NcNoteCard
		v-else-if="config.health === 3"
		type="error"
		style="margin: calc(var(--default-grid-baseline) * 7)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiServerNetworkOff"
				name="ServerNetworkOff"
				style="color: var(--note-theme)" />
		</template>
		<p style="font-size: var(--note-card-icon-size); font-weight: 600;">
			{{ t('The server failed to respond') }}
		</p>
		<p>{{ t('The server respond with an error, check the server status and the endpoint you provided.') }}</p>
	</NcNoteCard>
	<NcNoteCard
		v-else-if="config.health === 4"
		type="error"
		style="margin: calc(var(--default-grid-baseline) * 7)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiNetworkStrengthOffOutline"
				name="NetworkStrengthOffOutline"
				style="color: var(--note-theme)" />
		</template>
		<p style="font-size: var(--note-card-icon-size); font-weight: 600;">
			{{ t('The server is not reachable.') }}
		</p>
		<p>
			{{
				t('Check the server status and if your nextcloud instance can reach the server at the endpoint you provided.')
			}}
		</p>
	</NcNoteCard>
	<NcNoteCard
		v-else
		type="error"
		style="margin: calc(var(--default-grid-baseline) * 7)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiTextBoxOutline"
				name="TextBoxOutline"
				style="color: var(--note-theme)" />
		</template>
		<p style="font-size: var(--note-card-icon-size); font-weight: 600;">
			{{ t('The configuration is invalid') }}
		</p>
		<p>
			{{
				t('The configuration you provided is invalid. Please check it and try again. (tips: the endpoint should be a valid URL with \'/api\' path)')
			}}
		</p>
	</NcNoteCard>
</template>
