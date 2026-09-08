import { Link } from '@inertiajs/react';
import {
    ArrowRightIcon,
    CheckCircleIcon,
    ClockIcon,
    WarningCircleIcon,
} from '@phosphor-icons/react';
import type { ReactNode } from 'react';
import type { Collection } from '@/types/workspace';

const labels: Record<string, string> = {
    operational: 'Operacional',
    degraded: 'Degradado',
    down: 'Indisponível',
    unknown: 'Aguardando dados',
    investigating: 'Investigando',
    identified: 'Identificado',
    monitoring: 'Em observação',
    resolved: 'Resolvido',
    critical: 'Crítica',
    high: 'Alta',
    medium: 'Média',
    low: 'Baixa',
    info: 'Informação',
    warning: 'Aviso',
    error: 'Erro',
    debug: 'Depuração',
    healthy: 'Saudável',
    success: 'Sucesso',
    failed: 'Falha',
    owner: 'Proprietário',
    'project-manager': 'Gestor',
    member: 'Membro',
};
export function label(value?: string) {
    return value ? (labels[value] ?? value) : 'Sem informação';
}
export function Badge({ value }: { value: string }) {
    return (
        <span className={`badge badge-${value}`}>
            <span className="status-dot" />
            {label(value)}
        </span>
    );
}
export function Time({ value }: { value?: string }) {
    if (!value) {
        return <span className="muted">Sem dados</span>;
    }

    const date = new Date(value);

    return (
        <time dateTime={value} title={date.toLocaleString('pt-BR')}>
            {date.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            })}
        </time>
    );
}
export function Empty({
    title,
    children,
    action,
}: {
    title: string;
    children: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="empty-state">
            <ClockIcon size={30} weight="light" />
            <h3>{title}</h3>
            <p>{children}</p>
            {action}
        </div>
    );
}
export function Field({
    label: title,
    error,
    children,
    hint,
}: {
    label: string;
    error?: string;
    children: ReactNode;
    hint?: string;
}) {
    return (
        <label className="field">
            <span>{title}</span>
            {children}
            {hint && <small>{hint}</small>}
            {error && (
                <small className="field-error" role="alert">
                    {error}
                </small>
            )}
        </label>
    );
}
export function FormErrors({ errors }: { errors: Record<string, string> }) {
    return Object.keys(errors).length ? (
        <div className="notice notice-error" role="alert">
            <WarningCircleIcon size={19} />
            <div>
                {Object.entries(errors).map(([key, value]) => (
                    <p key={key}>{value}</p>
                ))}
            </div>
        </div>
    ) : null;
}
export function Notice({ children }: { children: ReactNode }) {
    return (
        <div className="notice" role="status">
            <CheckCircleIcon size={19} />
            <div>{children}</div>
        </div>
    );
}
export function Panel({
    title,
    action,
    children,
    className = '',
}: {
    title: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={`panel ${className}`}>
            <div className="panel-heading">
                <h2>{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}
export function More({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <Link className="text-link" href={href}>
            {children}
            <ArrowRightIcon size={15} />
        </Link>
    );
}
export function Pagination<T>({ collection }: { collection?: Collection<T> }) {
    if (
        !collection ||
        Array.isArray(collection) ||
        !collection.links ||
        (collection.last_page ?? 1) < 2
    ) {
        return null;
    }

    return (
        <nav className="pagination" aria-label="Paginação">
            {collection.links.map((link, i) =>
                link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        className={link.active ? 'selected' : ''}
                        preserveScroll
                    >
                        {i === 0
                            ? 'Anterior'
                            : i === collection.links!.length - 1
                              ? 'Próxima'
                              : link.label}
                    </Link>
                ) : (
                    <span key={i}>
                        {i === 0
                            ? 'Anterior'
                            : i === collection.links!.length - 1
                              ? 'Próxima'
                              : link.label}
                    </span>
                ),
            )}
        </nav>
    );
}
