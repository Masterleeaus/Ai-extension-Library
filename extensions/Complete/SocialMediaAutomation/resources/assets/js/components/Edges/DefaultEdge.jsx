import { BaseEdge, EdgeLabelRenderer, getBezierPath, useReactFlow } from '@xyflow/react';
import useAutomationStore from '../../store/useAutomationStore';
import { NODE_TYPES } from '../../utils/constants';

export default function DefaultEdge({
	id,
	source,
	target,
	sourceX,
	sourceY,
	targetX,
	targetY,
	sourcePosition,
	targetPosition,
	style = {},
	markerEnd,
}) {
	const { setEdges } = useReactFlow();
	const nodes = useAutomationStore(s => s.nodes);
	const removeDelayNode = useAutomationStore(s => s.removeDelayNode);

	const [ edgePath, labelX, labelY ] = getBezierPath({
		sourceX,
		sourceY,
		sourcePosition,
		targetX,
		targetY,
		targetPosition,
	});

	const sourceNode = nodes.find(n => n.id === source);
	const targetNode = nodes.find(n => n.id === target);
	const involvesDelay = sourceNode?.type === NODE_TYPES.DELAY || targetNode?.type === NODE_TYPES.DELAY;
	const isDirectTriggerAction = sourceNode?.type === NODE_TYPES.TRIGGER && targetNode?.type === NODE_TYPES.ACTION;

	function handleDelete() {
		if (involvesDelay) {
			removeDelayNode();
		} else {
			setEdges(edges => edges.filter(e => e.id !== id));
		}
	}

	return (
		<>
			<BaseEdge
				path={edgePath}
				markerEnd={markerEnd}
				style={{
					stroke: 'hsl(var(--foreground) / 0.3)',
					strokeWidth: 1.5,
					...style,
				}}
			/>
			{!isDirectTriggerAction && <EdgeLabelRenderer>
				<div
					className="nodrag nopan"
					style={{
						position: 'absolute',
						transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
						pointerEvents: 'all',
					}}
				>
					<button
						className="grid size-8 place-items-center rounded-full bg-surface-background text-red-700 transition hover:bg-red-500 hover:text-white"
						onClick={handleDelete}
					>
						<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className="size-4"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
					</button>
				</div>
			</EdgeLabelRenderer>}
		</>
	);
}
