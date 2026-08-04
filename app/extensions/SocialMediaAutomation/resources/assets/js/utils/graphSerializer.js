import { DEFAULT_POSITIONS, NODE_TYPES } from './constants';

/**
 * Convert flat automation data (from server) into React Flow nodes and edges.
 */
export function payloadToGraph(automation) {
    if (automation?.workflow_graph?.nodes?.length) {
        return {
            nodes: automation.workflow_graph.nodes,
            edges: automation.workflow_graph.edges || [],
        };
    }

    // Generate default linear layout from flat data
    const nodes = [];
    const edges = [];
    const hasDelay = (automation?.delay_seconds ?? 0) > 0;

    // Trigger node
    nodes.push({
        id: 'trigger-1',
        type: NODE_TYPES.TRIGGER,
        position: { ...DEFAULT_POSITIONS.trigger },
        data: {
            configured: !!automation,
            triggerTarget: automation?.trigger_target ?? 'all_posts',
            triggerPostId: automation?.trigger_post_id ?? null,
            triggerPostData: automation?.trigger_post_data ?? null,
            keywordMode: automation?.keyword_mode ?? 'any',
            includeKeywords: automation?.include_keywords ?? [],
            excludeKeywords: automation?.exclude_keywords ?? [],
            enablePublicReplies: automation?.enable_public_replies ?? false,
            replyVariations: automation?.replies?.map(r => r.content) ?? [],
        },
    });

    // Delay node (optional)
    if (hasDelay) {
        nodes.push({
            id: 'delay-1',
            type: NODE_TYPES.DELAY,
            position: { ...DEFAULT_POSITIONS.delay },
            data: {
                seconds: automation.delay_seconds,
            },
        });
        edges.push({
            id: 'e-trigger-delay',
            source: 'trigger-1',
            target: 'delay-1',
            type: 'custom',
        });
    }

    // Action node
    const actionPos = hasDelay ? DEFAULT_POSITIONS.action : DEFAULT_POSITIONS.actionNoDelay;
    nodes.push({
        id: 'action-1',
        type: NODE_TYPES.ACTION,
        position: { ...actionPos },
        data: {
            actions: automation?.actions?.map(a => ({ type: a.type, content: a.content })) ?? [],
        },
    });

    edges.push({
        id: hasDelay ? 'e-delay-action' : 'e-trigger-action',
        source: hasDelay ? 'delay-1' : 'trigger-1',
        target: 'action-1',
        type: 'custom',
    });

    return { nodes, edges };
}

/**
 * Convert React Flow graph state into the flat API payload.
 */
export function graphToPayload(nodes, edges, storeState) {
    const triggerNode = nodes.find(n => n.type === NODE_TYPES.TRIGGER);
    const delayNode = nodes.find(n => n.type === NODE_TYPES.DELAY);
    const actionNode = nodes.find(n => n.type === NODE_TYPES.ACTION);
    const triggerData = triggerNode?.data ?? {};
    const actionData = actionNode?.data ?? {};

    return {
        name: storeState.automationName,
        social_media_platform_id: storeState.selectedAccountId,
        trigger_target: triggerData.triggerTarget ?? 'all_posts',
        trigger_post_id: triggerData.triggerPostId ?? null,
        trigger_post_data: triggerData.triggerPostData ?? null,
        keyword_mode: triggerData.keywordMode ?? 'any',
        include_keywords: triggerData.includeKeywords ?? [],
        exclude_keywords: triggerData.excludeKeywords ?? [],
        enable_public_replies: triggerData.enablePublicReplies ?? false,
        delay_seconds: delayNode?.data?.seconds ?? 0,
        actions: (actionData.actions?.length > 0)
            ? actionData.actions
            : [{ type: 'text', content: { text: '' } }],
        replies: (triggerData.replyVariations ?? []).map(v => ({ content: v })),
        workflow_graph: { nodes, edges },
    };
}
