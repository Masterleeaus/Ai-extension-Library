const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '../..');
function read(relative) { return fs.readFileSync(path.join(root, relative), 'utf8'); }
function assert(condition, message) { if (!condition) throw new Error(message); }
const db = read('resources/pwa/chatbot-pwa/device-db.js');
const runtime = read('resources/pwa/chatbot-pwa/team-chat-local-first.js');
const channels = read('resources/pwa/chatbot-pwa/business-channels-local-first.js');
const ui = read('resources/pwa/chatbot-pwa/team-chat-local-ui.js');
assert(db.includes("const DB_VERSION = 4"), 'IndexedDB schema must be version 4.');
['conversation_type', 'linked_entity_type', 'slug'].forEach(index => assert(db.includes(index), `Missing ${index} index.`));
['local_uuid','server_id','tenant_id','user_id','device_id','version','sync_sequence','sync_status','created_at','updated_at','deleted_at','last_synced_at'].forEach(field => assert(runtime.includes(field), `Runtime missing shared metadata field ${field}.`));
assert(runtime.includes('TitanTeamChatNetwork'), 'Network adapter must be preserved beneath local-first wrapper.');
assert(runtime.includes('mergeCanonical'), 'Server records must merge canonically.');
assert(runtime.includes('cancelForEntity'), 'Acknowledged operations must be removed from outbox.');
assert(runtime.includes("error.status === 409 || error.status === 412"), 'Conflicts must capture 409/412 responses.');
assert(channels.includes("conversation_type: 'channel'"), 'Channels must share canonical conversation records.');
assert(ui.includes('data-team-message-retry'), 'UI must expose retry controls.');
assert(ui.includes('localSearch'), 'UI must use local search.');
console.log('team-chat local-first contract: PASS');
