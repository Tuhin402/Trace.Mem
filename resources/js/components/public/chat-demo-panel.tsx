import { useState, useEffect } from 'react';
import { User, Brain, Code2, Calendar, Zap, CheckCircle2, Clock } from 'lucide-react';

export default function ChatDemoPanel() {
    const [step, setStep] = useState(0);

    // Advanced auto-play sequence like a GIF
    useEffect(() => {
        let currentStep = 0;

        const advance = () => {
            currentStep++;
            setStep(currentStep);

            let nextDelay = 1500;
            if (currentStep === 1) nextDelay = 800; // User typing
            else if (currentStep === 2) nextDelay = 2000; // User msg visible
            else if (currentStep === 3) nextDelay = 1200; // System typing
            else if (currentStep === 4) nextDelay = 2500; // Scanning and extracting
            else if (currentStep === 5) nextDelay = 2000; // Atoms visible
            else if (currentStep === 6) nextDelay = 1000; // User 2 typing
            else if (currentStep === 7) nextDelay = 1500; // User 2 visible
            else if (currentStep === 8) nextDelay = 1000; // System 2 typing/processing
            else if (currentStep === 9) nextDelay = 4000; // Final state visible
            else if (currentStep >= 10) {
                // Reset loop
                setStep(0);
                currentStep = 0;
                nextDelay = 1000;
            }

            timer = setTimeout(advance, nextDelay);
        };

        let timer = setTimeout(advance, 1000);

        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="chat-demo-wrapper">
            {/* Top Bar */}
            <div className="chat-demo-header">
                <div className="chat-demo-dots">
                    <span className="dot red"></span>
                    <span className="dot yellow"></span>
                    <span className="dot green"></span>
                </div>
                <div className="chat-demo-title">Memory Inspector</div>
            </div>

            {/* Chat Body */}
            <div className="chat-demo-body">

                {/* User Message 1 Typing */}
                {step === 1 && (
                    <div className="typing-indicator visible">
                        <span></span><span></span><span></span>
                    </div>
                )}

                {/* User Message 1 */}
                <div className={`chat-msg user-msg ${step >= 2 ? 'visible' : ''}`} style={{ display: step >= 2 ? 'flex' : 'none' }}>
                    <div className="msg-avatar user-avatar"><User size={16} /></div>
                    <div className="msg-bubble">
                        <p>I have a client call next Thursday at 3 PM.</p>
                        <p>Also, whenever I ask for a Laravel API fix, always use the <code>App\Services\ApiResponder</code> trait.</p>
                    </div>
                </div>

                {/* System Typing 1 */}
                {step === 3 && (
                    <div className="typing-indicator visible" style={{ alignSelf: 'flex-start' }}>
                        <span></span><span></span><span></span>
                    </div>
                )}

                {/* System Message 1 - Extraction */}
                <div className={`chat-msg sys-msg ${step >= 4 ? 'visible' : ''}`} style={{ display: step >= 4 ? 'flex' : 'none' }}>
                    <div className="msg-avatar sys-avatar"><Brain size={16} /></div>
                    <div className={`msg-bubble sys-bubble ${step === 4 ? 'scanning' : ''}`}>
                        <div className="sys-status">
                            {step === 4 ? <Clock size={14} className="spin" /> : <CheckCircle2 size={14} className="text-green" />}
                            <span>{step === 4 ? 'Analyzing prompt...' : 'Memory extracted'}</span>
                        </div>

                        <div className="memory-atoms">
                            <div className={`memory-atom schedule ${step >= 5 ? 'visible' : ''}`} style={{ transitionDelay: '0.1s' }}>
                                <div className="atom-icon"><Calendar size={14} /></div>
                                <div className="atom-content">
                                    <span className="atom-label">Event</span>
                                    <span className="atom-value">Client Call · Thursday 3:00 PM</span>
                                </div>
                            </div>
                            <div className={`memory-atom code ${step >= 5 ? 'visible' : ''}`} style={{ transitionDelay: '0.4s' }}>
                                <div className="atom-icon"><Code2 size={14} /></div>
                                <div className="atom-content">
                                    <span className="atom-label">Rule</span>
                                    <span className="atom-value">Laravel API: use ApiResponder trait</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* User Message 2 Typing */}
                {step === 6 && (
                    <div className="typing-indicator visible">
                        <span></span><span></span><span></span>
                    </div>
                )}

                {/* User Message 2 */}
                <div className={`chat-msg user-msg ${step >= 7 ? 'visible' : ''}`} style={{ display: step >= 7 ? 'flex' : 'none' }}>
                    <div className="msg-avatar user-avatar"><User size={16} /></div>
                    <div className="msg-bubble">
                        <p>Generate a user controller for the API.</p>
                    </div>
                </div>

                {/* System Message 2 Processing */}
                {step === 8 && (
                    <div className="typing-indicator visible" style={{ alignSelf: 'flex-start' }}>
                        <span></span><span></span><span></span>
                    </div>
                )}

                {/* System Message 2 - Recall */}
                <div className={`chat-msg sys-msg ${step >= 9 ? 'visible' : ''}`} style={{ display: step >= 9 ? 'flex' : 'none' }}>
                    <div className="msg-avatar sys-avatar"><Zap size={16} /></div>
                    <div className="msg-bubble sys-bubble highlight-glow">
                        <div className="sys-status">
                            <CheckCircle2 size={14} className="text-green" />
                            <span>Context assembled</span>
                        </div>
                        <div className="context-block">
                            <div className="context-head">Injected Context</div>
                            <code>
                                <span className="code-comment">// Relevant memory applied:</span><br />
                                <span className="code-keyword">Rule:</span> Always use App\Services\ApiResponder trait for Laravel API fixes.
                            </code>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    );
}
