<script setup lang="ts">
import { useNextmailTranslate, useUsersList } from '~/composable'
import UserItem from '~/components/UserItem.vue'

const { entries } = useUsersList()

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
				<th style="padding: 1em">
					<strong>{{ t('Role') }}</strong>
				</th>
				<th style="padding: 1em">
					<strong>{{ t('Quota') }}</strong>
				</th>
				<th />
			</tr>
		</thead>
		<tbody>
			<UserItem v-for="user in entries" :key="user.id" :user="user" />
		</tbody>
	</table>
</template>
