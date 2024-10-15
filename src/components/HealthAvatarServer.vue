<script setup lang="ts">
import { defineProps } from 'vue'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import mdiAccessPoint from '@mdi/svg/svg/access-point.svg?raw'
import mdiFingerprintOff from '@mdi/svg/svg/fingerprint-off.svg?raw'
import mdiServerNetworkOff from '@mdi/svg/svg/server-network-off.svg?raw'
import mdiNetworkStrengthOffOutline from '@mdi/svg/svg/network-strength-off-outline.svg?raw'
import mdiTextBoxOutline from '@mdi/svg/svg/text-box-outline.svg?raw'
import type { MailServer } from '~/type'

defineProps<{
  server: MailServer
}>()

</script>

<template>
	<NcAvatar
		v-if="server.health === 'success'"
		style="background-color: rgba(var(--color-success-rgb), 0.1)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiAccessPoint"
				name="AccessPoint"
				style="color: var(--color-success); min-width: var(--size); min-height: var(--size)" />
		</template>
	</NcAvatar>
	<NcAvatar
		v-else-if="server.health === 'unauthorized'"
		style="background-color: rgba(var(--color-error-rgb), 0.1)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiFingerprintOff"
				name="FingerprintOff"
				style="color: var(--color-error); min-width: var(--size); min-height: var(--size)" />
		</template>
	</NcAvatar>
	<NcAvatar
		v-else-if="server.health === 'bad_network'"
		style="background-color: rgba(var(--color-error-rgb), 0.1)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiServerNetworkOff"
				name="ServerNetworkOff"
				style="color: var(--color-error); min-width: var(--size); min-height: var(--size)" />
		</template>
	</NcAvatar>
	<NcAvatar
		v-else-if="server.health === 'bad_server'"
		style="background-color: rgba(var(--color-error-rgb), 0.1)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiNetworkStrengthOffOutline"
				name="NetworkStrengthOffOutline"
				style="color: var(--color-error); min-width: var(--size); min-height: var(--size)" />
		</template>
	</NcAvatar>
	<NcAvatar
		v-else
		style="background-color: rgba(var(--color-warning-rgb), 0.1)">
		<template #icon>
			<NcIconSvgWrapper
				:svg="mdiTextBoxOutline"
				name="TextBoxOutline"
				style="color: var(--color-warning); min-width: var(--size); min-height: var(--size)" />
		</template>
	</NcAvatar>
</template>
