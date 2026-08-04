const fs = require('fs');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/titan-operational-screens.js'), 'utf8');
const apps = ['titan-zero','titan-go','titan-hub','titan-dispatch','titan-money','titan-teams','titan-analytics','titan-front-desk','titan-marketing','titan-social','titan-sprout','titan-locker','titan-office','titan-quality'];
for (const app of apps) { if (!source.includes(`'${app}'`)) throw new Error(`Missing operational profile: ${app}`); }
for (const token of ['listJobPacks','listRecords','titan:operational-action','authority: \'proposal-only\'','workcore:sync-completed']) { if (!source.includes(token)) throw new Error(`Missing runtime contract: ${token}`); }
console.log('Titan operational screens contract passed for 14 apps.');
