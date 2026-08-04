import React, { memo } from 'react';
import { Handle, Position } from '@xyflow/react';
import useAutomationStore from '../../store/useAutomationStore';
import { t } from '../../utils/i18n';

const TriggerNode = memo(function TriggerNode({ id, data }) {
	const openPanel = useAutomationStore(s => s.openPanel);
	const panelMode = useAutomationStore(s => s.panelMode);
	const platformName = useAutomationStore(s => s.getSelectedPlatformName());

	const configured = data.configured;
	const displayPlatform = platformName
		? platformName.charAt(0).toUpperCase() + platformName.slice(1)
		: '';

	return (
		<div className={`w-56 rounded-[10px] bg-background sm:w-64 ${panelMode === 'trigger-config' ? 'outline outline-[3px] outline-[#1CA685]' : ''}`}>
			{/* Node Header */}
			<div className="flex h-[52px] items-center gap-2 border-b p-4 text-2xs font-medium leading-tight text-heading-foreground">
				<svg className="text-primary" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
					<path d="M8.75 8.08333L6.75 6.75V3.41667M0.75 6.75C0.75 7.53793 0.905195 8.31815 1.20672 9.0461C1.50825 9.77405 1.95021 10.4355 2.50736 10.9926C3.06451 11.5498 3.72595 11.9917 4.4539 12.2933C5.18185 12.5948 5.96207 12.75 6.75 12.75C7.53793 12.75 8.31815 12.5948 9.0461 12.2933C9.77405 11.9917 10.4355 11.5498 10.9926 10.9926C11.5498 10.4355 11.9917 9.77405 12.2933 9.0461C12.5948 8.31815 12.75 7.53793 12.75 6.75C12.75 5.96207 12.5948 5.18185 12.2933 4.4539C11.9917 3.72595 11.5498 3.06451 10.9926 2.50736C10.4355 1.95021 9.77405 1.50825 9.0461 1.20672C8.31815 0.905195 7.53793 0.75 6.75 0.75C5.96207 0.75 5.18185 0.905195 4.4539 1.20672C3.72595 1.50825 3.06451 1.95021 2.50736 2.50736C1.95021 3.06451 1.50825 3.72595 1.20672 4.4539C0.905195 5.18185 0.75 5.96207 0.75 6.75Z"/>
				</svg>
				{t('when', 'When')}
			</div>

			{/* Node Body */}
			<div className="p-4">
				{!configured ? (
					<div>
						<p className="mb-3 text-2xs">
							{t('when_desc', 'When should we start the automation?')}
						</p>
						<button
							className="w-full rounded-[10px] border border-dashed p-4 text-center text-[12px] font-medium leading-none text-primary transition hover:border-primary hover:bg-primary hover:text-primary-foreground"
							onClick={() => openPanel('trigger-select', id)}
						>
                                + {t('add_trigger', 'Add Trigger')}
						</button>
					</div>
				) : (
					<button
						className='rounded-[10px] border p-4 text-start text-2xs transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-black/5'
						onClick={() => openPanel('trigger-config', id)}
					>
						<span className="mb-0.5 block font-medium text-heading-foreground">
							{displayPlatform}
						</span>
						<span className="m-0 block opacity-90">
							{t('user_replies_to', 'User Replies to Your :type', { type: t('post_or_reel', 'Post or Reel') })}
						</span>
					</button>
				)}
			</div>

			<Handle type="source" position={Position.Right} className="!size-2.5 !bg-foreground/30" />
		</div>
	);
});

export default TriggerNode;
