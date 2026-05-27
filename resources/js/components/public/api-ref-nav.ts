/**
 * Shared sidebar navigation groups for all API Reference pages.
 * Single source of truth — import this in every api-reference page file.
 */
export type SidebarItem = {
    label: string;
    href: string;
    subtitle?: string;
};

export type SidebarGroup = {
    title: string;
    items: SidebarItem[];
};

export const apiRefGroups: SidebarGroup[] = [
    {
        title: 'Getting started',
        items: [
            { label: 'Overview',            href: '/api-reference',                   subtitle: 'TraceMem REST API'          },
            { label: 'Quick start',          href: '/api-reference/quick-start',       subtitle: 'First working request'      },
            { label: 'Core operations',      href: '/api-reference/core-operations',   subtitle: 'Remember and recall'        },
            { label: 'Auth & authorization', href: '/api-reference/authentication',    subtitle: 'Bearer keys and scopes'     },
        ],
    },
    {
        title: 'Endpoints',
        items: [
            { label: 'Health',           href: '/api-reference/health',           subtitle: 'Service uptime check'   },
            { label: 'Remember',         href: '/api-reference/remember',         subtitle: 'Store memory units'     },
            { label: 'Recall',           href: '/api-reference/recall',           subtitle: 'Fetch relevant memory'  },
            { label: 'Context assemble', href: '/api-reference/context-assemble', subtitle: 'Prompt-ready context'   },
        ],
    },
];
