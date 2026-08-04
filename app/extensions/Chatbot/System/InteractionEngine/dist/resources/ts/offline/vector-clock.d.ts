export type VectorClock = Record<string, number>;
export type ClockRelation = 'before' | 'after' | 'equal' | 'concurrent';
export declare function tickVectorClock(clock: VectorClock, node: string): VectorClock;
export declare function mergeVectorClocks(left: VectorClock, right: VectorClock): VectorClock;
export declare function compareVectorClocks(left: VectorClock, right: VectorClock): ClockRelation;
export interface CausalState<T extends Record<string, unknown>> {
    clock: VectorClock;
    updatedAt: string;
    payload: T;
}
export interface CausalConflict {
    field: string;
    local: unknown;
    remote: unknown;
    strategy: 'manual_review';
}
export declare function resolveCausalState<T extends Record<string, unknown>>(local: CausalState<T>, remote: CausalState<T>, lastWriterWinsFields?: string[]): {
    relation: ClockRelation;
    state: CausalState<T>;
    conflicts: CausalConflict[];
};
