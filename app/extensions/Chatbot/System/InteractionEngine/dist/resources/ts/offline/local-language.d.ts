import type { LanguageUnderstanding } from './types';
export declare class LocalLanguageEngine {
    private readonly classifier;
    understand(input: string): LanguageUnderstanding;
}
