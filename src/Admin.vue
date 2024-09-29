<script setup lang="ts">
import { onMounted } from 'vue'
import { ServerView, NcEmptyContent, NcButton, ServerOutlineIcon, ServerSelection } from '~/components'
import { useServerConfigList, useStalwartTranslate } from '~/composable'

const { servers, loading, active, to, edit, reload, remove, create } = useServerConfigList()

const { t } = useStalwartTranslate()

onMounted(reload)

</script>

<template>
	<div id="stalwart">
		<ServerSelection :active="active?.id ?? null" :servers="servers" @select="to" />
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
