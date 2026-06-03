import React from 'react';

type Props = {
    children: React.ReactNode;
};

type State = {
    hasError: boolean;
};

export default class ErrorBoundary extends React.Component<Props, State> {
    private recoveryTimer: ReturnType<typeof setTimeout> | null = null;

    constructor(props: Props) {
        super(props);

        this.state = {
            hasError: false,
        };
    }

    static getDerivedStateFromError(): State {
        return {
            hasError: true,
        };
    }

    componentDidCatch(error: Error, info: React.ErrorInfo) {
        // Auto-recover from transient Inertia page-swap reconciliation errors.
        // These happen when React's commit phase collides with a skeleton
        // teardown — the DOM is left in a valid state and a re-render fixes it.
        const isTransient =
            error.name === 'NotFoundError' ||
            error.message?.includes('removeChild') ||
            error.message?.includes('insertBefore') ||
            error.message?.includes('appendChild');

        if (isTransient) {
            console.warn('[ErrorBoundary] Recovered from transient navigation error:', error.message);
            this.recoveryTimer = setTimeout(() => this.setState({ hasError: false }), 0);
            return;
        }

        console.error('React Error Boundary:', error, info);
    }

    componentWillUnmount() {
        if (this.recoveryTimer) {
            clearTimeout(this.recoveryTimer);
        }
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-screen items-center justify-center">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold">
                            Something went wrong.
                        </h1>

                        <p className="mt-2 text-muted-foreground">
                            Please refresh the page.
                        </p>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}