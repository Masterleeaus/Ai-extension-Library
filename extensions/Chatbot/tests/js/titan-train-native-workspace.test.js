const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '../..');
const schemaPath = path.join(root, 'resources/titan-apps/TemplateSchemas/titan-train.json');
const configPath = path.join(root, 'resources/titan-apps/TitanSuiteTemplates/titan-train/config.json');
const workspacePath = path.join(root, 'resources/assets/js/titan-train-workspace.js');
const clientPath = path.join(root, 'resources/pwa/chatbot-pwa/apps/titan-train.js');

for (const file of [schemaPath, configPath, workspacePath, clientPath]) {
  if (!fs.existsSync(file)) throw new Error(`Missing native Titan Train file: ${file}`);
}

const schema = JSON.parse(fs.readFileSync(schemaPath, 'utf8'));
if (schema.identity?.slug !== 'titan-train') throw new Error('Titan Train schema slug is missing');
if (schema.online_only !== true) throw new Error('Titan Train must be explicitly online-only');
if (schema.navigation?.primary?.length !== 4) throw new Error('Titan Train must expose four primary views');
if (schema.offline?.enabled !== false) throw new Error('Titan Train offline mode must be disabled');
if (schema.navigation.primary.some(item => item.offline !== false)) throw new Error('Every Titan Train view must require connectivity');

const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
if (config.slug !== 'titan-train') throw new Error('Titan registry config missing');
if (!config.features.includes('managed-training-channels')) throw new Error('Managed training channels missing');

const workspace = fs.readFileSync(workspacePath, 'utf8');
for (const token of ['data-template="titan-train"', 'window.TitanTrain?.client', 'titan-train:assessment-started', 'chatbot:team-channel-open-requested', 'navigator.onLine', 'completeLesson', 'titan:prompt-requested']) {
  if (!workspace.includes(token)) throw new Error(`Workspace contract missing: ${token}`);
}
for (const banned of ['IndexedDB', 'offline_operation', 'sync_cursor', 'outbox']) {
  if (workspace.includes(banned)) throw new Error(`Online-only workspace contains banned token: ${banned}`);
}

const client = fs.readFileSync(clientPath, 'utf8');
for (const token of ['X-CSRF-TOKEN', 'X-Titan-Company', 'credentials', 'submitAttempt']) {
  if (!client.includes(token)) throw new Error(`Client contract missing: ${token}`);
}

console.log('Titan Train native workspace contract passed.');
