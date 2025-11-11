declare module '@inertiajs/progress' {
    interface ProgressOptions {
        delay?: number;
        includeCSS?: boolean;
        showSpinner?: boolean;
        color?: string;
        minimum?: number;
    }

    interface ProgressInstance {
        init(options?: ProgressOptions): void;
    }

    const progress: ProgressInstance;

    export default progress;
}

