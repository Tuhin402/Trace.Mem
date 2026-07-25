import { useEffect, useRef, useState } from 'react';
import { Loader2 } from 'lucide-react';

export default function WorldMap() {
    const mapRef = useRef<HTMLDivElement>(null);
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {
        // In a real implementation, this would load a GeoJSON dataset (e.g., countries.geo.json)
        // and render it using a library like d3-geo or mapbox-gl.
        const timer = setTimeout(() => {
            setIsLoaded(true);
        }, 800);

        return () => clearTimeout(timer);
    }, []);

    return (
        <div 
            ref={mapRef} 
            className="w-full h-full min-h-[300px] bg-background border border-almost-black relative overflow-hidden flex flex-col"
        >
            <div className="absolute top-0 left-0 right-0 bg-primary/10 border-b border-primary/20 p-2 flex items-center justify-between z-10">
                <span className="text-xs font-mono font-bold text-primary">GEOJSON MAP RENDERER</span>
                <span className="text-xs font-mono text-on-background/50 flex items-center gap-2">
                    {!isLoaded && <Loader2 className="h-3 w-3 animate-spin" />}
                    {isLoaded ? 'DATA LOADED' : 'FETCHING...'}
                </span>
            </div>
            
            <div className="flex-1 flex items-center justify-center relative p-8">
                {/* SVG Grid Overlay to look architectural */}
                <svg className="absolute inset-0 w-full h-full opacity-[0.03]" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" strokeWidth="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>

                {isLoaded ? (
                    <div className="relative w-full max-w-2xl aspect-[2/1] border border-dashed border-primary/30 flex items-center justify-center">
                        <span className="font-mono text-sm text-primary/70 absolute">Map projection goes here</span>
                        
                        {/* Fake data points */}
                        <div className="absolute w-2 h-2 bg-error rounded-full top-[30%] left-[20%] animate-pulse cursor-pointer group">
                            <div className="absolute hidden group-hover:block w-32 bg-surface border border-almost-black text-xs font-mono p-2 -top-12 -left-16 z-20 shadow-xl">
                                <strong>US-EAST</strong><br/>Active Sessions: 45
                            </div>
                        </div>
                        <div className="absolute w-2 h-2 bg-error rounded-full top-[45%] left-[55%] animate-pulse cursor-pointer group">
                            <div className="absolute hidden group-hover:block w-32 bg-surface border border-almost-black text-xs font-mono p-2 -top-12 -left-16 z-20 shadow-xl">
                                <strong>EU-CENTRAL</strong><br/>Active Sessions: 128
                            </div>
                        </div>
                        <div className="absolute w-2 h-2 bg-error rounded-full top-[60%] left-[70%] animate-pulse cursor-pointer group">
                            <div className="absolute hidden group-hover:block w-32 bg-surface border border-almost-black text-xs font-mono p-2 -top-12 -left-16 z-20 shadow-xl">
                                <strong>AP-SOUTH</strong><br/>Active Sessions: 92
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="font-mono text-sm text-on-background/50">Initializing map vectors...</div>
                )}
            </div>
        </div>
    );
}
