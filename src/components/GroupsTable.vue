<script setup lang="ts">
import { useNextmailTranslate, useGroupsList } from '~/composable'
import GroupItem from '~/components/GroupItem.vue'

const { entries } = useGroupsList()

const { t } = useNextmailTranslate()

</script>

<template>
	<table>
		<thead>
			<tr>
				<th colspan="2" />
				<th style="padding: 1em">
					<strong>{{ t('Email') }}</strong>
				</th>
				<th style="padding: 1em">
					<strong>{{ t('Server') }}</strong>
				</th>
				<th />
			</tr>
		</thead>
		<tbody>
			<GroupItem v-for="group in entries" :key="group.id" :group="group" />
		</tbody>
	</table>
</template>
