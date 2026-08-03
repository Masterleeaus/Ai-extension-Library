export interface IntentExample {
    text: string;
    intent: string;
}
export interface IntentPrediction {
    intent: string;
    confidence: number;
    alternatives: Array<{
        intent: string;
        confidence: number;
    }>;
    model: 'multinomial-naive-bayes-v1';
}
export declare class NaiveBayesIntentClassifier {
    private readonly alpha;
    private readonly unknownThreshold;
    private readonly documentCounts;
    private readonly tokenCounts;
    private readonly totalTokens;
    private readonly vocabulary;
    private documents;
    constructor(examples: IntentExample[], alpha?: number, unknownThreshold?: number);
    static createDefault(): NaiveBayesIntentClassifier;
    predict(text: string): IntentPrediction;
    estimatedModelBytes(): number;
    private train;
}
