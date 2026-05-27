import React from 'react';

type Props = {
    children: React.ReactNode;
};

type State = {
    hasError: boolean;
};

export default class ErrorBoundary extends React.Component<Props, State> {
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
        console.error('React Error Boundary:', error, info);
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