import React, { useState, useRef } from 'react';
import useAutomationStore from '../../store/useAutomationStore';
import { NODE_TYPES, VARIABLES } from '../../utils/constants';
import { t } from '../../utils/i18n';
import { uploadImage } from '../../hooks/useAutomationApi';

export default function ActionConfigPanel() {
	const nodes = useAutomationStore(s => s.nodes);
	const selectedNodeId = useAutomationStore(s => s.selectedNodeId);
	const updateNodeData = useAutomationStore(s => s.updateNodeData);
	const closePanel = useAutomationStore(s => s.closePanel);
	const isDmSupported = useAutomationStore(s => s.isDmSupported());

	const actionNode = nodes.find(n => n.type === NODE_TYPES.ACTION);
	const nodeId = selectedNodeId || actionNode?.id;
	const actions = actionNode?.data?.actions ?? [];

	const [ uploadingIndex, setUploadingIndex ] = useState(null);

	function setActions(newActions) {
		if (nodeId) updateNodeData(nodeId, { actions: newActions });
	}

	function addAction(type) {
		const defaults = {
			text: { text: '' },
			button: { label: '', url: '' },
			image: { path: '', url: '' },
			quick_replies: { items: [] },
			delay: { seconds: 5 },
		};
		setActions([ ...actions, { type, content: defaults[type] || {} } ]);
	}

	function removeAction(index) {
		setActions(actions.filter((_, i) => i !== index));
	}

	function moveAction(index, direction) {
		const newIndex = index + direction;
		if (newIndex < 0 || newIndex >= actions.length) return;
		const copy = [ ...actions ];
		[ copy[index], copy[newIndex] ] = [ copy[newIndex], copy[index] ];
		setActions(copy);
	}

	function updateAction(index, content) {
		setActions(actions.map((a, i) => i === index ? { ...a, content: { ...a.content, ...content } } : a));
	}

	async function handleUploadImage(index, file) {
		if (!file) return;
		setUploadingIndex(index);
		try {
			const d = await uploadImage(file);
			if (d.data) {
				setActions(actions.map((a, i) => i === index ? { ...a, content: { ...d.data } } : a));
			}
		} catch {
			if (window.toastr) window.toastr.error('Image upload failed.');
		} finally {
			setUploadingIndex(null);
		}
	}

	return (
		<div className="flex h-full flex-col">
			{/* Platform Restriction Notice */}
			{!isDmSupported && (
				<div className="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-xs text-yellow-800">
					<div className="mb-1 flex items-center gap-1.5 font-medium">
						<svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></svg>
						{t('dm_not_available', 'DM Not Available')}
					</div>
					<p class="mb-0">{t('dm_not_available_desc', 'This platform does not support automated DMs. You can still configure public comment replies from the trigger settings.')}</p>
				</div>
			)}

			<p className="mb-5 w-full border-b py-3.5 text-[12px] font-semibold leading-tight text-heading-foreground">
				{isDmSupported
					? t('send_dm', 'Send DM')
					: t('send_reply', 'Send Reply')}
			</p>

			{/* Action Toolbar */}
			<div className="mb-5 flex flex-wrap gap-2.5">
				{isDmSupported && (
					<ToolbarButton onClick={() => addAction('button')}>+ {t('add_button', 'Add Button')}</ToolbarButton>
				)}
				<ToolbarButton onClick={() => addAction('text')}>+ {t('add_text', 'Add Text')}</ToolbarButton>
				{isDmSupported && (
					<ToolbarButton onClick={() => addAction('image')}>+ {t('add_image', 'Add Image')}</ToolbarButton>
				)}
				<ToolbarButton onClick={() => addAction('delay')}>+ {t('add_delay', 'Add Delay')}</ToolbarButton>
				{isDmSupported && (
					<ToolbarButton onClick={() => addAction('quick_replies')}>+ {t('add_quick_replies', 'Add Quick Replies')}</ToolbarButton>
				)}
			</div>

			{/* Actions List */}
			<div className="space-y-4">
				{actions.map((action, index) => (
					<ActionItem
						key={index}
						action={action}
						index={index}
						total={actions.length}
						uploadingIndex={uploadingIndex}
						onUpdate={content => updateAction(index, content)}
						onRemove={() => removeAction(index)}
						onMove={dir => moveAction(index, dir)}
						onUpload={file => handleUploadImage(index, file)}
						onClearImage={() => updateAction(index, { path: '', url: '' })}
					/>
				))}
			</div>

			{/* Save Button */}
			<div className="sticky bottom-0 z-20 mt-auto pt-10">
				<button
					className="lqd-btn lqd-btn-primary mt-auto w-full rounded-button bg-gradient-to-r from-gradient-from via-gradient-via to-gradient-to p-2.5 text-xs font-medium text-primary-foreground transition hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5"
					onClick={closePanel}
				>
					{t('save', 'Save')}
				</button>
			</div>
		</div>
	);
}

