import React, { useState, useEffect, useCallback } from 'react';
import useAutomationStore from '../../store/useAutomationStore';
import { fetchPosts } from '../../hooks/useAutomationApi';
import { NODE_TYPES } from '../../utils/constants';
import { t } from '../../utils/i18n';

export default function PostSelectorModal() {
	const postSelectorOpen = useAutomationStore(s => s.postSelectorOpen);
	const setPostSelectorOpen = useAutomationStore(s => s.setPostSelectorOpen);
	const selectedAccountId = useAutomationStore(s => s.selectedAccountId);
	const nodes = useAutomationStore(s => s.nodes);
	const updateNodeData = useAutomationStore(s => s.updateNodeData);

	const [ search, setSearch ] = useState('');
	const [ posts, setPosts ] = useState([]);
	const [ loading, setLoading ] = useState(false);
	const [ loadedForAccount, setLoadedForAccount ] = useState(null);

	const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);

	const loadPosts = useCallback(async () => {
		if (!selectedAccountId) return;
		setLoading(true);
		try {
			const data = await fetchPosts(selectedAccountId, search);
			setPosts(data);
		} catch {
			setPosts([]);
		} finally {
			setLoading(false);
		}
	}, [ selectedAccountId, search ]);

	useEffect(() => {
		if (postSelectorOpen && loadedForAccount !== selectedAccountId) {
			setSearch('');
			loadPosts().then(() => setLoadedForAccount(selectedAccountId));
		}
	}, [ postSelectorOpen ]);

	function selectPost(post) {
		if (triggerNode) {
			updateNodeData(triggerNode.id, {
				triggerPostId: post.platform_post_id || post.id,
				triggerPostData: {
					content: post.content?.substring(0, 100),
					image: post.image,
					type: post.post_type,
				},
			});
		}
		setPostSelectorOpen(false);
	}

	function handleSearchFormSubmit(event) {
		event.preventDefault();
		setSearch(search);
		loadPosts();
	}

	if (!postSelectorOpen) return null;

	return (
		<div className="absolute start-0 top-full z-10 mt-1 w-full rounded-[10px] bg-surface-background p-3 shadow-[0_25px_55px_hsl(0_0%_0%/15%)]">
			<form action="#" className='relative mb-3 w-full' onSubmit={handleSearchFormSubmit}>
				<input type="text" name="search" className='h-10 w-full rounded-[10px] bg-foreground/5 px-3 py-2 pe-10 text-2xs font-medium placeholder:text-foreground' placeholder={t('search_posts', 'Search Posts')} />
				<svg className="pointer-events-none absolute end-5 top-1/2 size-3 -translate-y-1/2" width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
					<path d="M11.2667 12L6.88333 7.61667C6.55 7.90556 6.16111 8.13056 5.71667 8.29167C5.27222 8.45278 4.8 8.53333 4.3 8.53333C3.1 8.53333 2.08333 8.11667 1.25 7.28333C0.416667 6.45 0 5.44444 0 4.26667C0 3.08889 0.416667 2.08333 1.25 1.25C2.08333 0.416667 3.09444 0 4.28333 0C5.46111 0 6.46389 0.416667 7.29167 1.25C8.11944 2.08333 8.53333 3.08889 8.53333 4.26667C8.53333 4.74444 8.45556 5.20556 8.3 5.65C8.14444 6.09444 7.91111 6.51111 7.6 6.9L12 11.2667L11.2667 12ZM4.28333 7.53333C5.18333 7.53333 5.95 7.21389 6.58333 6.575C7.21667 5.93611 7.53333 5.16667 7.53333 4.26667C7.53333 3.36667 7.21667 2.59722 6.58333 1.95833C5.95 1.31944 5.18333 1 4.28333 1C3.37222 1 2.59722 1.31944 1.95833 1.95833C1.31944 2.59722 1 3.36667 1 4.26667C1 5.16667 1.31944 5.93611 1.95833 6.575C2.59722 7.21389 3.37222 7.53333 4.28333 7.53333Z"/>
				</svg>
			</form>

			{/* Posts List */}
			<div>
				{loading && (
					<div className="py-12 text-center text-2xs font-medium">
						<svg className="mx-auto mb-2 size-6 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12a9 9 0 1 1-6.219-8.56" /></svg>
						{t('loading_posts', 'Loading posts...')}
					</div>
				)}

				{!loading && posts.length === 0 && (
					<div className="py-12 text-center text-2xs font-medium">
						<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className="mx-auto mb-2 size-10"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M8 4h11a2 2 0 1 1 0 4h-7m-4 0h-3a2 2 0 0 1 -.826 -3.822" /><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 1.824 -1.18m.176 -3.82v-7" /><path d="M10 12h2" /><path d="M3 3l18 18" /></svg>
						{t('no_posts_found', 'No published posts found')}
					</div>
				)}

				{!loading && posts.length > 0 && (
					<div className='max-h-72 overflow-y-auto rounded-[10px] border p-1'>
						{posts.map(post => (
							<button
								key={post.id}
								className="flex w-full items-center gap-3 rounded-lg px-1.5 py-2 text-start transition-colors hover:bg-foreground/5"
								onClick={() => selectPost(post)}
							>
								<span className="block w-full min-w-0 flex-1 overflow-hidden truncate">
									<span className="block text-4xs opacity-50">
										{post.post_type || 'Post'}
									</span>
									<span className="w-full truncate text-[12px] font-medium text-heading-foreground">
										{post.content?.substring(0, 120) || 'No caption'}
									</span>
								</span>
								{post.image ? (
									<img className="size-10 shrink-0 rounded object-cover" src={post.image} alt="" />
								) : (
									<span className="grid size-10 shrink-0 place-items-center rounded bg-foreground/5">
										<svg className="size-6 text-foreground/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
											<rect x="3" y="3" width="18" height="18" rx="2" ry="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21 15 16 10 5 21" />
										</svg>
									</span>
								)}
							</button>
						))}
					</div>
				)}
			</div>
		</div>
	);
}
