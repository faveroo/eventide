import { Link } from '@inertiajs/react';
import {
    ActivityIcon,
    ArrowUpRightIcon,
    GitBranchIcon,
    HardDrivesIcon,
} from '@phosphor-icons/react';
import { Badge, Empty, Time } from '@/components/ui';
import { show as projectShow } from '@/routes/project';
import { show as incidentShow } from '@/routes/project/incidents';
import type {
    AppEvent,
    Incident,
    Organization,
    Project,
} from '@/types/workspace';

export function ProjectList({
    projects,
    organization,
}: {
    projects: Project[];
    organization: Organization;
}) {
    return projects.length ? (
        <div className="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Aplicação</th>
                        <th>Estado</th>
                        <th>Última verificação</th>
                        <th>
                            <span className="sr-only">Abrir</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {projects.map((project) => (
                        <tr key={project.id}>
                            <td>
                                <Link
                                    className="project-name"
                                    href={projectShow.url([
                                        organization,
                                        project,
                                    ])}
                                >
                                    <span className="project-icon">
                                        <HardDrivesIcon size={18} />
                                    </span>
                                    <span>
                                        <strong>{project.name}</strong>
                                        <small>
                                            {project.base_url ??
                                                'Endpoint não configurado'}
                                        </small>
                                    </span>
                                </Link>
                            </td>
                            <td>
                                <Badge
                                    value={
                                        project.active
                                            ? project.status
                                            : 'unknown'
                                    }
                                />
                            </td>
                            <td>
                                <Time value={project.last_checked_at} />
                            </td>
                            <td>
                                <Link
                                    className="icon-button"
                                    aria-label={`Abrir ${project.name}`}
                                    href={projectShow.url([
                                        organization,
                                        project,
                                    ])}
                                >
                                    <ArrowUpRightIcon size={16} />
                                </Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    ) : (
        <Empty title="Conecte seu primeiro projeto">
            Cadastre uma aplicação para acompanhar verificações, eventos e
            incidentes.
        </Empty>
    );
}
export function IncidentList({
    incidents,
    organization,
    project,
}: {
    incidents: Incident[];
    organization: Organization;
    project?: Project;
}) {
    return incidents.length ? (
        <div>
            {incidents.map((incident) => {
                const itemProject = incident.project ?? project;

                return itemProject ? (
                    <Link
                        className="incident-item"
                        key={incident.id}
                        href={incidentShow.url({
                            organization,
                            project: itemProject,
                            incidentId: incident.id,
                        })}
                    >
                        <div className="incident-top">
                            <code>
                                INC-{String(incident.id).padStart(4, '0')}
                            </code>
                            <Badge value={incident.severity} />
                        </div>
                        <h3>{incident.title}</h3>
                        <div className="item-meta">
                            <span>{itemProject.name}</span>
                            <Badge value={incident.status} />
                        </div>
                        <div className="item-meta" style={{ marginTop: 10 }}>
                            <Time
                                value={
                                    incident.opened_at ?? incident.created_at
                                }
                            />
                            <ArrowUpRightIcon size={14} />
                        </div>
                    </Link>
                ) : null;
            })}
        </div>
    ) : (
        <Empty title="Nenhum incidente por aqui">
            Incidentes detectados pelas regras e verificações aparecerão nesta
            lista.
        </Empty>
    );
}
export function EventList({ events }: { events: AppEvent[] }) {
    return events.length ? (
        <div className="timeline">
            {events.map((event) => (
                <article className="timeline-row" key={event.id}>
                    <span className="timeline-symbol">
                        {event.source === 'github' ? (
                            <GitBranchIcon size={16} />
                        ) : (
                            <ActivityIcon size={16} />
                        )}
                    </span>
                    <div>
                        <strong>{event.type}</strong>
                        <p>
                            {event.message ??
                                event.project?.name ??
                                'Evento recebido da aplicação'}
                        </p>
                        <div className="button-row" style={{ marginTop: 7 }}>
                            <Badge value={event.severity ?? 'info'} />
                            {event.project && (
                                <small className="muted">
                                    {event.project.name}
                                </small>
                            )}
                        </div>
                        {Object.keys(event.payload ?? event.metadata ?? {})
                            .length > 0 && (
                            <details>
                                <summary>Ver dados do evento</summary>
                                <pre>
                                    {JSON.stringify(
                                        event.payload ?? event.metadata,
                                        null,
                                        2,
                                    )}
                                </pre>
                            </details>
                        )}
                    </div>
                    <Time value={event.occurred_at ?? event.created_at} />
                </article>
            ))}
        </div>
    ) : (
        <Empty title="Esperando o primeiro sinal">
            Gere um token nas configurações do projeto e envie um evento pela
            API.
        </Empty>
    );
}
