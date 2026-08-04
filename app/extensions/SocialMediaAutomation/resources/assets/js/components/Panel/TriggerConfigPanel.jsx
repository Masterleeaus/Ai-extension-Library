import React, { useState, useRef, useEffect } from 'react';
import useAutomationStore from '../../store/useAutomationStore';
import { NODE_TYPES } from '../../utils/constants';
import { t } from '../../utils/i18n';
import PostSelectorModal from './PostSelectorModal';

export default function TriggerConfigPanel() {
	const nodes = useAutomationStore(s => s.nodes);
	const selectedNodeId = useAutomationStore(s => s.selectedNodeId);
	const updateNodeData = useAutomationStore(s => s.updateNodeData);
	const closePanel = useAutomationStore(s => s.closePanel);
	const postSelectorOpen = useAutomationStore(s => s.postSelectorOpen);
	const setPostSelectorOpen = useAutomationStore(s => s.setPostSelectorOpen);

	const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);
	const nodeId = selectedNodeId || triggerNode?.id;
	const data = triggerNode?.data ?? {};

	const [ tab, setTab ] = useState(0);
	const [ newIncludeKw, setNewIncludeKw ] = useState('');
	const [ newExcludeKw, setNewExcludeKw ] = useState('');
	const [ newReply, setNewReply ] = useState('');

	const triggerTarget = data.triggerTarget ?? 'all_posts';
	const keywordMode = data.keywordMode ?? 'any';
	const includeKeywords = data.includeKeywords ?? [];
	const excludeKeywords = data.excludeKeywords ?? [];
	const enablePublicReplies = data.enablePublicReplies ?? false;
	const replyVariations = data.replyVariations ?? [];
	const triggerPostData = data.triggerPostData ?? null;

	function update(partial) {
		if (nodeId) updateNodeData(nodeId, partial);
	}

	function addIncludeKeyword() {
		const kw = newIncludeKw.trim();
		if (kw && !includeKeywords.includes(kw)) {
			update({ includeKeywords: [ ...includeKeywords, kw ] });
		}
		setNewIncludeKw('');
	}

	function removeIncludeKeyword(index) {
		update({ includeKeywords: includeKeywords.filter((_, i) => i !== index) });
	}

	function addExcludeKeyword() {
		const kw = newExcludeKw.trim();
		if (kw && !excludeKeywords.includes(kw)) {
			update({ excludeKeywords: [ ...excludeKeywords, kw ] });
		}
		setNewExcludeKw('');
	}

	function removeExcludeKeyword(index) {
		update({ excludeKeywords: excludeKeywords.filter((_, i) => i !== index) });
	}

	function addReplyVariation() {
		const v = newReply.trim();
		if (v) {
			update({ replyVariations: [ ...replyVariations, v ] });
		}
		setNewReply('');
	}

	function removeReplyVariation(index) {
		update({ replyVariations: replyVariations.filter((_, i) => i !== index) });
	}

	const isLastTab = tab === 2;

	const openPanel = useAutomationStore(s => s.openPanel);
	const actionNode = nodes.find(n => n.type === NODE_TYPES.ACTION);

	function handleContinueOrSave() {
		if (isLastTab) {
			update({ configured: true });
			openPanel('action-config', actionNode?.id);
		} else {
			setTab(tab + 1);
		}
	}

	const tabs = [
		{ id: 'select-post', label: t('select_post', 'Select Post') },
		{ id: 'set-trigger', label: t('set_trigger', 'Set Trigger') },
		{ id: 'leave-comment', label: t('leave_comment', 'Leave Comment') },
	];

	return (
		<div className='flex h-full flex-col'>
			{/* Breadcrumb Tabs */}
			<div className="mb-5 flex w-full items-center gap-2 overflow-x-auto border-b py-3.5">
				{tabs.map((tabItem, i) => (
					<React.Fragment key={tabItem.id}>
						{i > 0 && (
							<svg className={`size-4 opacity-50 [&.active]:opacity-100 ${tab >= i ? 'active' : ''}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
								<polyline points="9 18 15 12 9 6" />
							</svg>
						)}
						<button
							className={`whitespace-nowrap text-[12px] font-semibold leading-tight text-foreground opacity-50 transition [&.active]:opacity-100 ${tab >= i ? 'active' : ''}`}
							onClick={() => setTab(i)}
						>
							{tabItem.label}
						</button>
					</React.Fragment>
				))}
			</div>

			{/* Select Post Tab */}
			{tab === 0 && (
				<div>
					<div className="space-y-5">
						<div>
							<label htmlFor="specific_post" className='mb-2 block w-full text-2xs font-semibold leading-tight'>
								{t('select_post', 'Select Post')}
							</label>
							<select
								className='h-10 w-full rounded-input border border-input-border bg-input-background px-4 py-2 text-2xs font-medium'
								name="trigger_target"
								value={triggerTarget}
								id="specific_post"
								onChange={event => update({ triggerTarget: event.target.value })}
							>
								<option value="specific_post">{t('specific_post', 'Specific Post')}</option>
								<option value="all_posts">{t('all_posts', 'All Posts')}</option>
							</select>
						</div>

						{/* Post Selector (for specific post) */}
						{triggerTarget === 'specific_post' && (
							<div>
								<label className='mb-2 block w-full text-2xs font-semibold leading-tight'>
									{t('target_post', 'Target Post')}
								</label>
								<PostSelectorDropdown
									postSelectorOpen={postSelectorOpen}
									setPostSelectorOpen={setPostSelectorOpen}
									triggerPostData={triggerPostData}
								/>
							</div>
						)}
					</div>
				</div>
			)}

			{/* Set Trigger Tab */}
			{tab === 1 && (
				<div className="space-y-6">
					<div>
						<label htmlFor="keyword_mode" className='mb-2 block w-full text-2xs font-semibold leading-tight'>
							{t('trigger', 'Trigger')}
						</label>
						<select
							className='h-10 w-full rounded-input border border-input-border bg-input-background px-4 py-2 text-2xs font-medium'
							id="keyword_mode"
							value={keywordMode}
							onChange={e => update({ keywordMode: e.target.value })}
						>
							<option value="any">{t('keyword_any', 'Any Comment')}</option>
							<option value="specific">{t('keyword_specific', 'Specific Keywords')}</option>
						</select>
					</div>

					{/* Include Keywords */}
					{keywordMode === 'specific' && (
						<>
							<div className="rounded-[10px] border-2 border-dashed p-3">
								<label htmlFor="include_keywords" className='mb-2.5 block w-full text-2xs font-semibold leading-tight'>
									{t('includes', 'Includes')}
								</label>
								<div className="mb-2.5 flex flex-wrap gap-2 empty:hidden">
									{includeKeywords.map((kw, index) => (
										<span key={index} className="group relative inline-flex rounded-[10px] border px-4 py-1.5">
											{kw}
											<button className="invisible absolute -end-2 -top-2 z-10 inline-grid size-[18px] place-items-center rounded-full bg-surface-background opacity-0 shadow-xs transition hover:scale-125 group-hover:visible group-hover:opacity-100" onClick={() => removeIncludeKeyword(index)}>
												<svg className="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
											</button>
										</span>
									))}
								</div>
								<div className="flex">
									<input
										className="h-[42px] flex-1 rounded-none rounded-s-[10px] border border-e-0 border-input-border bg-input-background bg-none px-3 py-2 font-medium placeholder:text-foreground sm:text-[12px]"
										type="text"
										id="include_keywords"
										placeholder={t('add_keyword', 'Add Keyword')}
										value={newIncludeKw}
										onChange={e => setNewIncludeKw(e.target.value)}
										onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addIncludeKeyword())}
									/>
									<button className="inline-grid w-10 place-items-center rounded-e-[10px] border border-input-border bg-input-background transition hover:border-primary hover:bg-primary hover:text-primary-foreground disabled:pointer-events-none disabled:opacity-50" onClick={addIncludeKeyword} title={t('add_variation', 'Add Variation')} disabled={!newIncludeKw}>
										<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="currentColor" className="size-4">
											<path stroke="none" d="M0 0h24v24H0z" fill="none" />
											<path d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 0 1 0 2h-6v6a1 1 0 0 1 -2 0v-6h-6a1 1 0 0 1 0 -2h6v-6a1 1 0 0 1 1 -1" />
										</svg>
									</button>
								</div>
							</div>

							{/* Exclude Keywords */}
							<div className="rounded-[10px] border-2 border-dashed p-3">
								<label htmlFor="include_keywords" className='mb-2.5 block w-full text-2xs font-semibold leading-tight'>
									{t('excludes', 'Excludes')}
								</label>
								<div className="mb-2.5 flex flex-wrap gap-2 empty:hidden">
									{excludeKeywords.map((kw, index) => (
										<span key={index} className="group relative inline-flex rounded-[10px] border px-4 py-1.5">
											{kw}
											<button className="invisible absolute -end-2 -top-2 z-10 inline-grid size-[18px] place-items-center rounded-full bg-surface-background opacity-0 shadow-xs transition hover:scale-125 group-hover:visible group-hover:opacity-100" onClick={() => removeExcludeKeyword(index)}>
												<svg className="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
											</button>
										</span>
									))}
								</div>
								<div className="flex">
									<input
										className="h-[42px] flex-1 rounded-none rounded-s-[10px] border border-e-0 border-input-border bg-input-background bg-none px-3 py-2 font-medium placeholder:text-foreground sm:text-[12px]"
										type="text"
										placeholder={t('add_keyword', 'Add Keyword')}
										value={newExcludeKw}
										onChange={e => setNewExcludeKw(e.target.value)}
										onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addExcludeKeyword())}
									/>
									<button className="inline-grid w-10 place-items-center rounded-e-[10px] border border-input-border bg-input-background transition hover:border-primary hover:bg-primary hover:text-primary-foreground disabled:pointer-events-none disabled:opacity-50" onClick={addExcludeKeyword} title={t('add_variation', 'Add Variation')} disabled={!newExcludeKw}>
										<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="currentColor" className="size-4">
											<path stroke="none" d="M0 0h24v24H0z" fill="none" />
											<path d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 0 1 0 2h-6v6a1 1 0 0 1 -2 0v-6h-6a1 1 0 0 1 0 -2h6v-6a1 1 0 0 1 1 -1" />
										</svg>
									</button>
								</div>
							</div>
						</>
					)}
				</div>
			)}

			{/* Leave Comment Tab */}
			{tab === 2 && (
				<div className="space-y-4">
					<div className="flex items-center justify-between">
						<label className="relative flex w-full cursor-pointer items-center justify-between gap-1 text-2xs font-semibold leading-tight" htmlFor='enable_public_replies'>
							<input
								className="peer sr-only"
								id="enable_public_replies"
								type="checkbox"
								checked={enablePublicReplies}
								onChange={e => update({ enablePublicReplies: e.target.checked })}
							/>
							{t('enable_public_replies', 'Enable Public Replies')}
							<div className="relative h-[18px] w-[34px] rounded-full bg-foreground/10 after:absolute after:start-1 after:top-[5px] after:size-2 after:rounded-full after:bg-foreground/80 after:transition-all peer-checked:bg-primary/10  peer-checked:after:translate-x-[18px] peer-checked:after:bg-primary peer-focus:ring-2 peer-focus:ring-primary/20 rtl:peer-checked:after:-translate-x-full" />
						</label>
					</div>

					{enablePublicReplies && (
						<div>
							<div className="mb-3 space-y-3">
								<p className="text-2xs">
									{t('replies_rotate_desc', 'Replies are randomly rotated to look natural')}
								</p>

								{replyVariations.map((reply, index) => (
									<div key={index} className="group relative min-h-12 rounded-[10px] border border-input-border bg-input-background px-4 py-3">
										<p className="m-0 cursor-default text-2xs font-medium">
											{reply}
										</p>
										<button className="invisible absolute -end-3 -top-3 z-10 inline-grid size-7 place-items-center rounded-full bg-surface-background opacity-0 shadow-xs transition hover:scale-110 group-hover:visible group-hover:opacity-100" onClick={() => removeReplyVariation(index)}>
											<svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
										</button>
									</div>
								))}
							</div>
							<div className="flex">
								<input
									className="h-12 flex-1 rounded-none rounded-s-[10px] border border-e-0 border-input-border bg-input-background bg-none px-3 py-2 font-medium placeholder:text-foreground sm:text-[12px]"
									type="text"
									placeholder={t('add_keyword', 'Add Keyword')}
									value={newReply}
									onChange={e => setNewReply(e.target.value)}
									onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addReplyVariation())}
								/>
								<button className="inline-grid w-10 place-items-center rounded-e-[10px] border border-input-border bg-input-background transition hover:border-primary hover:bg-primary hover:text-primary-foreground disabled:pointer-events-none disabled:opacity-50" onClick={addReplyVariation}  title={t('add_variation', 'Add Variation')} disabled={!newReply}>
									<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="currentColor" className="size-4">
										<path stroke="none" d="M0 0h24v24H0z" fill="none" />
										<path d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 0 1 0 2h-6v6a1 1 0 0 1 -2 0v-6h-6a1 1 0 0 1 0 -2h6v-6a1 1 0 0 1 1 -1" />
									</svg>
								</button>
							</div>
						</div>
					)}
				</div>
			)}

			{/* Continue Button */}
			<div className="mt-auto pt-8">
				<button
					className="lqd-btn lqd-btn-primary mt-auto w-full rounded-button bg-gradient-to-r from-gradient-from via-gradient-via to-gradient-to p-2.5 text-xs font-medium text-primary-foreground transition hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5"
					onClick={handleContinueOrSave}
				>
					{isLastTab ? t('save', 'Save') : t('continue', 'Continue')}
				</button>
			</div>
		</div>
	);
}

function PostSelectorDropdown({ postSelectorOpen, setPostSelectorOpen, triggerPostData }) {
	const ref = useRef(null);

	useEffect(() => {
		if (!postSelectorOpen) return;
		function handleClick(e) {
			if (ref.current && !ref.current.contains(e.target)) {
				setPostSelectorOpen(false);
			}
		}
		document.addEventListener('mousedown', handleClick);
		return () => document.removeEventListener('mousedown', handleClick);
	}, [ postSelectorOpen, setPostSelectorOpen ]);

	return (
		<div className="relative" ref={ref}>
			<button
				className='flex h-10 w-full items-center gap-1 overflow-hidden rounded-input border border-input-border bg-input-background px-4 py-2 text-start text-2xs font-medium'
				onClick={() => setPostSelectorOpen(!postSelectorOpen)}
			>
				<span className='block w-full truncate'>
					{triggerPostData ? triggerPostData.content || t('selected_post', 'Selected post') : t('no_post_selected', 'No post selected')}
				</span>
				<svg className="ms-auto size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<polyline points="6 9 12 15 18 9" />
				</svg>
			</button>

			{postSelectorOpen && <PostSelectorModal />}
		</div>
	);
}
