import React, { useState, useRef } from 'react';
import { Download, Upload, HelpCircle, ChevronLeft, ChevronRight, Check, X, AlertTriangle } from 'lucide-react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { useToast } from '@/components/app/toast';

// Simple tooltip and modal based on standard CSS or inline styles to avoid heavy dependencies
export function MemoryImportExport() {
    const { toast, Toasts } = useToast();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [showHelp, setShowHelp] = useState(false);

    // Conflict state
    const [showConflictModal, setShowConflictModal] = useState(false);
    const [conflicts, setConflicts] = useState<any[]>([]);
    const [conflictIndex, setConflictIndex] = useState(0);
    const [resolutions, setResolutions] = useState<Record<number, 'skip' | 'overwrite'>>({});
    const [importedCount, setImportedCount] = useState(0);

    const handleExport = () => {
        window.location.href = '/memory-inspector/export';
    };

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > 7 * 1024 * 1024) {
            toast('File size exceeds 7MB limit.', 'error');
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await axios.post('/memory-inspector/import', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.status === 'conflicts') {
                setConflicts(res.data.conflicts);
                setImportedCount(res.data.imported_count);
                setConflictIndex(0);
                setResolutions({});
                setShowConflictModal(true);
            } else {
                // Success without conflicts (redirect handled by inertia usually, but we used axios)
                // Actually since it's axios, backend sent redirect()->back(). Axios might follow redirect, 
                // but for Inertia it's better to use Inertia router or handle JSON response.
                // Our backend returns a redirect for success. Let's just reload.
                router.reload({ only: ['memories', 'summary'] });
                toast('Successfully imported memories.', 'success');
            }
        } catch (err: any) {
            console.error(err);
            toast('Error importing file: ' + (err.response?.data?.message || err.message), 'error');
        } finally {
            setUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    const handleResolutionChange = (action: 'skip' | 'overwrite') => {
        setResolutions(prev => ({ ...prev, [conflictIndex]: action }));
    };

    const submitResolutions = async () => {
        const payload = conflicts.map((c, i) => ({
            memory: c.imported_memory,
            action: resolutions[i] || 'skip', // default to skip if unset
        }));

        try {
            setUploading(true);
            await axios.post('/memory-inspector/import/resolve', { resolutions: payload });
            setShowConflictModal(false);
            router.reload({ only: ['memories', 'summary'] });
            toast(`Conflict resolution complete. ${importedCount} imported initially, plus resolved ones.`, 'success');
        } catch (err: any) {
            console.error(err);
            toast('Error resolving conflicts.', 'error');
        } finally {
            setUploading(false);
        }
    };

    const currentConflict = conflicts[conflictIndex];

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', position: 'relative' }}>
            <Toasts />
            <style>{`
                .mi-tooltip-box {
                    position: absolute;
                    top: 100%;
                    right: 0;
                    margin-top: 8px;
                    width: 260px;
                    background: var(--app-surface);
                    border: 1px solid var(--app-border);
                    padding: 12px;
                    border-radius: 8px;
                    z-index: 100;
                    font-size: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .mi-conflict-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 24px;
                }
                .mi-conflict-actions {
                    display: flex;
                    justify-content: center;
                    gap: 16px;
                    margin-top: 24px;
                }
                .mi-conflict-footer {
                    padding: 16px 24px;
                    border-top: 1px solid var(--app-border);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background: var(--app-background);
                }
                
                @media (max-width: 600px) {
                    .mi-tooltip-box {
                        position: fixed;
                        top: auto;
                        bottom: 24px;
                        left: 24px;
                        right: 24px;
                        width: auto;
                    }
                    .mi-conflict-grid {
                        grid-template-columns: 1fr;
                        gap: 16px;
                    }
                    .mi-conflict-actions {
                        flex-direction: column;
                    }
                    .mi-conflict-actions button {
                        width: 100%;
                        justify-content: center;
                    }
                    .mi-conflict-footer {
                        flex-direction: column;
                        gap: 16px;
                    }
                    .mi-conflict-footer button {
                        width: 100%;
                        justify-content: center;
                    }
                }
            `}</style>
            {/* Help Tooltip */}
            <div 
                style={{ position: 'relative' }}
                onMouseEnter={() => setShowHelp(true)}
                onMouseLeave={() => setShowHelp(false)}
                onClick={() => setShowHelp(!showHelp)}
            >
                <HelpCircle size={18} style={{ color: 'var(--app-neutral-dim)', cursor: 'pointer' }} />
                {showHelp && (
                    <div className="mi-tooltip-box">
                        <strong>Import / Export</strong><br/>
                        Export your memories as a JSON file for backup or portability. Import a previously exported JSON file to restore memories. Migrated memories are securely mapped to your account.
                    </div>
                )}
            </div>

            <button onClick={handleExport} className="app-btn app-btn-secondary" style={{ padding: '6px 12px', fontSize: '13px' }}>
                <Download size={14} style={{ marginRight: '6px' }} /> Export JSON
            </button>
            
            <button 
                onClick={() => fileInputRef.current?.click()} 
                className="app-btn app-btn-primary" 
                style={{ padding: '6px 12px', fontSize: '13px' }}
                disabled={uploading}
            >
                <Upload size={14} style={{ marginRight: '6px' }} /> 
                {uploading ? 'Importing...' : 'Import JSON'}
            </button>
            
            <input 
                type="file" 
                ref={fileInputRef} 
                style={{ display: 'none' }} 
                accept=".json,application/json" 
                onChange={handleFileChange} 
            />

            {/* Conflict Modal */}
            {showConflictModal && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, 
                    backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 9999,
                    display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px'
                }}>
                    <div style={{
                        background: 'var(--app-surface)', width: '100%', maxWidth: '800px',
                        borderRadius: '12px', display: 'flex', flexDirection: 'column',
                        maxHeight: '90vh', overflow: 'hidden', border: '1px solid var(--app-border)'
                    }}>
                        <div style={{ padding: '16px 24px', borderBottom: '1px solid var(--app-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '18px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <AlertTriangle size={18} color="var(--app-warning)" />
                                Memory Conflicts ({conflictIndex + 1} of {conflicts.length})
                            </h3>
                            <button onClick={() => setShowConflictModal(false)} style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}>
                                <X size={20} />
                            </button>
                        </div>
                        
                        <div style={{ padding: '24px', overflowY: 'auto', flex: 1 }}>
                            <p style={{ fontSize: '14px', marginBottom: '16px', color: 'var(--app-neutral-dim)' }}>
                                This memory already exists in your system. Choose whether to skip importing this specific memory, or overwrite your existing memory (including its tuned scores).
                            </p>
                            
                            <div className="mi-conflict-grid">
                                <div>
                                    <h4 style={{ fontSize: '14px', marginBottom: '8px', color: 'var(--app-error)' }}>Existing Memory</h4>
                                    <pre style={{
                                        background: 'var(--app-background)', padding: '12px', borderRadius: '6px', 
                                        fontSize: '12px', overflowX: 'auto', border: '1px solid var(--app-border)'
                                    }}>
                                        {JSON.stringify({
                                            content: currentConflict?.existing_memory?.content,
                                            confidence: currentConflict?.existing_memory?.confidence,
                                            decay_score: currentConflict?.existing_memory?.decay_score,
                                            metadata: currentConflict?.existing_memory?.metadata,
                                        }, null, 2)}
                                    </pre>
                                </div>
                                <div>
                                    <h4 style={{ fontSize: '14px', marginBottom: '8px', color: 'var(--app-success)' }}>Importing Memory</h4>
                                    <pre style={{
                                        background: 'var(--app-background)', padding: '12px', borderRadius: '6px', 
                                        fontSize: '12px', overflowX: 'auto', border: '1px solid var(--app-border)'
                                    }}>
                                        {JSON.stringify({
                                            content: currentConflict?.imported_memory?.content,
                                            confidence: currentConflict?.imported_memory?.confidence,
                                            decay_score: currentConflict?.imported_memory?.decay_score,
                                            metadata: currentConflict?.imported_memory?.metadata,
                                        }, null, 2)}
                                    </pre>
                                </div>
                            </div>

                            <div className="mi-conflict-actions">
                                <button 
                                    onClick={() => handleResolutionChange('skip')}
                                    className={`app-btn ${resolutions[conflictIndex] === 'skip' ? 'app-btn-primary' : 'app-btn-secondary'}`}
                                >
                                    {resolutions[conflictIndex] === 'skip' && <Check size={14} style={{ marginRight: '6px' }} />}
                                    Skip this Memory
                                </button>
                                <button 
                                    onClick={() => handleResolutionChange('overwrite')}
                                    className={`app-btn ${resolutions[conflictIndex] === 'overwrite' ? 'app-btn-primary' : 'app-btn-secondary'}`}
                                >
                                    {resolutions[conflictIndex] === 'overwrite' && <Check size={14} style={{ marginRight: '6px' }} />}
                                    Overwrite Existing
                                </button>
                            </div>
                        </div>

                        <div className="mi-conflict-footer">
                            <button 
                                onClick={() => setConflictIndex(prev => Math.max(0, prev - 1))}
                                disabled={conflictIndex === 0}
                                className="app-btn app-btn-secondary"
                            >
                                <ChevronLeft size={16} /> Previous
                            </button>
                            
                            <div style={{ fontSize: '14px' }}>
                                {Object.keys(resolutions).length} of {conflicts.length} resolved
                            </div>

                            {conflictIndex < conflicts.length - 1 ? (
                                <button 
                                    onClick={() => setConflictIndex(prev => Math.min(conflicts.length - 1, prev + 1))}
                                    className="app-btn app-btn-secondary"
                                >
                                    Next <ChevronRight size={16} />
                                </button>
                            ) : (
                                <button 
                                    onClick={submitResolutions}
                                    className="app-btn app-btn-primary"
                                    disabled={uploading}
                                >
                                    {uploading ? 'Submitting...' : 'Submit Resolutions'}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
