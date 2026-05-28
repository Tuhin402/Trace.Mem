import React from 'react';

export function SkeletonBase({ className, style }: { className?: string; style?: React.CSSProperties }) {
    return <div className={`skeleton-base ${className || ''}`} style={style} />;
}

export function SkeletonText({ className, variant = 'medium' }: { className?: string; variant?: 'short' | 'medium' | 'long' }) {
    return <div className={`skeleton-base skeleton-text ${variant} ${className || ''}`} />;
}

export function SkeletonTitle({ className }: { className?: string }) {
    return <div className={`skeleton-base skeleton-title ${className || ''}`} />;
}

export function SkeletonAvatar({ className }: { className?: string }) {
    return <div className={`skeleton-base skeleton-avatar ${className || ''}`} />;
}

export function SkeletonButton({ className }: { className?: string }) {
    return <div className={`skeleton-base skeleton-button ${className || ''}`} />;
}
