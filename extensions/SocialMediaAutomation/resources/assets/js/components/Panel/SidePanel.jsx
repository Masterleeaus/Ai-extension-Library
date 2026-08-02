import React, { useEffect, useRef } from 'react';
import useAutomationStore from '../../store/useAutomationStore';
import { t } from '../../utils/i18n';
import TriggerSelectPanel from './TriggerSelectPanel';
import TriggerConfigPanel from './TriggerConfigPanel';
import ActionConfigPanel from './ActionConfigPanel';

export default function SidePanel() {
	const panelOpen = useAutomationStore(s => s.panelOpen);
	const panelMode = useAutomationStore(s => s.panelMode);
	const closePanel = useAutomationStore(s => s.closePanel);

	// Close on Escape
	useEffect(() => {
		function handleKeyDown(e) {
			if (e.key === 'Escape' && panelOpen) {
				closePanel();
			}
		}
		document.addEventListener('keydown', handleKeyDown);
		return () => document.removeEventListener('keydown', handleKeyDown);
	}, [ panelOpen, closePanel ]);

	if (!panelOpen) return null;

	const titles = {
		'trigger-select': t('select_trigger', 'Select Trigger'),
		'trigger-config': t('trigger_config', 'Trigger Config'),
		'action-config': t('send_dm', 'Send DM'),
	};
	const title = titles[panelMode] ?? '';

	return (
		<div className="motion-preset-slide-left fixed bottom-0 end-0 top-[--header-height] z-50 w-[min(370px,85%)] bg-background shadow-[0_4px_44px_hsl(0_0%_0%/6%)] motion-duration-150">
			<button
				className="absolute start-0 top-11 z-50 inline-grid size-9 -translate-x-1/2 place-items-center rounded-full border bg-background text-foreground transition hover:scale-110 hover:shadow-xl hover:shadow-black/5"
				onClick={closePanel}
			>
				<svg className="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M9 6l6 6l-6 6" /></svg>
			</button>

			<div
				className="h-full overflow-y-auto px-6 pb-6 pt-5"
			>
				{panelMode === 'trigger-select' && <TriggerSelectPanel />}
				{panelMode === 'trigger-config' && <TriggerConfigPanel />}
				{panelMode === 'action-config' && <ActionConfigPanel />}
			</div>
		</div>
	);
}
