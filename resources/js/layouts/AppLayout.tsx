import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ActivityIcon,
    ArrowSquareOutIcon,
    BuildingsIcon,
    CaretDownIcon,
    CirclesFourIcon,
    GearSixIcon,
    ListIcon,
    MoonIcon,
    PlusIcon,
    SignOutIcon,
    SquaresFourIcon,
    SunIcon,
    WarningDiamondIcon,
    XIcon,
} from '@phosphor-icons/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Notice } from '@/components/ui';
import { dashboard, logout } from '@/routes';
import type { Organization } from '@/types/workspace';

const items = [
    ['dashboard', 'Visão geral', SquaresFourIcon],
    ['projects', 'Projetos', CirclesFourIcon],
    ['incidents', 'Incidentes', WarningDiamondIcon],
    ['events', 'Eventos', ActivityIcon],
    ['organizations', 'Organizações', BuildingsIcon],
    ['settings', 'Configurações', GearSixIcon],
] as const;
export function workspaceUrl(tab: string, org?: Organization | null) {
    return dashboard.url({
        query: { tab, ...(org ? { organization: org.slug } : {}) },
    });
}
export default function AppLayout({
    title,
    subtitle,
    children,
    activeTab = 'dashboard',
    organizations = [],
    organization,
    action,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
    activeTab?: string;
    organizations?: Organization[];
    organization?: Organization | null;
    action?: ReactNode;
}) {
    const { auth, flash } = usePage<{
        auth: { user: { name: string; email: string } };
        flash?: {
            success?: string;
            message?: string;
            token?: string;
            api_token?: string;
            github_secret?: string;
        };
    }>().props;
    const [menu, setMenu] = useState(false);
    const [dark, setDark] = useState(
        () =>
            typeof window !== 'undefined' &&
            (localStorage.getItem('eventide-theme') === 'dark' ||
                (!localStorage.getItem('eventide-theme') &&
                    matchMedia('(prefers-color-scheme: dark)').matches)),
    );
    function toggleTheme() {
        const next = !dark;
        setDark(next);
        document.documentElement.dataset.theme = next ? 'dark' : 'light';
        localStorage.setItem('eventide-theme', next ? 'dark' : 'light');
    }

    return (
        <div className="app-shell">
            <Head title={title} />
            <a href="#main" className="skip-link">
                Ir para o conteúdo
            </a>
            {menu && (
                <button
                    className="nav-scrim"
                    onClick={() => setMenu(false)}
                    aria-label="Fechar navegação"
                />
            )}
            <aside className={`sidebar ${menu ? 'is-open' : ''}`}>
                <Link href={dashboard.url()} className="brand">
                    <span className="brand-mark">
                        <ActivityIcon weight="bold" size={24} />
                    </span>
                    eventide<span className="brand-period">.</span>
                </Link>
                <button
                    className="icon-button mobile-close"
                    onClick={() => setMenu(false)}
                    aria-label="Fechar menu"
                >
                    <XIcon size={20} />
                </button>
                <div className="workspace-picker">
                    <span className="org-avatar">
                        {organization?.name.slice(0, 1) ?? 'E'}
                    </span>
                    <div>
                        <small>Workspace</small>
                        <select
                            aria-label="Selecionar organização"
                            value={organization?.slug ?? ''}
                            onChange={(e) =>
                                router.get(
                                    dashboard.url({
                                        query: {
                                            organization: e.target.value,
                                            tab: activeTab,
                                        },
                                    }),
                                )
                            }
                        >
                            <option value="" disabled>
                                Selecionar organização
                            </option>
                            {organizations.map((org) => (
                                <option key={org.id} value={org.slug}>
                                    {org.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <CaretDownIcon size={14} />
                </div>
                <nav aria-label="Principal">
                    {items.map(([key, name, Icon]) => (
                        <Link
                            key={key}
                            href={workspaceUrl(key, organization)}
                            className={`nav-item ${activeTab === key ? 'active' : ''}`}
                            onClick={() => setMenu(false)}
                        >
                            <Icon
                                size={20}
                                weight={activeTab === key ? 'fill' : 'regular'}
                            />
                            {name}
                        </Link>
                    ))}
                </nav>
                <div className="sidebar-bottom">
                    <div className="sidebar-note">
                        <span className="mini-brand">
                            Detect. Correlate. Respond.
                        </span>
                        <p>O contexto certo para responder mais rápido.</p>
                        <Link href={workspaceUrl('settings', organization)}>
                            Conectar aplicação <ArrowSquareOutIcon size={14} />
                        </Link>
                    </div>
                    <div className="user-row">
                        <span className="user-avatar">
                            {auth.user.name
                                .split(' ')
                                .slice(0, 2)
                                .map((n) => n[0])
                                .join('')}
                        </span>
                        <div>
                            <strong>{auth.user.name}</strong>
                            <small>{auth.user.email}</small>
                        </div>
                        <button
                            className="icon-button"
                            aria-label="Sair da conta"
                            onClick={() => router.post(logout.url())}
                        >
                            <SignOutIcon size={18} />
                        </button>
                    </div>
                </div>
            </aside>
            <div className="main-column">
                <header className="topbar">
                    <div className="topbar-breadcrumb">
                        <button
                            className="icon-button mobile-menu"
                            aria-label="Abrir navegação"
                            onClick={() => setMenu(true)}
                        >
                            <ListIcon size={22} />
                        </button>
                        <span>{organization?.name ?? 'Seu workspace'}</span>
                        <span className="breadcrumb-slash">/</span>
                        <strong>
                            {items.find(([key]) => key === activeTab)?.[1] ??
                                title}
                        </strong>
                    </div>
                    <div className="topbar-actions">
                        <span className="connection-note">
                            Monitoramento de aplicações
                        </span>
                        <button
                            className="icon-button"
                            onClick={toggleTheme}
                            aria-label={
                                dark ? 'Usar tema claro' : 'Usar tema escuro'
                            }
                        >
                            {dark ? (
                                <SunIcon size={20} />
                            ) : (
                                <MoonIcon size={20} />
                            )}
                        </button>
                    </div>
                </header>
                <main id="main">
                    <div className="page-heading">
                        <div>
                            <h1>{title}</h1>
                            {subtitle && <p>{subtitle}</p>}
                        </div>
                        {action}
                    </div>
                    {(flash?.success || flash?.message) && (
                        <Notice>{flash.success ?? flash.message}</Notice>
                    )}
                    {(flash?.token ||
                        flash?.api_token ||
                        flash?.github_secret) && (
                        <Notice>
                            <strong>
                                Copie a credencial agora. Ela só será exibida
                                uma vez.
                            </strong>
                            <code className="secret">
                                {flash.token ??
                                    flash.api_token ??
                                    flash.github_secret}
                            </code>
                        </Notice>
                    )}
                    {children}
                </main>
                <footer className="app-footer">
                    <span>Eventide</span>
                    <span>Detectar. Entender. Resolver.</span>
                </footer>
            </div>
        </div>
    );
}
export function NewProjectButton({
    organization,
}: {
    organization?: Organization | null;
}) {
    return (
        <Link
            className="button primary"
            href={workspaceUrl('projects', organization) + '&create=1'}
        >
            <PlusIcon size={17} />
            Novo projeto
        </Link>
    );
}
