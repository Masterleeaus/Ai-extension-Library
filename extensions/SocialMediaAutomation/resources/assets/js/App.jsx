import React, { useEffect } from 'react';
import { ReactFlowProvider } from '@xyflow/react';
import useAutomationStore from './store/useAutomationStore';
import BuilderHeader from './components/Header/BuilderHeader';
import WorkflowCanvas from './components/Canvas/WorkflowCanvas';
import SidePanel from './components/Panel/SidePanel';

export default function App() {
	const initFromServerData = useAutomationStore(s => s.initFromServerData);

	useEffect(() => {
		const data = window.__automationData ?? {};
		initFromServerData(data);
	}, [ initFromServerData ]);

	return (
		<ReactFlowProvider>
			<div className="flex h-screen flex-col">
				<BuilderHeader />
				<WorkflowCanvas />
				<SidePanel />
			</div>
		</ReactFlowProvider>
	);
}
