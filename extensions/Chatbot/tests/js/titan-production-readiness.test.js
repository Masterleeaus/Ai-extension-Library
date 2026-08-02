const fs = require('fs');
const path = require('path');
const base = path.join(__dirname, '../..');
const read = relative => fs.readFileSync(path.join(base, relative), 'utf8');

const operational = read('resources/js/titan-operational-screens.js');
for (const token of ['escapeHtml', 'safeDataValue', '&lt;', 'authority: \'proposal-only\'']) {
  if (!operational.includes(token)) throw new Error(`Missing operational security contract: ${token}`);
}
if (operational.includes('data-record-id="${id}"')) throw new Error('Raw record IDs are interpolated into HTML attributes.');

const contracts = read('resources/pwa/chatbot-pwa/workcore/contracts.js');
for (const endpoint of [
  "attachmentSessions: '/api/v1/workcore/offline/attachments'",
  'attachmentSession: id => `/api/v1/workcore/offline/attachments/${encodeURIComponent(id)}`',
  "reachability: '/api/v1/workcore/actions'"
]) if (!contracts.includes(endpoint)) throw new Error(`WorkCore server contract mismatch: ${endpoint}`);

const attachments = read('resources/pwa/chatbot-pwa/workcore/attachments.js');
for (const token of [
  'attachment_id: record.id',
  'body:JSON.stringify({complete:true,checksum:record.checksum})',
  'fetch(root.contracts.endpoints.attachmentSession(record.upload_session_id),{method:\'PUT\''
]) if (!attachments.includes(token)) throw new Error(`Attachment protocol mismatch: ${token}`);
if (attachments.includes('/chunks') || attachments.includes('/complete`')) throw new Error('Legacy attachment session routes remain in the runtime.');

const serviceWorker = read('resources/pwa/chatbot-sw.js');
for (const asset of [
  '/vendor/chatbot/js/titan-operational-screens.js',
  '/vendor/chatbot/css/titan-app-shell.css',
  '/chatbot-pwa/settings-runtime.js'
]) if (!serviceWorker.includes(asset)) throw new Error(`Production shell asset not precached: ${asset}`);
if (!serviceWorker.includes("'/api/'")) throw new Error('Authenticated API paths are not excluded from service-worker caching.');

const header = read('resources/views/frontend-ui/components/header.blade.php');
for (const token of ['aria-controls="titan-app-drawer"', 'aria-controls="titan-settings-panel"', ':aria-expanded=']) {
  if (!header.includes(token)) throw new Error(`Missing header accessibility contract: ${token}`);
}
const nav = read('resources/views/frontend-ui/components/app-shell-navigation.blade.php');
if (!nav.includes(':aria-current=')) throw new Error('Primary navigation lacks aria-current.');
console.log('Titan production readiness contract passed.');
