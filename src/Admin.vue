<template>
	<div id="stalwart" class="section">
		<NcNoteCard :type="statu.type" :text="statu.text" />
		<NcSettingsSection :name="t('Stalwart server configuration')"
			:description="t('Configure the Stalwart server connection. The Salwart URL is http or https. Don\'t forget to include the port number if is not a standard port. Add api at the end of the URL.')"
			doc-url="#todo">
			<div class="line-inputs vertical">
				<NcTextField :value.sync="server.endpoint"
					:label="t('Stalwart API endpoint URL')"
					placeholder="https://mail.example.com:443/api"
					:disabled="disabled" />
				<div class="line-inputs">
					<NcTextField :value.sync="server.username"
						:label="t('Username')"
						placeholder="admin"
						:disabled="disabled" />

					<NcPasswordField :value.sync="server.password"
						:label="t('Password')"
						placeholder="****************"
						:disabled="disabled" />
				</div>
				<NcButton
					:disabled="disabled"
					type="primary"
					@click="onSave">
					<template #icon>
						<NcLoadingIcon v-if="disabled" />
						<ContentSaveIcon v-else />
					</template>
					{{ t('Save') }}
				</NcButton>
			</div>
		</NcSettingsSection>
	</div>
</template>

<script>
import { getServer, getStatu, setServers } from './network.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import ContentSaveIcon from 'vue-material-design-icons/ContentSave.vue'

export default {
	name: 'Admin',
	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcPasswordField,
		NcSettingsSection,
		NcTextField,
		ContentSaveIcon,
	},
	data() {
		return {
			server: {
				endpoint: '',
				username: '',
				password: '',
			},
			statu: {
				type: 'info',
				text: t('Stalwart server not loaded'),
			},
			id: 0,
			disabled: false,
		}
	},
	created() {
		this.onLoad()
	},
	methods: {
		async onLoad() {
			if (!this.disabled) {
				this.disabled = true
				this.server = await getServer(this.id) ?? this.server
				this.statu = await getStatu(this.id) ?? this.statu
				this.disabled = false
			}
		},
		async onSave() {
			if (!this.disabled) {
				this.disabled = true
				await setServers(this.id, this.server)
				this.statu = await getStatu(this.id) ?? this.statu
				this.disabled = false
			}
		},
	},
}
</script>

<style scoped>
.line-inputs {
  display: flex;
  align-items: baseline;
  width: 100%;
  gap: 16px;
}

.vertical {
  flex-direction: column;
  gap: 16px;
}
</style>
