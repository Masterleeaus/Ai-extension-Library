(() => {
  'use strict';

  const APP_PROFILES = {
    'titan-zero': { headline: 'Business briefing', recordTypes: ['work_order','invoice','approval','lead'], actions: ['Review operations','Open approvals','Switch app'], empty: 'No urgent cross-app issues are stored on this device.' },
    'titan-go': { headline: 'Today in the field', recordTypes: ['job_pack','work_order','job'], actions: ['Open next job','Capture evidence','Sync now'], empty: 'No assigned jobs are downloaded yet.' },
    'titan-hub': { headline: 'Your services', recordTypes: ['booking','quote','invoice'], actions: ['Book service','Request quote','View payments'], empty: 'No bookings or account records are available offline.' },
    'titan-dispatch': { headline: 'Live operations', recordTypes: ['work_order','appointment','worker'], actions: ['Review unassigned','Open schedule','Show exceptions'], empty: 'No dispatch records are stored locally.' },
    'titan-money': { headline: 'Money requiring attention', recordTypes: ['invoice','payment','expense'], actions: ['Create invoice','Review overdue','Add expense'], empty: 'No finance projections are stored on this device.' },
    'titan-teams': { headline: 'Team coverage', recordTypes: ['worker','roster','attendance'], actions: ['Open roster','Review attendance','View requests'], empty: 'No workforce projections are available offline.' },
    'titan-analytics': { headline: 'Operational briefing', recordTypes: ['metric','report','insight'], actions: ['Open performance','Compare trends','Build report'], empty: 'Analytics are available after the first WorkCore sync.' },
    'titan-front-desk': { headline: 'Customer waiting room', recordTypes: ['conversation','booking','follow_up'], actions: ['Open inbox','Create booking','Review follow-ups'], empty: 'No enquiries are stored locally.' },
    'titan-marketing': { headline: 'Campaign workspace', recordTypes: ['campaign','content','audience'], actions: ['Create campaign','Open calendar','Review results'], empty: 'No campaign records are available offline.' },
    'titan-social': { headline: 'Publishing workspace', recordTypes: ['social_post','content','engagement'], actions: ['Create post','Open calendar','Review engagement'], empty: 'No social records are stored locally.' },
    'titan-sprout': { headline: 'Sales pipeline', recordTypes: ['lead','opportunity','activity'], actions: ['Add lead','Review hot leads','Open pipeline'], empty: 'No lead projections are stored locally.' },
    'titan-locker': { headline: 'Stock and equipment', recordTypes: ['inventory_item','asset','maintenance'], actions: ['Scan item','Review low stock','Open maintenance'], empty: 'No inventory or asset records are downloaded.' },
    'titan-office': { headline: 'Office work', recordTypes: ['document','time_entry','resource_booking'], actions: ['Create document','Log time','Find resources'], empty: 'No office records are available offline.' },
    'titan-quality': { headline: 'Quality and compliance', recordTypes: ['inspection','incident','compliance_record'], actions: ['Start inspection','Report incident','Review compliance'], empty: 'No inspection or compliance records are downloaded.' }
  };

  const safeText = (value, fallback = '') => {
    const text = value == null ? '' : String(value);
    return text.trim() || fallback;
  };
  const escapeHtml = value => safeText(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('\"', '&quot;')
    .replaceAll("'", '&#039;');
  const safeDataValue = value => encodeURIComponent(safeText(value));

  const humanise = value => safeText(value).replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  const firstValue = (record, keys, fallback = '') => {
    for (const key of keys) {
      const value = key.split('.').reduce((current, part) => current?.[part], record);
      if (value !== undefined && value !== null && value !== '') return value;
    }
    return fallback;
  };

  class TitanOperationalWorkspace {
    constructor(element) {
      this.element = element;
      try { this.schema = JSON.parse(element.dataset.schema || '{}'); } catch (_) { this.schema = {}; }
      this.template = element.dataset.template || this.schema?.identity?.slug || 'generic';
      this.view = this.schema?.navigation?.default_view || 'home';
      this.profile = APP_PROFILES[this.template] || APP_PROFILES['titan-zero'];
      this.records = [];
      this.loading = false;
      this.bind();
      this.refresh();
    }

    bind() {
      window.addEventListener('titan:navigate', event => {
        if (event.detail?.template && event.detail.template !== this.template) return;
        this.view = event.detail?.view || this.view;
        this.refresh();
      });
      window.addEventListener('workcore:sync-completed', () => this.refresh());
      this.element.addEventListener('click', event => {
        const encodedAction = event.target.closest('[data-titan-action]')?.dataset.titanAction;
        const action = encodedAction ? decodeURIComponent(encodedAction) : '';
        if (action) this.performAction(action);
        const encodedRecordId = event.target.closest('[data-record-id]')?.dataset.recordId;
        if (encodedRecordId) this.openRecord(decodeURIComponent(encodedRecordId));
      });
    }

    async loadRecords() {
      const client = window.TitanWorkCore?.client;
      if (!client) return [];
      const rows = [];
      if (this.profile.recordTypes.includes('job_pack') && client.listJobPacks) {
        try { rows.push(...(await client.listJobPacks()).map(row => ({ ...row, __type: 'job_pack' }))); } catch (_) {}
      }
      for (const type of this.profile.recordTypes.filter(type => type !== 'job_pack')) {
        try {
          const found = await client.listRecords?.(type);
          if (Array.isArray(found)) rows.push(...found.map(row => ({ ...row, __type: type })));
        } catch (_) {}
      }
      const unique = new Map();
      rows.forEach((row, index) => unique.set(`${row.__type}:${row.id ?? row.key ?? index}`, row));
      return [...unique.values()].slice(0, 12);
    }

    async refresh() {
      this.loading = true;
      this.render();
      this.records = await this.loadRecords();
      this.loading = false;
      this.render();
    }

    render() {
      const nav = this.schema?.navigation?.primary || [];
      const active = nav.find(item => item.id === this.view);
      const title = active?.label || humanise(this.view) || this.profile.headline;
      const widgets = this.schema?.home?.widgets || [];
      const prompts = this.schema?.chat?.suggested_prompts || [];
      const pending = Number(window.TitanSettings?.status?.()?.pending || 0);
      const records = this.records;

      this.element.innerHTML = `
        <div class="titan-workspace__heading">
          <div><span class="titan-workspace__eyebrow">${escapeHtml(this.schema?.identity?.name || 'Titan Zero')}</span><h2>${escapeHtml(title)}</h2></div>
          <button type="button" data-titan-action="refresh" class="titan-workspace__refresh" aria-label="Refresh local records">↻</button>
        </div>
        <div class="titan-workspace__status">
          <span>${navigator.onLine ? 'Online' : 'Offline-ready'}</span>
          <span>${records.length} local record${records.length === 1 ? '' : 's'}</span>
          <span>${pending} pending</span>
        </div>
        ${this.loading ? '<div class="titan-workspace__loading">Loading local WorkCore data…</div>' : ''}
        <section class="titan-workspace__hero">
          <div><p>${escapeHtml(this.profile.headline)}</p><strong>${records.length ? `${records.length} records ready on this device` : escapeHtml(this.profile.empty)}</strong></div>
          <div class="titan-workspace__actions">${this.profile.actions.map(action => `<button type="button" data-titan-action="${safeDataValue(action)}">${escapeHtml(action)}</button>`).join('')}</div>
        </section>
        <section class="titan-workspace__widgets" aria-label="Workspace summary">
          ${widgets.slice(0, 4).map((widget, index) => `<article><span>${escapeHtml(humanise(widget))}</span><strong>${escapeHtml(this.widgetValue(widget, index))}</strong><small>${escapeHtml(this.widgetHint(widget))}</small></article>`).join('')}
        </section>
        <section class="titan-workspace__records">
          <header><h3>${records.length ? 'Local WorkCore records' : 'Ready for WorkCore data'}</h3><span>${escapeHtml(title)}</span></header>
          ${records.length ? records.slice(0, 6).map(record => this.recordCard(record)).join('') : `<div class="titan-workspace__empty"><strong>${escapeHtml(this.profile.empty)}</strong><p>Connect or sync WorkCore to populate this screen. Actions created offline remain on this device until confirmed by Laravel.</p><button type="button" data-titan-action="Sync%20now">Sync now</button></div>`}
        </section>
        ${prompts.length ? `<section class="titan-workspace__prompts"><h3>Ask Titan</h3>${prompts.slice(0, 3).map(prompt => `<button type="button" data-titan-action="${safeDataValue(`prompt:${prompt}`)}">${escapeHtml(prompt)}</button>`).join('')}</section>` : ''}
      `;
    }

    widgetValue(widget, index) {
      const matches = this.records.filter(record => JSON.stringify(record).toLowerCase().includes(String(widget).split('_')[0])).length;
      if (matches) return String(matches);
      if (widget.includes('pending')) return String(Number(window.TitanSettings?.status?.()?.pending || 0));
      if (widget.includes('next') && this.records[0]) return safeText(firstValue(this.records[0], ['scheduled_at','start_at','date','status']), 'Ready');
      return this.records.length ? String(Math.max(0, this.records.length - index)) : '—';
    }

    widgetHint(widget) {
      if (widget.includes('pending')) return 'Awaiting server confirmation';
      if (widget.includes('alert') || widget.includes('exception')) return 'Needs attention';
      return navigator.onLine ? 'Updated from local projection' : 'Available offline';
    }

    recordCard(record) {
      const id = safeText(firstValue(record, ['id','resource_id','key']), 'local');
      const title = safeText(firstValue(record, ['title','name','reference','job.title','work_order.title','customer.name']), `${humanise(record.__type)} ${id}`);
      const status = safeText(firstValue(record, ['status','state','sync_status']), 'Local');
      const subtitle = safeText(firstValue(record, ['customer.name','premises.address','address','description','updated_at']), humanise(record.__type));
      return `<button type="button" class="titan-workspace__record" data-record-id="${safeDataValue(id)}"><span><strong>${escapeHtml(title)}</strong><small>${escapeHtml(subtitle)}</small></span><em>${escapeHtml(humanise(status))}</em></button>`;
    }

    async performAction(action) {
      if (action === 'refresh') return this.refresh();
      if (action === 'Sync now') {
        await window.TitanWorkCore?.client?.sync?.();
        return this.refresh();
      }
      if (action.startsWith('prompt:')) {
        const prompt = action.slice(7);
        window.dispatchEvent(new CustomEvent('titan:prompt-requested', { detail: { prompt, template: this.template, view: this.view } }));
        const message = document.querySelector('[x-ref="message"]');
        if (message) { message.value = prompt; message.dispatchEvent(new Event('input', { bubbles: true })); message.focus(); }
        return;
      }
      if (/capture|scan|incident/i.test(action)) {
        window.dispatchEvent(new CustomEvent('workcore:open-field-workspace', { detail: { action, template: this.template } }));
      }
      window.dispatchEvent(new CustomEvent('titan:operational-action', { detail: { action, template: this.template, view: this.view, authority: 'proposal-only' } }));
    }

    openRecord(id) {
      const record = this.records.find(row => String(row.id ?? row.resource_id ?? row.key) === String(id));
      window.dispatchEvent(new CustomEvent('titan:record-opened', { detail: { template: this.template, view: this.view, id, record, authority: 'local-projection' } }));
    }
  }

  const boot = () => document.querySelectorAll('[data-titan-operational]').forEach(el => {
    if (!el.__titanWorkspace) el.__titanWorkspace = new TitanOperationalWorkspace(el);
  });
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
  window.TitanOperationalScreens = { APP_PROFILES, boot, escapeHtml, safeDataValue };
})();
