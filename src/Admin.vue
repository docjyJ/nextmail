<script setup lang="ts">
import { onMounted } from 'vue'
// import { ServerView, NcEmptyContent, NcButton, ServerOutlineIcon, ServerSelection } from '~/components'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import ServerOutlineIcon from 'vue-material-design-icons/ServerOutline.vue'
import ServerSelection from '~/components/ServerSelection.vue'
import ServerView from '~/components/ServerView.vue'

import { useServerConfigList, useStalwartTranslate } from '~/composable'

const { servers, loading, active, to, edit, reload, remove, create } = useServerConfigList()

const { t } = useStalwartTranslate()

onMounted(reload)

</script>

<template>
	<div id="stalwart">
		<ServerSelection :config-list="servers" :config="active" @update:config="e => to(e? e.id : null)" />
		<ServerView v-if="active !== null"
			:config="active"
			:loading="loading"
			@edit="edit"
			@delete="remove" />
		<NcEmptyContent
			v-else
			:name="t('No mail server selected...')"
			:description="t('Chose a mail server from the list or create a new one by clicking the button below.')">
			<template #icon>
				<ServerOutlineIcon />
			</template>
			<template #desc />
			<template #action>
				<NcButton type="primary" @click="create">
					{{ t('Create a new config!') }}
				</NcButton>
			</template>
		</NcEmptyContent>
	</div>
</template>
