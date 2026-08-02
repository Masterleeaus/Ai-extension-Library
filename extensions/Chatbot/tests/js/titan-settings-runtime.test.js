const fs = require('fs');
const vm = require('vm');
const source = fs.readFileSync('resources/pwa/chatbot-pwa/settings-runtime.js', 'utf8');
const storage = new Map();
const context = {
  window: {}, navigator: { onLine: true }, structuredClone: value => JSON.parse(JSON.stringify(value)),
  localStorage: { getItem:k=>storage.get(k)||null, setItem:(k,v)=>storage.set(k,v) },
  CustomEvent: function(name, options){ this.name=name; this.detail=options?.detail; }
};
context.window.window=context.window; context.window.dispatchEvent=()=>{};
context.window.TitanWorkCore={ vault:{ isUnlocked:()=>true, encrypt:async value=>({cipher:'protected', value:Object.keys(value).length}) }, client:{}, state:{pending:2} };
vm.createContext(context); vm.runInContext(source, context);
(async()=>{
  if (!context.window.TitanSettings) throw new Error('TitanSettings missing');
  context.window.TitanSettings.update({theme:'dark'});
  if (context.window.TitanSettings.get().theme !== 'dark') throw new Error('settings update failed');
  await context.window.TitanSettings.saveProvider('openai',{api_key:'secret',model:'model'});
  const provider=context.window.TitanSettings.get().providers.openai;
  if (!provider.connected || provider.storage !== 'device-vault') throw new Error('provider not encrypted');
  if (JSON.stringify(provider).includes('secret')) throw new Error('plaintext provider key persisted');
  console.log('Titan settings runtime contract passed');
})().catch(error=>{console.error(error);process.exit(1)});
