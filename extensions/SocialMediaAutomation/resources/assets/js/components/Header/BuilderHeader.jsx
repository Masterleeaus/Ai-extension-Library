import React from 'react';
import useAutomationStore from '../../store/useAutomationStore';
import { t } from '../../utils/i18n';

export default function BuilderHeader() {
	const automationName = useAutomationStore(s => s.automationName);
	const status = useAutomationStore(s => s.status);
	const saving = useAutomationStore(s => s.saving);
	const canSetLive = useAutomationStore(s => s.canSetLive());
	const saveAutomationAction = useAutomationStore(s => s.saveAutomationAction);
	const setLiveAction = useAutomationStore(s => s.setLiveAction);

	const routes = window.__automationData?.routes ?? {};
	const indexUrl = routes.index ?? '/dashboard/user/social-media/automation';

	const statusClass = {
		live: 'bg-green-500',
		draft: 'bg-foreground/50',
		paused: 'bg-yellow-500',
		expired: 'bg-red-500',
	}[status] || 'bg-foreground/50';

	return (
		<div className="z-30 flex h-[--header-height] shrink-0 items-center justify-between gap-2 bg-background/90 px-3 py-2 shadow-[0_4px_4px_hsl(0_0%_0%/5%)] backdrop-blur-md max-sm:px-2 sm:gap-4 sm:px-6 sm:py-3">
			<div className="flex min-w-0 items-center gap-2 sm:gap-3">
				<a
					className="inline-grid size-[34px] shrink-0 place-items-center rounded-full border transition hover:bg-foreground/5"
					href={indexUrl}
				>
					<svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
						<line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" />
					</svg>
				</a>

				<div className="flex min-w-0 cursor-default items-center gap-2 text-2xs font-medium">
					{t('editing', 'Editing')}
					<input
						className="min-w-0 border-0 bg-transparent font-semibold text-heading-foreground outline-none focus:ring-0 sm:text-2xs"
						type="text"
						value={automationName}
						onChange={e => useAutomationStore.setState({ automationName: e.target.value })}
						placeholder={t('untitled_automation', 'Untitled Automation')}
					/>
					<span className="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2 py-1.5 text-2xs font-medium leading-none max-sm:hidden">
						<span className={`inline-flex size-1 rounded-full ${statusClass}`}></span>
						{status.charAt(0).toUpperCase() + status.slice(1)}
					</span>
				</div>
			</div>

			<div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
				<button
					className="lqd-btn lqd-btn-ghost-shadow group inline-flex items-center justify-center rounded-button bg-background px-3 py-2 font-medium shadow-xs transition hover:bg-primary hover:text-primary-foreground disabled:pointer-events-none disabled:opacity-50 max-sm:px-2 max-sm:py-1.5"
					onClick={() => saveAutomationAction()}
					disabled={saving}
				>
					{saving ? t('saving', 'Saving...') : t('update', 'Update')}
				</button>

				<button
					className="lqd-btn lqd-btn-primary group inline-flex items-center justify-center rounded-button bg-primary px-3 py-2 font-medium text-primary-foreground shadow-xs transition hover:bg-primary hover:text-primary-foreground disabled:pointer-events-none disabled:opacity-50 max-sm:px-2 max-sm:py-1.5"
					onClick={() => setLiveAction()}
					disabled={!canSetLive || saving}
				>
					{status === 'live' ? t('pause', 'Pause') : t('set_live', 'Set Live')}
				</button>
			</div>
		</div>
	);
}
