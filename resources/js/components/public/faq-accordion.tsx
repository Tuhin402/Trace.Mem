import { useState } from 'react';
import { Plus } from 'lucide-react';

type Item = {
    q: string;
    a: string;
};

type Props = {
    items: Item[];
};

export default function FaqAccordion({ items }: Props) {
    const [openIndex, setOpenIndex] = useState<number>(0);

    const toggle = (index: number) => {
        setOpenIndex((prev) => (prev === index ? -1 : index));
    };

    return (
        <div className="faq-list" role="list">
            {items.map((item, index) => {
                const isOpen = openIndex === index;
                return (
                    <div
                        key={item.q}
                        className={`faq-item ${isOpen ? 'open' : ''}`}
                        role="listitem"
                    >
                        <button
                            type="button"
                            className="faq-question"
                            onClick={() => toggle(index)}
                            aria-expanded={isOpen}
                            id={`faq-q-${index}`}
                            aria-controls={`faq-a-${index}`}
                        >
                            <span className="faq-q-text">{item.q}</span>
                            <Plus size={18} className="faq-icon" aria-hidden="true" />
                        </button>

                        {isOpen && (
                            <div
                                className="faq-answer"
                                id={`faq-a-${index}`}
                                role="region"
                                aria-labelledby={`faq-q-${index}`}
                            >
                                {item.a}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}