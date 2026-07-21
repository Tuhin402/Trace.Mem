import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Building2, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useIsMobile } from '@/hooks/use-mobile';
import type { WorkspaceContext, WorkspaceSwitcherItem } from '@/types';

type WorkspaceSwitcherProps = {
    inHeader?: boolean;
};

/**
 * WorkspaceSwitcher — shown only for Company (tenant) accounts.
 * Individual accounts never see this component.
 *
 * Reads workspace context from Inertia shared props (set by HandleInertiaRequests).
 * Switches workspace via POST /workspaces/{slug}/switch (preserves CSRF).
 */
export function WorkspaceSwitcher({ inHeader = false }: WorkspaceSwitcherProps) {
    const page = usePage();
    const isMobile = useIsMobile();

    const workspace = page.props.workspace as WorkspaceContext | null;
    const account = page.props.account;

    // Don't render for Individual accounts or when no workspace context
    if (!account?.isCompany || !workspace?.id) {
        return null;
    }

    const allWorkspaces: WorkspaceSwitcherItem[] = workspace.all ?? [];
    const currentName = workspace.name ?? 'Select workspace';

    const switchWorkspace = (item: WorkspaceSwitcherItem) => {
        if (item.isCurrent) return;
        router.post(
            `/workspaces/${item.slug}/switch`,
            {},
            { preserveScroll: false }
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    data-test="workspace-switcher-trigger"
                    className={
                        inHeader
                            ? 'h-8 gap-1 px-2'
                            : 'w-full justify-start px-2 has-[>svg]:px-2 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground'
                    }
                >
                    <Building2
                        className={
                            inHeader
                                ? 'hidden'
                                : 'hidden size-4 shrink-0 group-data-[collapsible=icon]:block'
                        }
                    />
                    <div
                        className={
                            inHeader
                                ? 'grid flex-1 text-left text-sm leading-tight'
                                : 'grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden'
                        }
                    >
                        <span
                            className={
                                inHeader
                                    ? 'max-w-[140px] truncate font-medium'
                                    : 'truncate font-semibold'
                            }
                        >
                            {currentName}
                        </span>
                        {workspace.environment && (
                            <span className="truncate text-xs text-muted-foreground capitalize">
                                {workspace.environment}
                            </span>
                        )}
                    </div>
                    <ChevronsUpDown
                        className={
                            inHeader
                                ? 'size-4 opacity-50'
                                : 'ml-auto group-data-[collapsible=icon]:hidden'
                        }
                    />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                className={
                    inHeader
                        ? 'w-64'
                        : 'w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg'
                }
                side={inHeader ? undefined : isMobile ? 'bottom' : 'right'}
                align={inHeader ? 'end' : 'start'}
                sideOffset={inHeader ? undefined : 4}
            >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Workspaces
                </DropdownMenuLabel>

                {allWorkspaces.map((item) => (
                    <DropdownMenuItem
                        key={item.id}
                        data-test="workspace-switcher-item"
                        className={
                            inHeader
                                ? 'cursor-pointer gap-2'
                                : 'cursor-pointer gap-2 p-2'
                        }
                        onSelect={() => switchWorkspace(item)}
                    >
                        <span className="truncate">{item.name}</span>
                        {item.isCurrent && (
                            <Check
                                className={
                                    inHeader
                                        ? 'ml-auto size-4'
                                        : 'ml-auto h-4 w-4'
                                }
                            />
                        )}
                    </DropdownMenuItem>
                ))}

                <DropdownMenuSeparator />

                <DropdownMenuItem
                    data-test="workspace-switcher-manage"
                    className={
                        inHeader
                            ? 'cursor-pointer gap-2'
                            : 'cursor-pointer gap-2 p-2'
                    }
                    onSelect={() => router.visit('/workspaces')}
                >
                    <Plus className={inHeader ? 'size-4' : 'h-4 w-4'} />
                    <span className="text-muted-foreground">Manage workspaces</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
