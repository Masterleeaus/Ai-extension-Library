const fs = require('fs');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/frontend-ui/frontend-ui-scripts.blade.php'), 'utf8');
['openTitanDrawer()', 'openTitanSettings()', 'navigateTitanView(view)', 'openTitanChat()', 'titanConnectionState'].forEach(token => {
  if (!source.includes(token)) throw new Error(`Missing shell runtime token: ${token}`);
});
console.log('Titan shell runtime contract passed.');
