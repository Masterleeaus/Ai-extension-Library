export interface PersonaMetrics {
    sentimentPolarity: number;
    formality: number;
    emotionalIntensity: number;
    label: string;
}
export interface PersonaDriftObservation {
    observedAt: string;
    metrics: PersonaMetrics;
    baseline: {
        sentimentPolarity: number;
        formality: number;
    } | null;
    deltas: {
        sentiment: number;
        formality: number;
    };
    driftScore: number;
    driftEvent: boolean;
    triggers: string[];
    model: 'adaptive-persona-v2';
}
export declare class PersonaSignalAnalyzer {
    analyze(text: string): PersonaMetrics;
}
export declare class BehavioralDriftTracker {
    private readonly windowSize;
    private readonly driftThreshold;
    private readonly observations;
    private readonly analyzer;
    constructor(windowSize?: number, driftThreshold?: number);
    observe(text: string, observedAt?: string): PersonaDriftObservation;
    history(limit?: number): PersonaDriftObservation[];
}
