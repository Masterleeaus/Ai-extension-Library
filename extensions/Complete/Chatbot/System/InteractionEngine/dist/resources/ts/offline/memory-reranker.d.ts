export interface MemoryCandidate {
    id: string;
    content: string;
    semanticSimilarity?: number;
    timestamp?: string;
    emotionalIntensity?: number;
    factKey?: string;
    factValue?: unknown;
    [key: string]: unknown;
}
export interface ScoredMemory extends MemoryCandidate {
    semanticScore: number;
    recencyScore: number;
    emotionalScore: number;
    score: number;
}
export interface MemoryRanking {
    mostRelevant: ScoredMemory;
    contradictions: ScoredMemory[];
    alternatives: ScoredMemory[];
    weights: {
        semantic: number;
        recency: number;
        emotion: number;
    };
}
export declare class WeightedMemoryReranker {
    private readonly semanticWeight;
    private readonly recencyWeight;
    private readonly emotionWeight;
    private readonly analyzer;
    constructor(semanticWeight?: number, recencyWeight?: number, emotionWeight?: number);
    rank(candidates: MemoryCandidate[], now?: Date, limit?: number): MemoryRanking;
}
