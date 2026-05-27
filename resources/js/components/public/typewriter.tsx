import { useEffect, useMemo, useState } from 'react';

type Props = {
    phrases: string[];
    typingSpeed?: number;
    deletingSpeed?: number;
    delay?: number;
};

export default function Typewriter({
    phrases,
    typingSpeed = 55,
    deletingSpeed = 35,
    delay = 1200,
}: Props) {
    const items = useMemo(() => phrases.filter(Boolean), [phrases]);
    const [index, setIndex] = useState(0);
    const [text, setText] = useState('');
    const [isDeleting, setIsDeleting] = useState(false);

    useEffect(() => {
        if (items.length === 0) return;

        const current = items[index % items.length];
        
        let timerDelay = isDeleting ? deletingSpeed : typingSpeed;
        
        if (!isDeleting && text === current) {
            timerDelay = delay;
        } else if (isDeleting && text === '') {
            timerDelay = typingSpeed;
        }

        const timeout = window.setTimeout(() => {
            if (!isDeleting) {
                if (text === current) {
                    setIsDeleting(true);
                } else {
                    setText(current.slice(0, text.length + 1));
                }
            } else {
                if (text === '') {
                    setIsDeleting(false);
                    setIndex((v) => (v + 1) % items.length);
                } else {
                    setText(current.slice(0, text.length - 1));
                }
            }
        }, timerDelay);

        return () => window.clearTimeout(timeout);
    }, [text, isDeleting, index, items, typingSpeed, deletingSpeed, delay]);

    return <span>{text}<span className="type-caret">|</span></span>;
}