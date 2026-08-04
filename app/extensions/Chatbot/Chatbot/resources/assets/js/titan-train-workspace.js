(() => {
  'use strict';

  const SELECTOR = '[data-titan-operational][data-template="titan-train"]';
  const VIEW_ALIASES = {
    programs: 'learn',
    assessments: 'practice',
    'live-training': 'practice',
    'trainer-review': 'practice',
    certificates: 'skills',
    readiness: 'skills',
  };

  const text = (value, fallback = '') => {
    const result = value == null ? '' : String(value).trim();
    return result || fallback;
  };

  const escapeHtml = value => text(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const encode = value => encodeURIComponent(text(value));
  const decode = value => decodeURIComponent(text(value));
  const formatDate = value => {
    if (!value) return 'Not scheduled';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? text(value) : date.toLocaleDateString();
  };

  class TitanTrainWorkspace {
    constructor(element) {
      this.element = element;
      try { this.schema = JSON.parse(element.dataset.schema || '{}'); } catch (_) { this.schema = {}; }
      this.view = this.schema?.navigation?.default_view || 'learn';
      this.data = null;
      this.loading = false;
      this.error = null;
      this.busyAction = null;
      this.bind();
      this.refresh();
    }

    get client() { return window.TitanTrain?.client || null; }

    bind() {
      window.addEventListener('titan:navigate', event => {
        if (event.detail?.template && event.detail.template !== 'titan-train') return;
        this.view = VIEW_ALIASES[event.detail?.view] || event.detail?.view || this.view;
        this.render();
      });
      window.addEventListener('online', () => this.refresh());
      window.addEventListener('titan-train:refresh-requested', () => this.refresh());
      window.addEventListener('offline', () => { this.error = null; this.loading = false; this.render(); });
      this.element.addEventListener('click', event => {
        const target = event.target.closest('[data-train-action]');
        if (!target) return;
        this.perform(decode(target.dataset.trainAction || '')).catch(error => {
          this.error = error;
          this.busyAction = null;
          this.render();
        });
      });
    }

    async refresh() {
      if (!navigator.onLine) { this.loading = false; this.render(); return; }
      if (!this.client) {
        this.error = new Error('Titan Train is still loading. Refresh this page to retry.');
        this.render();
        return;
      }
      this.loading = true;
      this.error = null;
      this.render();
      try {
        this.data = await this.client.bootstrap();
        this.client.setContext({ companyId: this.data?.company?.public_id, endpoints: this.data?.endpoints });
      } catch (error) {
        this.error = error;
      } finally {
        this.loading = false;
        this.render();
      }
    }

    render() {
      if (!navigator.onLine) { this.element.innerHTML = this.connectionRequired(); return; }
      if (this.loading && !this.data) {
        this.element.innerHTML = '<div class="titan-train-state"><span class="titan-train-spinner" aria-hidden="true"></span><strong>Loading Titan Train…</strong></div>';
        return;
      }
      if (this.error) { this.element.innerHTML = this.errorState(this.error); return; }
      if (!this.data) { this.element.innerHTML = this.emptyState('Titan Train is ready to connect.', 'refresh', 'Connect'); return; }

      const view = VIEW_ALIASES[this.view] || this.view;
      const content = {
        learn: () => this.renderLearn(),
        practice: () => this.renderPractice(),
        skills: () => this.renderSkills(),
        me: () => this.renderMe(),
      }[view]?.() || this.renderLearn();

      this.element.innerHTML = `<div class="titan-train-shell">${this.renderHeading(view)}${content}</div>`;
    }

    renderHeading(view) {
      const labels = { learn: 'Learn', practice: 'Practice', skills: 'Skills', me: 'Me' };
      const readiness = this.data?.readiness || {};
      return `
        <header class="titan-train-heading">
          <div><span class="titan-train-eyebrow">Titan Train · Online</span><h2>${escapeHtml(labels[view] || 'Learn')}</h2><p>${escapeHtml(this.headingHint(view))}</p></div>
          <button type="button" class="titan-train-icon-button" data-train-action="refresh" aria-label="Refresh Titan Train">↻</button>
        </header>
        <section class="titan-train-readiness" aria-label="Training readiness">
          <div><span>Progress</span><strong>${Number(readiness.progress_percent || 0)}%</strong></div>
          <div><span>Knowledge</span><strong>${readiness.knowledge_passed ? 'Passed' : 'Pending'}</strong></div>
          <div><span>Practical</span><strong>${readiness.practical_passed ? 'Passed' : 'Pending'}</strong></div>
          <div><span>Work status</span><strong>${readiness.ready ? 'Ready' : 'Training'}</strong></div>
        </section>`;
    }

    headingHint(view) {
      return {
        learn: 'Continue the next approved lesson in your assigned program.',
        practice: 'Complete assessments and work with your trainer.',
        skills: 'See competencies, certificates and job readiness.',
        me: 'Review your training role, company and support channels.',
      }[view] || '';
    }

    renderLearn() {
      const assignments = this.data?.assignments || [];
      if (!assignments.length) return this.emptyState('No training has been assigned yet.', 'prompt:What training has been assigned to me?', 'Ask Titan');
      return `<section class="titan-train-list" aria-label="Assigned programs">${assignments.map(assignment => this.assignmentCard(assignment)).join('')}</section>${this.askTitan(['What training do I need next?', 'Explain my next lesson more simply'])}`;
    }

    assignmentCard(assignment) {
      const lessons = (assignment.program?.modules || []).flatMap(module => (module.lessons || []).map(lesson => ({ ...lesson, module: module.title })));
      const next = lessons.find(lesson => !lesson.completed);
      return `
        <article class="titan-train-card">
          <header><div><span>${escapeHtml(assignment.status || 'assigned')}</span><h3>${escapeHtml(assignment.program?.title || 'Training program')}</h3><p>${escapeHtml(assignment.program?.summary || '')}</p></div><strong>${Number(assignment.progress_percent || 0)}%</strong></header>
          <div class="titan-train-progress" role="progressbar" aria-valuenow="${Number(assignment.progress_percent || 0)}" aria-valuemin="0" aria-valuemax="100"><i style="width:${Math.min(100, Math.max(0, Number(assignment.progress_percent || 0)))}%"></i></div>
          <dl><div><dt>Due</dt><dd>${escapeHtml(formatDate(assignment.due_at))}</dd></div><div><dt>Lessons</dt><dd>${lessons.filter(lesson => lesson.completed).length}/${lessons.length}</dd></div><div><dt>Cycle</dt><dd>${Number(assignment.cycle_number || 1)}</dd></div></dl>
          ${next ? `<div class="titan-train-next"><span>Next · ${escapeHtml(next.module)}</span><strong>${escapeHtml(next.title)}</strong><small>${Number(next.duration_minutes || 0)} minutes</small>${next.body ? `<p>${escapeHtml(next.body)}</p>` : ''}<button type="button" data-train-action="${encode(`complete:${assignment.id}:${next.id}`)}" ${this.busyAction ? 'disabled' : ''}>${this.busyAction === `complete:${assignment.id}:${next.id}` ? 'Saving…' : 'Mark lesson complete'}</button></div>` : '<div class="titan-train-complete">All required lessons completed.</div>'}
          <button type="button" class="titan-train-link" data-train-action="${encode(`open-assignment:${assignment.id}`)}">Open full program</button>
        </article>`;
    }

    renderPractice() {
      const assignments = this.data?.assignments || [];
      const assessments = assignments.flatMap(assignment => (assignment.program?.assessments || []).map(assessment => ({ ...assessment, assignment })));
      const channels = this.data?.channels || [];
      return `
        <section class="titan-train-section"><header><h3>Assessments</h3><span>${assessments.length}</span></header>${assessments.length ? assessments.map(item => this.assessmentCard(item)).join('') : '<p class="titan-train-muted">No assessments are available yet.</p>'}</section>
        <section class="titan-train-section"><header><h3>Training channels</h3><span>${channels.length}</span></header>${channels.length ? channels.map(channel => this.channelCard(channel)).join('') : '<p class="titan-train-muted">No trainer or cohort channel has been linked yet.</p>'}</section>
        ${this.askTitan(['How do I prepare for my practical assessment?', 'Explain the assessment pass requirements'])}`;
    }

    assessmentCard(item) {
      const related = (item.assignment.attempts || []).filter(attempt => attempt.assessment_id === item.id);
      const latest = related.at(-1);
      const canStart = item.kind !== 'practical';
      return `<article class="titan-train-row"><div><span>${escapeHtml(item.kind || 'assessment')}</span><strong>${escapeHtml(item.title)}</strong><small>Pass mark ${Number(item.pass_mark || 0)}% · ${Number(item.duration_minutes || 0)} minutes</small></div><div class="titan-train-row__actions"><em>${escapeHtml(latest?.result || latest?.status || (item.kind === 'practical' ? 'Trainer sign-off' : 'Not started'))}</em>${canStart ? `<button type="button" data-train-action="${encode(`assessment:${item.assignment.id}:${item.id}`)}" ${this.busyAction ? 'disabled' : ''}>${this.busyAction === `assessment:${item.assignment.id}:${item.id}` ? 'Starting…' : 'Start'}</button>` : ''}</div></article>`;
    }

    channelCard(channel) {
      const title = channel.metadata?.title || channel.metadata?.name || channel.slug || 'Training channel';
      return `<button type="button" class="titan-train-channel" data-train-action="${encode(`channel:${channel.id}`)}"><span><strong>${escapeHtml(title)}</strong><small>${escapeHtml(channel.type || 'training')} · ${escapeHtml(channel.status || 'active')}</small></span><span aria-hidden="true">›</span></button>`;
    }

    renderSkills() {
      const readiness = this.data?.readiness || {};
      const certificate = readiness.certificate;
      const active = Number(readiness.active_competencies || 0);
      const required = Number(readiness.required_competencies || 0);
      return `
        <section class="titan-train-qualification ${readiness.ready ? 'is-ready' : ''}"><span>${readiness.ready ? 'Qualified' : 'Qualification in progress'}</span><h3>${escapeHtml(readiness.program?.title || 'Cleaner Foundation')}</h3><p>${readiness.ready ? 'Your current evidence meets the required cleaner-foundation rules.' : 'Complete lessons, knowledge assessment and supervised practical sign-off.'}</p><div class="titan-train-competency-count"><strong>${active}</strong><span>of ${required} competencies active</span></div></section>
        <section class="titan-train-section"><header><h3>Certificate</h3></header>${certificate ? `<article class="titan-train-certificate"><span>${escapeHtml(certificate.status || 'issued')}</span><strong>${escapeHtml(certificate.number || 'Certificate')}</strong><small>Issued ${escapeHtml(formatDate(certificate.issued_at))}${certificate.expires_at ? ` · Expires ${escapeHtml(formatDate(certificate.expires_at))}` : ''}</small></article>` : '<p class="titan-train-muted">Your certificate will appear after qualification is finalised.</p>'}</section>
        ${this.askTitan(['Am I ready for cleaning work?', 'Which competencies do I still need?'])}`;
    }

    renderMe() {
      const company = this.data?.company || {};
      const permissions = this.data?.permissions || {};
      const channels = this.data?.channels || [];
      return `
        <section class="titan-train-profile"><div class="titan-train-avatar" aria-hidden="true">TT</div><div><span>${escapeHtml(company.role || 'learner')}</span><h3>My Titan Train</h3><p>${permissions.manage ? 'Trainer and learner access' : 'Learner access'}</p></div></section>
        <section class="titan-train-section"><header><h3>Account context</h3></header><dl class="titan-train-details"><div><dt>Company</dt><dd>${escapeHtml(company.public_id || 'Active company')}</dd></div><div><dt>Role</dt><dd>${escapeHtml(company.role || 'learner')}</dd></div><div><dt>Delivery</dt><dd>Online only</dd></div><div><dt>Training channels</dt><dd>${channels.length}</dd></div></dl></section>
        <div class="titan-train-actions"><button type="button" data-train-action="launch">Open full Titan Train</button><button type="button" data-train-action="${encode('prompt:I need help with my training')}">Ask for help</button></div>`;
    }

    askTitan(prompts) {
      return `<section class="titan-train-prompts"><h3>Ask Titan</h3>${prompts.map(prompt => `<button type="button" data-train-action="${encode(`prompt:${prompt}`)}">${escapeHtml(prompt)}</button>`).join('')}</section>`;
    }

    connectionRequired() {
      return `<div class="titan-train-state"><span class="titan-train-state__icon" aria-hidden="true">↗</span><strong>Connect to continue training</strong><p>Titan Train is online-only. No lesson, assessment or qualification record is stored in the device offline database.</p><button type="button" data-train-action="refresh">Try again</button></div>`;
    }

    errorState(error) {
      const signedOut = Number(error?.status || 0) === 401 || Number(error?.status || 0) === 403;
      return `<div class="titan-train-state"><strong>${signedOut ? 'Sign in to Titan Train' : 'Titan Train could not load'}</strong><p>${escapeHtml(error?.message || 'Check your connection and try again.')}</p><button type="button" data-train-action="refresh">Retry</button></div>`;
    }

    emptyState(message, action, label) {
      return `<div class="titan-train-state"><strong>${escapeHtml(message)}</strong><button type="button" data-train-action="${encode(action)}">${escapeHtml(label)}</button></div>`;
    }

    async perform(action) {
      if (!action) return;
      if (action === 'refresh') return this.refresh();
      if (action === 'launch') { window.location.assign(this.data?.app?.launch_url || '/train'); return; }
      if (action.startsWith('prompt:')) {
        const prompt = action.slice(7);
        window.dispatchEvent(new CustomEvent('titan:prompt-requested', { detail: { prompt, template: 'titan-train', view: this.view, authority: 'learning-assistance' } }));
        return;
      }
      if (action.startsWith('open-assignment:')) {
        const assignmentId = action.split(':')[1];
        window.location.assign(`/train?assignment=${encodeURIComponent(assignmentId)}`);
        return;
      }
      if (action.startsWith('complete:')) {
        const [, assignmentId, lessonId] = action.split(':');
        this.busyAction = action;
        this.render();
        await this.client.completeLesson(assignmentId, lessonId);
        window.dispatchEvent(new CustomEvent('titan-train:lesson-completed', { detail: { assignmentId, lessonId, authority: 'server-confirmed' } }));
        this.busyAction = null;
        await this.refresh();
        return;
      }
      if (action.startsWith('assessment:')) {
        const [, assignmentId, assessmentId] = action.split(':');
        this.busyAction = action;
        this.render();
        const attempt = await this.client.startAssessment(assignmentId, assessmentId);
        this.busyAction = null;
        window.dispatchEvent(new CustomEvent('titan-train:assessment-started', { detail: { assignmentId, assessmentId, attempt, authority: 'server-confirmed' } }));
        window.dispatchEvent(new CustomEvent('titan:operational-action', { detail: { action: 'open-training-assessment', template: 'titan-train', view: 'practice', attempt, authority: 'server-confirmed' } }));
        this.render();
        return;
      }
      if (action.startsWith('channel:')) {
        const channelId = action.split(':')[1];
        const channel = (this.data?.channels || []).find(item => String(item.id) === String(channelId));
        if (!channel) return;
        const detail = { channel, source: 'titan-train', authority: 'titan-channels' };
        window.dispatchEvent(new CustomEvent('chatbot:team-channel-open-requested', { detail }));
        window.dispatchEvent(new CustomEvent('titan-train:channel-opened', { detail }));
      }
    }
  }

  const boot = () => document.querySelectorAll(SELECTOR).forEach(element => {
    if (element.__titanWorkspace) return;
    const workspace = new TitanTrainWorkspace(element);
    element.__titanWorkspace = workspace;
  });

  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
  window.TitanTrainWorkspace = Object.freeze({ TitanTrainWorkspace, boot, selector: SELECTOR });
})();
