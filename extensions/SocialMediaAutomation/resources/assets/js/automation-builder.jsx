import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

function mount() {
    const container = document.getElementById('automation-builder-root');
    if (container && !container._reactMounted) {
        container._reactMounted = true;
        const root = createRoot(container);
        root.render(<App />);
    }
}

// Expose for Alpine x-init to call once the container is in the DOM and visible
window.__mountAutomationBuilder = mount;
