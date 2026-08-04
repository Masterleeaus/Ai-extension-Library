const fs = require('fs');
const assert = require('assert');
const paths = [
  'resources/pwa/chatbot-pwa/device-db.js',
  'resources/pwa/chatbot-pwa/local-repositories.js',
  'resources/pwa/chatbot-pwa/local-services.js',
  'resources/pwa/chatbot-pwa/local-search.js',
  'resources/pwa/chatbot-pwa/local-ui-bridge.js',
  'resources/pwa/chatbot-pwa/outbox.js'
];
for (const path of paths) {
  const source = fs.readFileSync(path, 'utf8');
  assert(source.length > 100, `${path} must not be empty`);
  new Function(source);
}
const repositories = fs.readFileSync(paths[1], 'utf8');
for (const status of ['local-only','queued','sending','synced','failed','needs-review','conflict','cancelled','deleted-locally','deleted-remotely']) {
  assert(repositories.includes(status), `missing shared status ${status}`);
}
console.log('Agent 1 local runtime syntax and contract checks passed.');
