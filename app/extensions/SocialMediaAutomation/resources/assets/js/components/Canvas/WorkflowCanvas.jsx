import { ReactFlow, Background, Controls } from '@xyflow/react';
import '@xyflow/react/dist/style.css';

import nodeTypes from '../Nodes/nodeTypes';
import edgeTypes from '../Edges/edgeTypes';
import useAutomationStore from '../../store/useAutomationStore';
import { NODE_TYPES } from '../../utils/constants';
import { t } from '../../utils/i18n';

export default function WorkflowCanvas() {
	const nodes = useAutomationStore(s => s.nodes);
	const edges = useAutomationStore(s => s.edges);
	const onNodesChange = useAutomationStore(s => s.onNodesChange);
	const onEdgesChange = useAutomationStore(s => s.onEdgesChange);
	const onConnect = useAutomationStore(s => s.onConnect);
	const addDelayNode = useAutomationStore(s => s.addDelayNode);
	const platformName = useAutomationStore(s => s.getSelectedPlatformName());
	const hasPermissions = useAutomationStore(s => s.hasPermissions());
	const permissionInfo = useAutomationStore(s => s.getPermissionInfo());

	const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);
	const hasDelay = nodes.some(n => n.type === NODE_TYPES.DELAY);
	const triggerConfigured = triggerNode?.data?.configured ?? false;


	// Platform warning
	const platformWarning = (() => {
		if (platformName === 'tiktok') return 'DM not available on TikTok. Only public comment replies supported.';
		if (platformName === 'linkedin') return 'Limited automation support. Comment monitoring via polling only.';
		if (platformName === 'x') return 'Limited DM support. API restrictions apply.';
		return null;
	})();

	const rateLimit = permissionInfo?.rate_limit ?? '';
	const missingScopes = permissionInfo?.missing_scopes ?? [];

	return (
		<div className="relative flex h-full min-h-0 flex-1 overflow-hidden bg-foreground/5">
			{/* Dot Grid Background */}
			<div
				className="pointer-events-none absolute inset-0"
				style={{ backgroundImage: 'radial-gradient(circle, hsl(var(--foreground) / 0.1) 1px, transparent 1px)', backgroundSize: '14px 14px' }}
			></div>

			{/* Platform Warning */}
			{platformWarning && (
				<div className="absolute inset-x-3 top-3 z-20 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 shadow-sm sm:inset-x-auto sm:start-6 sm:top-4 sm:max-w-xl">
					<svg className="me-1 inline size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></svg>
					{platformWarning}
					{rateLimit && (
						<p className="mt-1 text-xs opacity-70">{t('rate_limit', 'Rate limit')}: {rateLimit}</p>
					)}
				</div>
			)}

			{/* Permission Warning */}
			{!hasPermissions && missingScopes.length > 0 && (
				<div className="absolute inset-x-3 top-3 z-20 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm sm:inset-x-auto sm:end-6 sm:top-4 sm:max-w-sm">
					<div className="mb-1 flex items-center gap-1.5 font-medium">
						<svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
						{t('missing_permissions', 'Missing Permissions')}
					</div>
					<p className="text-xs">{t('permissions_required_desc', 'Some permissions are required to activate this automation.')}</p>
				</div>
			)}

			{/* Add Delay button (floating, when trigger configured but no delay) */}
			{triggerConfigured && !hasDelay && (
				<div className="absolute start-1/2 top-4 z-20 -translate-x-1/2">
					<button
						className="flex items-center gap-1.5 rounded-lg border border-dashed border-amber-400/40 bg-amber-500/5 px-3 py-2 text-xs font-medium text-amber-600 transition-all hover:border-amber-400 hover:bg-amber-500/10"
						onClick={addDelayNode}
					>
						<svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>
                        + {t('smart_delay', 'Smart Delay')}
					</button>
				</div>
			)}

			<ReactFlow
				nodes={nodes}
				edges={edges}
				onNodesChange={onNodesChange}
				onEdgesChange={onEdgesChange}
				onConnect={onConnect}
				nodeTypes={nodeTypes}
				edgeTypes={edgeTypes}
				fitView
				fitViewOptions={{ padding: 0.3 }}
				proOptions={{ hideAttribution: true }}
				minZoom={0.3}
				maxZoom={1.5}
				defaultEdgeOptions={{ type: 'custom' }}
			>
				<Background variant="dots" gap={24} size={1} color="hsl(var(--foreground) / 0.1)" />
				<Controls
					showInteractive={false}
					className="!rounded-lg !border !border-border !bg-background !shadow-sm [&>button:hover]:!bg-foreground/5 [&>button]:!border-border [&>button]:!bg-background [&>button]:!text-foreground/60"
				/>
			</ReactFlow>
		</div>
	);
}
