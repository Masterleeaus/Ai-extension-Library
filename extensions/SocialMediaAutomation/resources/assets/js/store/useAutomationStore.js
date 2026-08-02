import { create } from 'zustand';
import { applyNodeChanges, applyEdgeChanges, addEdge } from '@xyflow/react';
import { payloadToGraph, graphToPayload } from '../utils/graphSerializer';
import { saveAutomation, toggleStatus } from '../hooks/useAutomationApi';
import { NODE_TYPES, DEFAULT_POSITIONS } from '../utils/constants';

let nodeIdCounter = 10;

const useAutomationStore = create((set, get) => ({
	// Identity
	automationId: null,
	automationName: '',
	status: 'draft',

	// Account
	selectedAccountId: null,
	accounts: [],
	permissionInfo: {},

	// React Flow graph
	nodes: [],
	edges: [],

	// Panel
	panelOpen: false,
	panelMode: null,
	selectedNodeId: null,

	// Saving
	saving: false,

	// Post selector modal
	postSelectorOpen: false,

	// Computed-like helpers
	getSelectedPlatformName() {
		const { accounts, selectedAccountId } = get();
		const account = accounts.find(a => a.id == selectedAccountId);
		return account?.platform ?? '';
	},

	getSelectedAccountLabel() {
		const { accounts, selectedAccountId } = get();
		const account = accounts.find(a => a.id == selectedAccountId);
		return account ? (account.credentials?.name || account.credentials?.username || 'Account') : '';
	},

	getPermissionInfo() {
		const { permissionInfo, selectedAccountId } = get();
		return permissionInfo[selectedAccountId] ?? null;
	},

	isDmSupported() {
		const info = get().getPermissionInfo();
		if (info) return info.dm_supported;
		return ![ 'tiktok', 'linkedin' ].includes(get().getSelectedPlatformName());
	},

	hasPermissions() {
		const info = get().getPermissionInfo();
		return info ? info.has_permissions : true;
	},

	canSetLive() {
		const { nodes } = get();
		const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);
		const actionNode = nodes.find(n => n.type === NODE_TYPES.ACTION);
		const triggerConfigured = triggerNode?.data?.configured ?? false;
		const hasActions = (actionNode?.data?.actions?.length ?? 0) > 0;
		return triggerConfigured && hasActions && get().hasPermissions();
	},

	// Initialize from server data
	initFromServerData(data) {
		const automation = data.automation;
		const { nodes, edges } = payloadToGraph(automation);

		set({
			automationId: automation?.id ?? null,
			automationName: automation?.name ?? '',
			status: automation?.status ?? 'draft',
			selectedAccountId: automation?.social_media_platform_id ?? data.selectedAccountId ?? null,
			accounts: data.accounts ?? [],
			permissionInfo: data.permissionInfo ?? {},
			nodes,
			edges,
		});
	},

	// React Flow callbacks
	onNodesChange(changes) {
		set({ nodes: applyNodeChanges(changes, get().nodes) });
	},

	onEdgesChange(changes) {
		set({ edges: applyEdgeChanges(changes, get().edges) });
	},

	onConnect(connection) {
		// No self-connections
		if (connection.source === connection.target) return;

		const { nodes, edges } = get();
		const sourceNode = nodes.find(n => n.id === connection.source);
		const targetNode = nodes.find(n => n.id === connection.target);
		if (!sourceNode || !targetNode) return;

		// No direct trigger→action when a delay node exists
		const hasDelay = nodes.some(n => n.type === NODE_TYPES.DELAY);
		if (hasDelay && sourceNode.type === NODE_TYPES.TRIGGER && targetNode.type === NODE_TYPES.ACTION) return;
		if (hasDelay && sourceNode.type === NODE_TYPES.ACTION && targetNode.type === NODE_TYPES.TRIGGER) return;

		// No duplicate edges between same pair
		const exists = edges.some(e => e.source === connection.source && e.target === connection.target);
		if (exists) return;

		set({ edges: addEdge({ ...connection, type: 'custom' }, edges) });
	},

	// Node operations
	updateNodeData(nodeId, data) {
		set({
			nodes: get().nodes.map(n =>
				n.id === nodeId ? { ...n, data: { ...n.data, ...data } } : n
			),
		});
	},

	addDelayNode() {
		const { nodes, edges } = get();
		const existing = nodes.find(n => n.type === NODE_TYPES.DELAY);
		if (existing) return;

		const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);
		const actionNode = nodes.find(n => n.type === NODE_TYPES.ACTION);

		const triggerPos = triggerNode?.position ?? { x: 100, y: 200 };
		const actionPos = actionNode?.position ?? { x: 550, y: 200 };
		const midX = (triggerPos.x + actionPos.x) / 2;
		const midY = (triggerPos.y + actionPos.y) / 2;
		const spacing = 200;

		const delayNode = {
			id: `delay-${++nodeIdCounter}`,
			type: NODE_TYPES.DELAY,
			position: { x: midX, y: midY },
			data: { seconds: 10 },
		};

		// Push action node to the right to make room
		const newNodes = nodes.map(n =>
			n.id === actionNode?.id
				? { ...n, position: { ...n.position, x: Math.max(n.position.x, midX + spacing) } }
				: n
		);

		// Remove direct trigger→action edge
		let newEdges = edges.filter(
			e => !(e.source === triggerNode?.id && e.target === actionNode?.id)
		);

		// Add trigger→delay and delay→action edges
		if (triggerNode) {
			newEdges.push({
				id: `e-${triggerNode.id}-${delayNode.id}`,
				source: triggerNode.id,
				target: delayNode.id,
				type: 'custom',
			});
		}
		if (actionNode) {
			newEdges.push({
				id: `e-${delayNode.id}-${actionNode.id}`,
				source: delayNode.id,
				target: actionNode.id,
				type: 'custom',
			});
		}

		set({
			nodes: [ ...newNodes, delayNode ],
			edges: newEdges,
		});
	},

	removeDelayNode() {
		const { nodes, edges } = get();
		const delayNode = nodes.find(n => n.type === NODE_TYPES.DELAY);
		if (!delayNode) return;

		const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);
		const actionNode = nodes.find(n => n.type === NODE_TYPES.ACTION);

		// Remove delay node and its edges
		let newEdges = edges.filter(
			e => e.source !== delayNode.id && e.target !== delayNode.id
		);

		// Reconnect trigger directly to action
		if (triggerNode && actionNode) {
			newEdges.push({
				id: `e-${triggerNode.id}-${actionNode.id}`,
				source: triggerNode.id,
				target: actionNode.id,
				type: 'custom',
			});
		}

		set({
			nodes: nodes.filter(n => n.id !== delayNode.id),
			edges: newEdges,
		});
	},

	// Panel operations
	openPanel(mode, nodeId = null) {
		set({ panelOpen: true, panelMode: mode, selectedNodeId: nodeId });
	},

	closePanel() {
		set({ panelOpen: false, panelMode: null, selectedNodeId: null });
	},

	// Post selector modal
	setPostSelectorOpen(open) {
		set({ postSelectorOpen: open });
	},

	// Save automation
	async saveAutomationAction(silent = false) {
		const state = get();
		let name = state.automationName;

		if (!name.trim()) {
			const platform = state.getSelectedPlatformName();
			const pName = platform ? platform.charAt(0).toUpperCase() + platform.slice(1) : 'Social';
			const date = new Date();
			const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
			name = `${pName} Automation ${dateStr}`;
			set({ automationName: name });
		}

		set({ saving: true });

		try {
			const payload = graphToPayload(state.nodes, state.edges, { ...state, automationName: name });
			const data = await saveAutomation(state.automationId, payload);

			if (data.status === 'success') {
				if (!silent && window.toastr) window.toastr.success(data.message);
				if (!state.automationId && data.data?.id) {
					set({ automationId: data.data.id });
					history.replaceState(null, '', `/dashboard/user/social-media/automation/${data.data.id}/edit`);
				}
				return true;
			}

			if (window.toastr) window.toastr.error(data.message || 'An error occurred.');
			return false;
		} catch {
			if (window.toastr) window.toastr.error('An error occurred.');
			return false;
		} finally {
			set({ saving: false });
		}
	},

	async setLiveAction() {
		const state = get();
		const saved = await get().saveAutomationAction(true);
		if (!saved || !get().automationId) return;

		try {
			const data = await toggleStatus(get().automationId);
			if (data.status === 'success') {
				set({ status: data.data.status });
				if (data.data.status === 'live') {
					if (window.toastr) window.toastr.success('Automation is now live!');
				} else {
					if (window.toastr) window.toastr.warning('Automation is now paused.');
				}
			} else {
				if (window.toastr) window.toastr.error(data.message || 'An error occurred.');
			}
		} catch {
			if (window.toastr) window.toastr.error('An error occurred.');
		}
	},
}));

export default useAutomationStore;
