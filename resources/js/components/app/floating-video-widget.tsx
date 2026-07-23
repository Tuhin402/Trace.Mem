import { useState, useEffect } from 'react';
import { X } from 'lucide-react';

// Extract YouTube ID from various URL formats or return the ID if already clean
function extractYouTubeId(urlOrId: string): string | null {
    if (!urlOrId) return null;
    
    // If it's just an 11 character string without spaces/slashes, it's likely an ID
    if (/^[a-zA-Z0-9_-]{11}$/.test(urlOrId.trim())) {
        return urlOrId.trim();
    }

    try {
        const url = new URL(urlOrId.trim());
        if (url.hostname.includes('youtube.com')) {
            if (url.pathname.startsWith('/shorts/')) {
                return url.pathname.split('/')[2];
            }
            if (url.pathname.startsWith('/embed/')) {
                return url.pathname.split('/')[2];
            }
            return url.searchParams.get('v');
        } else if (url.hostname === 'youtu.be') {
            return url.pathname.slice(1);
        }
    } catch (e) {
        // Not a valid URL, fallback
    }

    return null;
}

export function FloatingVideoWidget({ rawVideoData, pagePath }: { rawVideoData: string, pagePath: string }) {
    const [isVisible, setIsVisible] = useState(false);
    
    const videoId = extractYouTubeId(rawVideoData);

    useEffect(() => {
        if (!videoId) return;
        
        // Check session storage for this specific page path
        const storageKey = `floating-video-closed-${pagePath}`;
        if (!sessionStorage.getItem(storageKey)) {
            // Slight delay before showing for smooth entrance after page load
            const timer = setTimeout(() => setIsVisible(true), 1000);
            return () => clearTimeout(timer);
        }
    }, [videoId, pagePath]);

    if (!videoId || !isVisible) return null;

    const handleClose = () => {
        setIsVisible(false);
        sessionStorage.setItem(`floating-video-closed-${pagePath}`, 'true');
    };

    return (
        <div className="tracemem-floating-video-wrapper">
            <button 
                onClick={handleClose} 
                className="tracemem-floating-video-close"
                title="Close video for this session"
            >
                <X size={14} strokeWidth={2.5} />
            </button>
            <div className="tracemem-floating-video-inner">
                <iframe
                    className="tracemem-floating-video-iframe"
                    src={`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&mute=1&controls=1&modestbranding=1&rel=0`}
                    title="TraceMem Video Guide"
                    frameBorder="0"
                    allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                    allowFullScreen
                ></iframe>
            </div>
        </div>
    );
}
