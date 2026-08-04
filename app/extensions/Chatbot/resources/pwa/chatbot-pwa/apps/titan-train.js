(() => {
  'use strict';

  const meta = name => document.querySelector(`meta[name="${name}"]`)?.content || null;

  class TitanTrainClient {
    constructor(options = {}) {
      this.baseUrl = String(options.baseUrl || window.location.origin).replace(/\/$/, '');
      this.token = options.token || null;
      this.companyId = options.companyId || meta('workcore-company-id') || null;
      this.csrfToken = options.csrfToken || meta('csrf-token') || null;
      this.endpoints = {
        bootstrap: '/api/v1/titan-train/pwa/bootstrap',
        assignments: '/api/v1/titan-train/assignments',
      };
    }

    setContext({ token, companyId, csrfToken, endpoints } = {}) {
      if (token) this.token = token;
      if (companyId) this.companyId = companyId;
      if (csrfToken) this.csrfToken = csrfToken;
      if (endpoints && typeof endpoints === 'object') this.endpoints = { ...this.endpoints, ...endpoints };
      return this;
    }

    headers(extra = {}) {
      const headers = { Accept: 'application/json', 'Content-Type': 'application/json', ...extra };
      if (this.token) headers.Authorization = `Bearer ${this.token}`;
      if (this.companyId) headers['X-Titan-Company'] = String(this.companyId);
      if (this.csrfToken) headers['X-CSRF-TOKEN'] = this.csrfToken;
      return headers;
    }

    async request(path, options = {}) {
      if (!navigator.onLine) {
        const error = new Error('Titan Train requires an internet connection.');
        error.code = 'TITAN_TRAIN_ONLINE_REQUIRED';
        throw error;
      }
      const response = await fetch(`${this.baseUrl}${path}`, {
        ...options,
        credentials: options.credentials || 'include',
        headers: this.headers(options.headers || {}),
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const error = new Error(payload.message || `Titan Train request failed (${response.status}).`);
        error.status = response.status;
        error.payload = payload;
        throw error;
      }
      return payload.data ?? payload;
    }

    bootstrap() { return this.request(this.endpoints.bootstrap); }
    assignments() { return this.request(this.endpoints.assignments); }
    assignment(id) { return this.request(`/api/v1/titan-train/assignments/${encodeURIComponent(id)}`); }
    completeLesson(assignmentId, lessonId) {
      return this.request(`/api/v1/titan-train/assignments/${encodeURIComponent(assignmentId)}/lessons/${encodeURIComponent(lessonId)}/complete`, { method: 'POST', body: '{}' });
    }
    startAssessment(assignmentId, assessmentId) {
      return this.request(`/api/v1/titan-train/assignments/${encodeURIComponent(assignmentId)}/assessments/${encodeURIComponent(assessmentId)}/start`, { method: 'POST', body: '{}' });
    }
    submitAttempt(attemptId, answers) {
      return this.request(`/api/v1/titan-train/attempts/${encodeURIComponent(attemptId)}/submit`, { method: 'POST', body: JSON.stringify({ answers }) });
    }
  }

  const client = new TitanTrainClient({
    token: window.TitanAuth?.token?.() || window.TitanAuth?.token || null,
    companyId: window.TitanCompany?.id?.() || window.TitanCompany?.id || meta('workcore-company-id'),
  });

  window.TitanTrain = Object.freeze({ client, TitanTrainClient });
  window.dispatchEvent(new CustomEvent('titan:app-registered', {
    detail: {
      id: 'titan-train',
      name: 'Titan Train',
      manifest: '/chatbot-pwa/apps/titan-train.json',
      launchUrl: '/train',
      onlineOnly: true,
      nativeTemplate: true,
      client,
    },
  }));
})();
