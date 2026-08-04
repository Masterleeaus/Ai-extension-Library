import React, { memo } from 'react';
import { Handle, Position } from '@xyflow/react';
import useAutomationStore from '../../store/useAutomationStore';
import { t } from '../../utils/i18n';
import PlatformIcon from '../Panel/PlatformIcon';

const ActionNode = memo(function ActionNode({ id, data }) {
	const openPanel = useAutomationStore(s => s.openPanel);
	const panelMode = useAutomationStore(s => s.panelMode);
	const platformName = useAutomationStore(s => s.getSelectedPlatformName());
	const actions = data.actions ?? [];
	const displayPlatform = platformName
		? platformName.charAt(0).toUpperCase() + platformName.slice(1)
		: 'Platform';
	const isDmSupported = useAutomationStore(s => s.isDmSupported());

	return (
		<div className={`w-56 rounded-[10px] bg-background sm:w-64 ${panelMode === 'action-config' ? 'outline outline-[3px] outline-[#1CA685]' : ''}`}>
			<Handle type="target" position={Position.Left} className="!size-2.5 !bg-foreground/30" />

			{/* Node Header */}
			{!actions.length ?
				<div className="flex h-[52px] items-center gap-2 border-b p-4 text-2xs font-medium leading-tight text-heading-foreground">
					<svg className="text-primary" width="11" height="14" viewBox="0 0 11 14" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
						<path d="M6.08333 0.750053V5.41672H10.0833L4.75 12.7501V8.08339H0.75L6.08333 0.750053Z"/>
					</svg>
					{t('do', 'Do')}
				</div>
				:
				<div
					className="flex gap-2.5 px-5 pt-5"
					onClick={() => openPanel('action-config', id)}
				>
					<span className="inline-flex size-6 shrink-0">
						<PlatformIcon platform={platformName} />
					</span>
					<div className='grow'>
						<p className='m-0 text-2xs font-medium opacity-60'>
							{displayPlatform}
						</p>
						<p className='m-0 text-2xs font-medium'>
							{isDmSupported ? t('send_dm', 'Send DM') : t('send_reply', 'Send Reply')}
						</p>
					</div>
				</div>
			}

			{/* Node Body */}
			<div className="p-4">
				{actions.length === 0 ? (
					<div>
						<p className="mb-3 text-2xs">
							{t('do_desc', 'Perform this action once the automation starts')}
						</p>
						<button
							className="w-full rounded-[10px] border border-dashed p-4 text-center text-[12px] font-medium leading-none text-primary transition hover:border-primary hover:bg-primary hover:text-primary-foreground"
							onClick={() => openPanel('action-config', id)}
						>
							+ {t('add_action', 'Add Action')}
						</button>
					</div>
				) : (
					<div>
						<div
							className="space-y-2"
							onClick={() => openPanel('action-config', id)}
						>
							{actions.map((action, index) => (
								<ActionPreview key={index} action={action} />
							))}
						</div>
					</div>
				)}
			</div>
		</div>
	);
});

function ActionPreview({ action }) {
	switch (action.type) {
		case 'text':
			return (
				<p className="line-clamp-[8] text-2xs leading-relaxed">
					{action.content.text || 'Text message...'}
				</p>
			);
		case 'button':
			return (
				<div className="flex w-full items-center justify-center rounded-full px-3 py-2 text-center text-xs font-medium shadow-xs">
					{action.content.label || 'Button'}
				</div>
			);
		case 'image':
			return action.content.url ? (
				<img className="h-20 w-full rounded-lg object-cover" src={action.content.url} alt="" />
			) : (
				<div className="flex h-16 items-center justify-center rounded-lg bg-foreground/5">
					<svg className="size-5 text-foreground/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
						<rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
						<circle cx="8.5" cy="8.5" r="1.5" />
						<polyline points="21 15 16 10 5 21" />
					</svg>
				</div>
			);
		case 'quick_replies':
			return (
				<div className="flex flex-wrap gap-1">
					{(action.content.items || []).map((item, i) => (
						<span key={i} className="relative flex min-h-8 items-center rounded-[10px] border border-input-border p-2 text-2xs font-medium">
							{item}
						</span>
					))}
				</div>
			);
		case 'delay':
			return (
				<div className="flex items-center gap-1.5 text-[10px] font-medium">
					<svg className="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
						<circle cx="12" cy="12" r="10" />
						<polyline points="12 6 12 12 16 14" />
					</svg>
					<span>{action.content.seconds || 5}s</span>
				</div>
			);
		default:
			return null;
	}
}

export default ActionNode;
