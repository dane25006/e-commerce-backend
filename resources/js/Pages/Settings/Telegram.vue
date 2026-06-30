<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

const linked = ref(false)
const telegramUsername = ref<string | null>(null)
const notificationsEnabled = ref(false)
const linkCode = ref<string | null>(null)
const botUsername = ref('')
const deepLink = ref('')
const loading = ref(true)
const toggling = ref(false)
const sending = ref(false)
const message = ref('')

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/telegram/status')
    linked.value = data.linked
    telegramUsername.value = data.telegram_username
    notificationsEnabled.value = data.notifications_enabled
  } catch {
    //
  } finally {
    loading.value = false
  }
})

async function generateLink() {
  try {
    const { data } = await axios.get('/api/telegram/link')
    linkCode.value = data.link_code
    botUsername.value = data.bot_username
    deepLink.value = data.deep_link
    message.value = ''
  } catch {
    message.value = 'Failed to generate link'
  }
}

async function toggleNotifications() {
  toggling.value = true
  try {
    const { data } = await axios.post('/api/telegram/toggle-notifications', {
      enabled: !notificationsEnabled.value,
    })
    notificationsEnabled.value = data.notifications_enabled
  } catch {
    message.value = 'Failed to update notification settings'
  } finally {
    toggling.value = false
  }
}

async function sendTest() {
  sending.value = true
  try {
    await axios.post('/api/telegram/send-test')
    message.value = 'Test message sent!'
  } catch {
    message.value = 'Failed to send test message'
  } finally {
    sending.value = false
  }
}

async function unlink() {
  if (!confirm('Unlink Telegram from your account?')) return
  try {
    await axios.post('/api/telegram/unlink')
    linked.value = false
    telegramUsername.value = null
    notificationsEnabled.value = false
    linkCode.value = null
    message.value = 'Telegram unlinked'
  } catch {
    message.value = 'Failed to unlink'
  }
}
</script>

<template>
  <div class="telegram-settings">
    <h2>Telegram Notifications</h2>
    <p class="subtitle">Receive order updates directly in Telegram</p>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="!linked" class="not-linked">
      <p>Link your account to receive order notifications via Telegram.</p>
      <button class="btn btn-primary" @click="generateLink" v-if="!linkCode">
        Generate Link Code
      </button>
      <div v-if="linkCode" class="link-code-box">
        <p>Open Telegram and start a chat with <strong>@{{ botUsername }}</strong>, then send:</p>
        <code>/start {{ linkCode }}</code>
        <p class="or">Or click the link below:</p>
        <a :href="deepLink" target="_blank" class="btn btn-success">
          Open Telegram
        </a>
      </div>
    </div>

    <div v-else class="linked">
      <div class="status-card">
        <span class="badge badge-success">Connected</span>
        <p v-if="telegramUsername">@{{ telegramUsername }}</p>
      </div>

      <div class="setting-row">
        <label>Notifications</label>
        <button
          class="btn"
          :class="notificationsEnabled ? 'btn-success' : 'btn-secondary'"
          :disabled="toggling"
          @click="toggleNotifications"
        >
          {{ notificationsEnabled ? 'Enabled' : 'Disabled' }}
        </button>
      </div>

      <button class="btn btn-outline" :disabled="sending" @click="sendTest">
        {{ sending ? 'Sending...' : 'Send Test Notification' }}
      </button>

      <button class="btn btn-danger" @click="unlink">Unlink Telegram</button>
    </div>

    <p v-if="message" class="message">{{ message }}</p>
  </div>
</template>

<style scoped>
.telegram-settings {
  max-width: 500px;
}
.subtitle {
  color: #6b7280;
  margin-bottom: 1.5rem;
}
.loading {
  color: #6b7280;
}
.not-linked p {
  margin-bottom: 1rem;
}
.link-code-box {
  margin-top: 1rem;
}
.link-code-box code {
  display: block;
  background: #f3f4f6;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  margin: 0.5rem 0;
  font-size: 1.1rem;
}
.or {
  margin: 0.75rem 0 0.25rem;
}
.status-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}
.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.875rem;
  font-weight: 600;
}
.badge-success {
  background: #d1fae5;
  color: #065f46;
}
.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.btn {
  padding: 0.5rem 1.25rem;
  border-radius: 6px;
  border: none;
  font-size: 0.875rem;
  cursor: pointer;
  display: inline-block;
  text-decoration: none;
}
.btn-primary { background: #2563eb; color: #fff; }
.btn-success { background: #059669; color: #fff; }
.btn-secondary { background: #9ca3af; color: #fff; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; }
.btn-danger { background: #dc2626; color: #fff; margin-top: 1rem; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.message {
  margin-top: 1rem;
  padding: 0.5rem 0.75rem;
  background: #f3f4f6;
  border-radius: 6px;
}
</style>
