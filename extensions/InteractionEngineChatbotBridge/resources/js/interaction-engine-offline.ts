import * as offline from '@titanzero/interaction-engine-offline';

export { offline };

export function installTitanInteractionOffline(target: Window = window): typeof offline {
  Object.defineProperty(target, 'TitanInteractionOffline', {
    configurable: true,
    enumerable: false,
    value: offline,
    writable: false,
  });
  return offline;
}

declare global {
  interface Window {
    TitanInteractionOffline?: typeof offline;
  }
}
