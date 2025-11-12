declare module 'bootstrap/js/dist/dropdown' {
    export default class Dropdown {
        constructor(element: Element, options?: Record<string, unknown>);
        dispose(): void;
        show(): void;
        hide(): void;
    }
}