function ToolbarButton({ children, onClick }) {
	return (
		<button
			className="inline-flex items-center gap-1 rounded-button border px-3 py-2 text-[12px] font-medium transition hover:border-primary hover:bg-primary hover:text-primary-foreground"
			onClick={onClick}
		>
			{children}
		</button>
	);
}

function ActionItem({ action, index, total, uploadingIndex, onUpdate, onRemove, onMove, onUpload, onClearImage }) {
	const [ newQrItem, setNewQrItem ] = useState('');
	const textareaRef = useRef(null);

	function addQuickReply() {
		const item = newQrItem.trim();
		if (item) {
			const items = [ ...(action.content.items || []), item ];
			onUpdate({ items });
			setNewQrItem('');
		}
	}

	function removeQuickReply(i) {
		const items = (action.content.items || []).filter((_, idx) => idx !== i);
		onUpdate({ items });
	}

	return (
		<div className="group relative rounded-[10px] border border-dashed p-3">
			<button onClick={onRemove} title={t('delete', 'Delete')} className="invisible absolute -end-3 -top-3 z-10 inline-grid size-7 place-items-center rounded-full bg-surface-background opacity-0 shadow-xs transition hover:scale-110 group-hover:visible group-hover:opacity-100">
				<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className="size-4"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
			</button>

			<div className="mb-3 flex items-center justify-between">
				<p className="mb-1 flex items-center gap-2">
					<span className="text-xs font-semibold capitalize text-heading-foreground">
						{action.type.replace('_', ' ')}
					</span>
				</p>
				{action.type === 'text' && (
					<div className="group/variables relative">
						<p className="m-0 flex cursor-default items-center gap-1 text-[12px] font-medium text-heading-foreground">
							<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className="size-3"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
							{t('available_variables', 'Available Variables')}
						</p>
						<div className="invisible absolute end-0 top-full z-10 mt-1 flex min-w-72 translate-y-1 flex-wrap gap-1 rounded-lg border bg-surface-background p-4 opacity-0 shadow-xl shadow-black/5 transition before:absolute before:inset-x-0 before:bottom-full before:h-1 group-hover/variables:visible group-hover/variables:translate-y-0 group-hover/variables:opacity-100">
							{VARIABLES.map((v, i) => (
								<button type="button" key={i} className="rounded bg-foreground/[3%] px-1 py-0.5 text-[12px] font-medium transition hover:bg-primary hover:text-primary-foreground" onClick={() => { onUpdate({ text: (action.content.text ?? '') + v }); textareaRef.current?.focus(); }}>
									{v}
								</button>
							))}
						</div>
					</div>
				)}
			</div>

			{/* Text */}
			{action.type === 'text' && (
				<textarea
					ref={textareaRef}
					className="w-full rounded-input border border-input-border bg-input-background px-5 py-4 font-medium text-foreground placeholder:text-foreground/50 sm:text-2xs"
					rows="3"
					value={action.content.text ?? ''}
					onChange={e => onUpdate({ text: e.target.value })}
					placeholder="Hey {first_name}! Thanks for commenting..."
				/>
			)}

			{/* Button */}
			{action.type === 'button' && (
				<div className="space-y-3">
					<div>
						<input
							className="h-10 w-full rounded-input border border-input-border bg-input-background px-4 py-2 font-medium placeholder:text-foreground sm:text-2xs"
							type="text"
							value={action.content.label ?? ''}
							placeholder={t('label', 'Label')}
							onChange={e => onUpdate({ label: e.target.value })}
						/>
					</div>
					<div>
						<input
							className="h-10 w-full rounded-input border border-input-border bg-input-background px-4 py-2 font-medium placeholder:text-foreground sm:text-2xs"
							type="url"
							value={action.content.url ?? ''}
							placeholder={t('url', 'URL')}
							onChange={e => onUpdate({ url: e.target.value })}
						/>
					</div>
				</div>
			)}

			{/* Image */}
			{action.type === 'image' && (
				<div>
					{action.content.url ? (
						<div className="relative">
							<img className="h-32 w-full rounded-lg object-cover" src={action.content.url} alt="" />
							<button
								className="absolute end-2 top-2 grid size-6 place-items-center rounded-full bg-black/50 text-white hover:bg-black/70"
								onClick={onClearImage}
							>
								<svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
							</button>
						</div>
					) : uploadingIndex === index ? (
						<div className="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-primary/30 bg-primary/5 px-4 py-8">
							<svg className="mb-2 size-6 animate-spin text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12a9 9 0 1 1-6.219-8.56" /></svg>
							<span className="text-xs text-primary">{t('uploading', 'Uploading...')}</span>
						</div>
					) : (
						<label className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-border px-4 py-8 text-foreground/40 transition-colors hover:border-primary/30 hover:text-primary">
							<svg className="mb-2 size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="16 16 12 12 8 16" /><line x1="12" y1="12" x2="12" y2="21" /><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3" /></svg>
							<span className="text-xs">{t('click_to_upload', 'Click to upload image')}</span>
							<input className="hidden" type="file" accept="image/*" onChange={e => onUpload(e.target.files[0])} />
						</label>
					)}
				</div>
			)}

			{/* Delay */}
			{action.type === 'delay' && (
				<div className="flex items-center gap-2">
					<input
						className="grow rounded-input border border-input-border bg-input-background px-3 py-2 font-medium sm:text-2xs"
						type="number"
						min="1"
						max="60"
						value={action.content.seconds ?? 5}
						onChange={e => onUpdate({ seconds: parseInt(e.target.value, 10) || 5 })}
					/>
					<span className="shrink-0 text-2xs font-medium opacity-50">
						{t('seconds', 'seconds')}
					</span>
				</div>
			)}

			{/* Quick Replies */}
			{action.type === 'quick_replies' && (
				<div>
					<div className="mb-2 flex flex-col gap-1.5">
						{(action.content.items || []).map((item, i) => (
							<div key={i} className="group/item relative min-h-12 rounded-[10px] border border-input-border bg-input-background px-4 py-3">
								<p className="m-0 cursor-default text-2xs font-medium">
									{item}
								</p>
								<button class="invisible absolute -end-3 -top-3 z-10 inline-grid size-7 place-items-center rounded-full bg-surface-background opacity-0 shadow-xs transition hover:scale-110 group-hover/item:visible group-hover/item:opacity-100" onClick={() => removeQuickReply(i)}>
									<svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
								</button>
							</div>
						))}
					</div>
					<div className="flex">
						<input
							className="h-[42px] flex-1 rounded-none rounded-s-[10px] border border-e-0 border-input-border bg-input-background bg-none px-3 py-2 font-medium placeholder:text-foreground sm:text-[12px]"
							type="text"
							placeholder={t('add_quick_replies', 'Add Quick Replies')}
							value={newQrItem}
							onChange={e => setNewQrItem(e.target.value)}
							onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addQuickReply())}
						/>
						<button className="inline-grid w-10 place-items-center rounded-e-[10px] border border-input-border bg-input-background transition hover:border-primary hover:bg-primary hover:text-primary-foreground disabled:pointer-events-none disabled:opacity-50" onClick={addQuickReply} disabled={!newQrItem}>
							<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="currentColor" className="size-4">
								<path stroke="none" d="M0 0h24v24H0z" fill="none" />
								<path d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 0 1 0 2h-6v6a1 1 0 0 1 -2 0v-6h-6a1 1 0 0 1 0 -2h6v-6a1 1 0 0 1 1 -1" />
							</svg>
						</button>
					</div>
				</div>
			)}
		</div>
	);
}

function IconButton({ children, onClick, disabled, title, className = '' }) {
	return (
		<button
			className={`grid size-6 place-items-center rounded text-foreground/40 hover:bg-foreground/5 hover:text-foreground disabled:opacity-30 ${className}`}
			onClick={onClick}
			disabled={disabled}
			title={title}
		>
			{children}
		</button>
	);
}
