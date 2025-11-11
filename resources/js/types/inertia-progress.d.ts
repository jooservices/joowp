declare module '@inertiajs/progress' {
    interface ProgressOptions {
        delay?: number;
        includeCSS?: boolean;
        showSpinner?: boolean;
        color?: string;
        minimum?: number;
    }

    export function setupProgress(options?: ProgressOptions): void;
}

