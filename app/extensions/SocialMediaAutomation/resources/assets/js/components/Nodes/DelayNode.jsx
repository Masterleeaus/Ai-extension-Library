import { memo } from 'react';
import { Handle, Position } from '@xyflow/react';
import useAutomationStore from '../../store/useAutomationStore';
import { t } from '../../utils/i18n';

const DelayNode = memo(function DelayNode({ id, data }) {
	const updateNodeData = useAutomationStore(s => s.updateNodeData);

	return (
		<div className="w-56 rounded-[10px] bg-background sm:w-64">
			<Handle type="target" position={Position.Left} className="!size-2.5 !bg-foreground/30" />

			{/* Node Header */}
			<div className="flex h-[52px] items-center gap-2 border-b p-4 text-2xs font-medium leading-tight text-heading-foreground">
				<svg className="text-primary" width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg"  stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
					<path d="M12.7115 7.42901C12.8524 6.19188 12.6049 4.94156 12.0034 3.85138C11.4019 2.7612 10.4761 1.88516 9.35436 1.3447C8.23265 0.804242 6.97058 0.626145 5.74312 0.835095C4.51565 1.04405 3.38359 1.62969 2.50388 2.51085C1.62417 3.39201 1.04038 4.52502 0.83345 5.75283C0.626515 6.98064 0.806685 8.24241 1.34898 9.36323C1.89128 10.4841 2.76884 11.4084 3.86001 12.0081C4.95118 12.6079 6.2019 12.8533 7.4388 12.7103M6.75013 3.41701V6.75034L8.08346 8.08367M10.0835 10.0837V13.417M12.7501 10.0837V13.417"/>
				</svg>

				{t('smart_delay', 'Smart Delay')}
			</div>

			{/* Node Body */}
			<div className="p-4">
				<p className="m-0 text-2xs">
					{t('wait_for', 'Wait for')}{' '}
					<input
						className="mx-1 inline-block w-12 rounded border border-input-border bg-input-background px-1.5 py-0.5 text-center text-xs text-heading-foreground"
						type="number"
						min="0"
						max="300"
						value={data.seconds ?? 10}
						onChange={e => updateNodeData(id, { seconds: parseInt(e.target.value, 10) || 0 })}
					/>{' '}
					{t('seconds_and_continue', 'seconds and continue')}
				</p>
			</div>

			<Handle type="source" position={Position.Right} className="!size-2.5 !bg-foreground/30" />
		</div>
	);
});

export default DelayNode;
