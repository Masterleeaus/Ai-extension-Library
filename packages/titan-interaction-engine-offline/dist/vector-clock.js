"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.tickVectorClock = tickVectorClock;
exports.mergeVectorClocks = mergeVectorClocks;
exports.compareVectorClocks = compareVectorClocks;
exports.resolveCausalState = resolveCausalState;
function tickVectorClock(clock, node) {
    if (!node.trim())
        throw new Error('Vector clock node ID is required.');
    return normalizeClock({ ...clock, [node]: (clock[node] ?? 0) + 1 });
}
function mergeVectorClocks(left, right) {
    const merged = { ...left };
    for (const [node, counter] of Object.entries(right))
        merged[node] = Math.max(merged[node] ?? 0, counter);
    return normalizeClock(merged);
}
function compareVectorClocks(left, right) {
    const nodes = new Set([...Object.keys(left), ...Object.keys(right)]);
    let less = false;
    let greater = false;
    for (const node of nodes) {
        const leftCounter = left[node] ?? 0;
        const rightCounter = right[node] ?? 0;
        if (leftCounter < rightCounter)
            less = true;
        if (leftCounter > rightCounter)
            greater = true;
    }
    if (!less && !greater)
        return 'equal';
    if (less && !greater)
        return 'before';
    if (!less && greater)
        return 'after';
    return 'concurrent';
}
function resolveCausalState(local, remote, lastWriterWinsFields = []) {
    const relation = compareVectorClocks(local.clock, remote.clock);
    if (relation === 'before')
        return { relation, state: remote, conflicts: [] };
    if (relation === 'after')
        return { relation, state: local, conflicts: [] };
    const remoteIsLater = Date.parse(remote.updatedAt) > Date.parse(local.updatedAt);
    const payload = {};
    const conflicts = [];
    const fields = new Set([...Object.keys(local.payload), ...Object.keys(remote.payload)]);
    for (const field of fields) {
        const hasLocal = Object.prototype.hasOwnProperty.call(local.payload, field);
        const hasRemote = Object.prototype.hasOwnProperty.call(remote.payload, field);
        const localValue = local.payload[field];
        const remoteValue = remote.payload[field];
        if (!hasLocal)
            payload[field] = remoteValue;
        else if (!hasRemote || Object.is(localValue, remoteValue))
            payload[field] = localValue;
        else if (lastWriterWinsFields.includes(field))
            payload[field] = remoteIsLater ? remoteValue : localValue;
        else {
            payload[field] = localValue;
            conflicts.push({ field, local: localValue, remote: remoteValue, strategy: 'manual_review' });
        }
    }
    return {
        relation,
        state: { clock: mergeVectorClocks(local.clock, remote.clock), updatedAt: remoteIsLater ? remote.updatedAt : local.updatedAt, payload: payload },
        conflicts,
    };
}
function normalizeClock(clock) {
    const normalized = {};
    for (const node of Object.keys(clock).sort()) {
        const counter = clock[node] ?? 0;
        if (!Number.isInteger(counter) || counter < 0)
            throw new Error('Vector clocks require non-negative integer counters.');
        normalized[node] = counter;
    }
    return normalized;
}
