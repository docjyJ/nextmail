<script setup lang="ts">
import { defineProps } from 'vue'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import AccessPointIcon from 'vue-material-design-icons/AccessPoint.vue'
import FingerprintOffIcon from 'vue-material-design-icons/FingerprintOff.vue'
import NetworkStrengthOffOutlineIcon from 'vue-material-design-icons/NetworkStrengthOffOutline.vue'
import ServerNetworkOffIcon from 'vue-material-design-icons/ServerNetworkOff.vue'
import TextBoxOutlineIcon from 'vue-material-design-icons/TextBoxOutline.vue'
import type { ServerConfig } from '~/type'
import { useStalwartTranslate } from '~/composable'

defineProps<{
  config: ServerConfig
}>()

const { t } = useStalwartTranslate()

</script>

<template>
	<div>
		<NcAvatar :display-name="config.id.toString()" :no-user="true" />
		<p>{{ /^[a-z0-9-]+:\/*([a-z0-9-.:]+).*$/.exec(config.endpoint)?.at(1) || config.endpoint || '?????' }}</p>
		<NcChip
			v-if="config.health === 0"
			:text="t('Connected and healthy')"
			type="primary"
			style="background-color: green"
			no-close>
			<template #icon>
				<AccessPointIcon :size="20" />
			</template>
		</NcChip>
		<NcChip
			v-else-if="config.health === 1"
			:text="t('The account is not administrator')"
			type="primary"
			style="background-color: orange"
			no-close>
			<template #icon>
				<AccessPointIcon :size="20" />
			</template>
		</NcChip>
		<NcChip
			v-else-if="config.health === 2"
			:text="t('Invalid credentials')"
			type="primary"
			style="background-color: darkred"
			no-close>
			<template #icon>
				<FingerprintOffIcon :size="20" />
			</template>
		</NcChip>
		<NcChip
			v-else-if="config.health === 3"
			:text="t('The server failed to respond')"
			type="primary"
			style="background-color: darkred"
			no-close>
			<template #icon>
				<ServerNetworkOffIcon :size="20" />
			</template>
		</NcChip>
		<NcChip
			v-else-if="config.health === 4"
			:text="t('The server is not reachable')"
			type="primary"
			style="background-color: darkred"
			no-close>
			<template #icon>
				<NetworkStrengthOffOutlineIcon :size="20" />
			</template>
		</NcChip>
		<NcChip
			v-else-if="config.health === 5"
			:text="t('The configuration is invalid')"
			type="primary"
			style="background-color: dimgrey"
			no-close>
			<template #icon>
				<TextBoxOutlineIcon :size="20" />
			</template>
		</NcChip>
	</div>
</template>

<style scoped>
div {
  display: grid;
  grid-template-columns: min-content auto;
  gap: 0.2em;
}

div > :first-child {
  justify-self: stretch;
  grid-row: span 2;
  align-self: center;
}

div > p {
  margin: 0;
}
</style>
