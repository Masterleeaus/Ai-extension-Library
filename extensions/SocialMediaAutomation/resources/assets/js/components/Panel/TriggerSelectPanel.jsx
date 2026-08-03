import React from 'react';
import useAutomationStore from '../../store/useAutomationStore';
import { t } from '../../utils/i18n';
import PlatformIcon from './PlatformIcon';

export default function TriggerSelectPanel() {
	const openPanel = useAutomationStore(s => s.openPanel);
	const selectedNodeId = useAutomationStore(s => s.selectedNodeId);
	const platformName = useAutomationStore(s => s.getSelectedPlatformName());
	const nodes = useAutomationStore(s => s.nodes);
	const addDelayNode = useAutomationStore(s => s.addDelayNode);
	const removeDelayNode = useAutomationStore(s => s.removeDelayNode);

	const hasDelay = nodes.some(n => n.type === 'delay');
	const displayPlatform = platformName
		? platformName.charAt(0).toUpperCase() + platformName.slice(1)
		: 'Platform';

	function handleContinue() {
		openPanel('trigger-config', selectedNodeId);
	}

	function toggleDelay() {
		if (hasDelay) {
			removeDelayNode();
		} else {
			addDelayNode();
		}
	}

	return (
		<div className="flex h-full flex-col">
			<p className="mb-5 w-full border-b py-3.5 text-[12px] font-semibold leading-tight text-heading-foreground">
				{t('select_trigger', 'Choose what triggers your automation.')}
			</p>

			<div className="space-y-2.5">
				{/* Platform Trigger Option */}
				<button className="active flex w-full items-center gap-2.5 rounded-[10px] border p-4 text-start text-2xs leading-tight transition hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5 [&.active]:border-primary [&.active]:bg-primary/5">
					<span className="inline-flex size-4 shrink-0">
						<PlatformIcon platform={platformName} />
					</span>

					<span>
						<span className="mb-1.5 block font-medium text-heading-foreground">
							{displayPlatform} {t('post_or_reel', 'Post or Reel')}
						</span>
						<span className="block">
							{t('trigger_comment_desc', 'Trigger when someone comments on a post or reel')}
						</span>
					</span>
				</button>

				{/* Smart Delay Toggle */}
				<button
					className="flex w-full items-center gap-2.5 rounded-[10px] border p-4 text-start text-2xs leading-tight transition hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5"
					onClick={toggleDelay}
				>
					<svg className="text-primary" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
						<path d="M8.75 8.08333L6.75 6.75V3.41667M0.75 6.75C0.75 7.53793 0.905195 8.31815 1.20672 9.0461C1.50825 9.77405 1.95021 10.4355 2.50736 10.9926C3.06451 11.5498 3.72595 11.9917 4.4539 12.2933C5.18185 12.5948 5.96207 12.75 6.75 12.75C7.53793 12.75 8.31815 12.5948 9.0461 12.2933C9.77405 11.9917 10.4355 11.5498 10.9926 10.9926C11.5498 10.4355 11.9917 9.77405 12.2933 9.0461C12.5948 8.31815 12.75 7.53793 12.75 6.75C12.75 5.96207 12.5948 5.18185 12.2933 4.4539C11.9917 3.72595 11.5498 3.06451 10.9926 2.50736C10.4355 1.95021 9.77405 1.50825 9.0461 1.20672C8.31815 0.905195 7.53793 0.75 6.75 0.75C5.96207 0.75 5.18185 0.905195 4.4539 1.20672C3.72595 1.50825 3.06451 1.95021 2.50736 2.50736C1.95021 3.06451 1.50825 3.72595 1.20672 4.4539C0.905195 5.18185 0.75 5.96207 0.75 6.75Z"/>
					</svg>

					<span>
						<span className="mb-1.5 block font-medium text-heading-foreground">
							{t('smart_delay', 'Smart Delay')}
						</span>
						<span className="block">
							{t('smart_delay_desc', 'Wait before the next action')}
						</span>
					</span>
				</button>
			</div>

			<div className="mt-auto pt-8">
				<button
					className="lqd-btn lqd-btn-primary w-full rounded-button bg-gradient-to-r from-gradient-from via-gradient-via to-gradient-to p-2.5 text-xs font-medium text-primary-foreground transition hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5"
					onClick={handleContinue}
				>
					{t('continue', 'Continue')}
				</button>
			</div>
		</div>
	);
}
