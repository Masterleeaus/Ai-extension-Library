<template>
  <div class="go-dashboard">
    <div class="status-bar" :style="{ background: config.theme.gradient }">
      <div class="clock-status">
        <button @click="toggleClockIn" class="clock-btn" :class="{ active: clockedIn }">
          {{ clockedIn ? '⏹ Clock Out' : '▶ Clock In' }}
        </button>
      </div>
      <div v-if="currentLocation" class="location-display">
        📍 {{ currentLocation.address }}
      </div>
    </div>

    <div class="jobs-list">
      <h2>Today's Jobs</h2>
      <div 
        v-for="job in todayJobs" 
        :key="job.id"
        class="job-card"
        @click="selectJob(job)"
        :style="{ borderLeftColor: config.theme.primary }"
      >
        <div class="job-header">
          <div class="time">{{ formatTime(job.scheduled_at) }}</div>
          <div class="status" :class="job.status">{{ job.status }}</div>
        </div>
        <div class="customer">{{ job.customer_name }}</div>
        <div class="address">{{ job.address }}</div>
        <div class="service-type">{{ job.service_type }}</div>
      </div>
    </div>

    <!-- Selected Job Detail -->
    <div v-if="selectedJob" class="job-detail-sheet">
      <div class="sheet-header">
        <h2>{{ selectedJob.customer_name }}</h2>
        <button @click="selectedJob = null" class="close-btn">✕</button>
      </div>

      <div class="job-info">
        <div class="info-row">
          <span class="label">Time:</span>
          <span>{{ formatTime(selectedJob.scheduled_at) }}</span>
        </div>
        <div class="info-row">
          <span class="label">Address:</span>
          <span>{{ selectedJob.address }}</span>
        </div>
        <div class="info-row">
          <span class="label">Service:</span>
          <span>{{ selectedJob.service_type }}</span>
        </div>
        <div class="info-row">
          <span class="label">Quote:</span>
          <span>${{ selectedJob.quote }}</span>
        </div>
      </div>

      <div class="checklist">
        <h3>Work Checklist</h3>
        <label v-for="item in selectedJob.checklist" :key="item.id" class="checklist-item">
          <input type="checkbox" v-model="item.completed" />
          <span>{{ item.task }}</span>
        </label>
      </div>

      <div class="action-buttons">
        <button @click="navigateTo(selectedJob)" class="action-btn">📍 Navigate</button>
        <button @click="startPhoto" class="action-btn">📷 Photos</button>
        <button @click="getSignature" class="action-btn">✍️ Signature</button>
        <button @click="markComplete" class="action-btn primary">✅ Complete</button>
      </div>

      <!-- Embedded Chatbot for this job -->
      <ChatbotWidget 
        :app="'titan-go'"
        :context="{ job_id: selectedJob.id, customer: selectedJob.customer_name }"
        :prompts="['Troubleshoot issue', 'Get customer help', 'Extend time']"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import config from './config.json'
import ChatbotWidget from '../components/ChatbotWidget.vue'

const todayJobs = ref<any[]>([])
const selectedJob = ref<any>(null)
const clockedIn = ref(false)
const currentLocation = ref<any>(null)

onMounted(async () => {
  // Fetch today's jobs
  const response = await fetch('/api/v2/titan-go/jobs/today')
  if (response.ok) {
    todayJobs.value = await response.json()
  }

  // Start location tracking
  if ('geolocation' in navigator) {
    navigator.geolocation.watchPosition((pos) => {
      currentLocation.value = {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        address: `${pos.coords.latitude}, ${pos.coords.longitude}`,
      }
    })
  }
})

function formatTime(date: string): string {
  return new Date(date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

function selectJob(job: any) {
  selectedJob.value = { ...job, checklist: job.checklist || [] }
}

function toggleClockIn() {
  clockedIn.value = !clockedIn.value
  fetch('/api/v2/titan-go/clock-toggle', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ clocked_in: clockedIn.value }),
  })
}

function navigateTo(job: any) {
  const url = `https://maps.google.com/?q=${encodeURIComponent(job.address)}`
  window.open(url, '_blank')
}

function startPhoto() {
  // Camera capture
}

function getSignature() {
  // Signature pad modal
}

async function markComplete() {
  const response = await fetch(`/api/v2/titan-go/job/${selectedJob.value.id}/complete`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      checklist: selectedJob.value.checklist,
      location: currentLocation.value,
    }),
  })

  if (response.ok) {
    todayJobs.value = todayJobs.value.filter(j => j.id !== selectedJob.value.id)
    selectedJob.value = null
  }
}
</script>

<style scoped lang="scss">
.go-dashboard {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #0f172a;
  color: white;
}

.status-bar {
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  .clock-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid white;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;

    &.active {
      background: white;
      color: #00d4ff;
    }
  }

  .location-display {
    font-size: 12px;
    opacity: 0.9;
  }
}

.jobs-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;

  h2 {
    margin: 0 0 12px 0;
    font-size: 16px;
  }
}

.job-card {
  background: rgba(255, 255, 255, 0.05);
  border-left: 4px solid;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    background: rgba(255, 255, 255, 0.1);
  }

  .job-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;

    .time {
      font-weight: bold;
    }

    .status {
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 3px;
      background: rgba(255, 255, 255, 0.1);

      &.scheduled { background: rgba(59, 130, 246, 0.3); }
      &.in-progress { background: rgba(245, 158, 11, 0.3); }
      &.completed { background: rgba(16, 185, 129, 0.3); }
    }
  }

  .customer {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .address {
    font-size: 12px;
    opacity: 0.7;
    margin-bottom: 4px;
  }

  .service-type {
    font-size: 11px;
    opacity: 0.6;
  }
}

.job-detail-sheet {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #1e293b;
  border-radius: 12px 12px 0 0;
  padding: 20px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.3);

  .sheet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    h2 {
      margin: 0;
    }

    .close-btn {
      background: none;
      border: none;
      color: white;
      font-size: 20px;
      cursor: pointer;
    }
  }

  .job-info {
    background: rgba(255, 255, 255, 0.05);
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 16px;

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;

      .label {
        opacity: 0.7;
      }
    }
  }

  .checklist {
    margin-bottom: 16px;

    h3 {
      margin: 0 0 12px 0;
      font-size: 14px;
    }

    .checklist-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px;
      cursor: pointer;

      input {
        cursor: pointer;
      }
    }
  }

  .action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;

    .action-btn {
      padding: 12px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;

      &.primary {
        background: #00d4ff;
        color: #0f172a;
        grid-column: 1 / -1;
      }

      &:hover {
        background: rgba(255, 255, 255, 0.2);
      }
    }
  }
}
</style>
