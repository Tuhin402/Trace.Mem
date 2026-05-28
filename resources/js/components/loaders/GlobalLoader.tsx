import { useEffect, useState } from 'react';
import AppLogo from '@/components/app-logo';
import loaderVideo from '@/assets/videos/loader_animation.mp4';

export default function GlobalLoader() {
    // Check if it's the first visit. During SSR, window is undefined, so it defaults to true.
    const isInitialVisit = typeof window === 'undefined' || !sessionStorage.getItem('tracemem_loader_shown');
    
    const [isMounted, setIsMounted] = useState(isInitialVisit);
    const [isVisible, setIsVisible] = useState(isInitialVisit);
    const [progress, setProgress] = useState(0);
    const [mousePos, setMousePos] = useState({ x: 50, y: 50 });

    useEffect(() => {
        if (!isInitialVisit) return;

        const handleMouseMove = (e: MouseEvent) => {
            const x = (e.clientX / window.innerWidth) * 100;
            const y = (e.clientY / window.innerHeight) * 100;
            setMousePos({ x, y });
        };
        window.addEventListener('mousemove', handleMouseMove);

        const simInterval = setInterval(() => {
            setProgress(p => {
                if (p < 60) return p + (Math.random() * 15);
                if (p < 90) return p + (Math.random() * 5);
                return p; // Stick at ~90-95% until actually loaded
            });
        }, 150);

        const finishLoading = () => {
            clearInterval(simInterval);
            setProgress(100);
            setTimeout(() => {
                setIsVisible(false);
                sessionStorage.setItem('tracemem_loader_shown', 'true');
                setTimeout(() => setIsMounted(false), 500); // Wait 500ms for CSS fade out transition
            }, 500); // Hold at 100% for 500ms before fading
        };

        let isPageLoaded = document.readyState === 'complete';
        let isMinTimePassed = false;

        const attemptFinish = () => {
            if (isPageLoaded && isMinTimePassed) {
                finishLoading();
            }
        };

        // 1. Wait for minimum display time (3.5 seconds)
        setTimeout(() => {
            isMinTimePassed = true;
            attemptFinish();
        }, 3500);

        // 2. Wait for full page load (assets, fonts, etc.)
        const onPageLoad = () => {
            isPageLoaded = true;
            attemptFinish();
        };

        if (!isPageLoaded) {
            window.addEventListener('load', onPageLoad);
        }

        return () => {
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('load', onPageLoad);
            clearInterval(simInterval);
        };
    }, [isInitialVisit]);

    if (!isMounted) return null;

    return (
        <div className={`global-loader ${isVisible ? 'visible' : 'hidden'} mode-full`}>
            <video
                className="loader-video"
                src={loaderVideo}
                autoPlay
                muted
                loop
                playsInline
            />
            <div className="loader-dark-overlay" />
            <div
                className="loader-mesh"
                style={{
                    '--mx': `${mousePos.x}%`,
                    '--my': `${mousePos.y}%`,
                } as React.CSSProperties}
            />

            <div className="loader-content">
                <div className="loader-logo-area">
                    <AppLogo />
                </div>

                <div className="loader-bottom">
                    <div className="loader-status">
                        <span className="loader-text pulse-text">Loading TraceMem...</span>
                        <span className="loader-percentage">{Math.min(Math.round(progress), 100)}%</span>
                    </div>
                    <div className="loader-bar-container">
                        <div className="loader-bar-fill" style={{ width: `${Math.min(progress, 100)}%` }} />
                    </div>
                </div>
            </div>
        </div>
    );
}
