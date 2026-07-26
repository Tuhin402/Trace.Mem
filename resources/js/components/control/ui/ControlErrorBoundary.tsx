import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertTriangle } from 'lucide-react';

interface Props {
    children?: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ControlErrorBoundary extends Component<Props, State> {
    public state: State = {
        hasError: false,
        error: null
    };

    public static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('[ControlErrorBoundary] Uncaught error:', error, errorInfo);
        // In the future, this would send to Sentry or another tracking service
    }

    public render() {
        if (this.state.hasError) {
            return (
                <div className="flex flex-col items-center justify-center py-24 text-center px-4 border border-destructive/20 rounded-lg bg-destructive/5">
                    <div className="mb-6 p-4 rounded-full bg-destructive/10 text-destructive">
                        <AlertTriangle className="h-10 w-10" />
                    </div>
                    <h3 className="text-xl font-bold font-heading text-on-background mb-2">Something went wrong</h3>
                    <p className="text-on-background/60 max-w-md font-mono text-sm mb-6">
                        An unexpected error occurred while rendering this module. Our team has been notified.
                    </p>
                    <button
                        type="button"
                        onClick={() => this.setState({ hasError: false, error: null })}
                        className="px-4 py-2 bg-background border border-almost-black/10 hover:bg-almost-black/5 text-sm font-medium rounded transition-colors"
                    >
                        Try Again
                    </button>
                    {process.env.NODE_ENV === 'development' && this.state.error && (
                        <pre className="mt-8 p-4 bg-background border border-destructive/20 text-destructive text-left text-xs font-mono overflow-auto max-w-full rounded">
                            {this.state.error.toString()}
                        </pre>
                    )}
                </div>
            );
        }

        return this.props.children;
    }
}
