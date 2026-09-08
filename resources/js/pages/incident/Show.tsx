import { Link, useForm, usePoll } from '@inertiajs/react';
import { ArrowLeftIcon } from '@phosphor-icons/react';
import { EventList } from '@/components/MonitoringLists';
import {
    Badge,
    Empty,
    Field,
    FormErrors,
    Panel,
    Time,
    label,
} from '@/components/ui';
import AppLayout from '@/layouts/AppLayout';
import { show } from '@/routes/project';
import {
    notes as addNote,
    status as updateStatus,
} from '@/routes/project/incidents';
import type {
    AppEvent,
    Incident,
    Organization,
    Project,
} from '@/types/workspace';

type Note = {
    id: number;
    body: string;
    created_at: string;
    user: { name: string };
};
export default function Show({
    incident,
    project,
    organization,
    events,
    notes,
}: {
    incident: Incident;
    project: Project;
    organization: Organization;
    events: AppEvent[];
    notes: Note[];
}) {
    const note = useForm({ body: '' });
    const status = useForm({ status: incident.status });
    const args = { organization, project, incidentId: incident.id };
    usePoll(15000, { only: ['incident', 'events', 'notes'] });

    return (
        <AppLayout
            title={incident.title}
            subtitle={`INC-${String(incident.id).padStart(4, '0')} / ${project.name}`}
            organization={organization}
            organizations={[organization]}
            activeTab="incidents"
            action={
                <Link
                    className="button"
                    href={show.url([organization, project])}
                >
                    <ArrowLeftIcon size={15} />
                    Voltar ao projeto
                </Link>
            }
        >
            <div className="toolbar">
                <Badge value={incident.severity} />
                <Badge value={incident.status} />
                <span className="muted">
                    Aberto em <Time value={incident.opened_at} />
                </span>
                {incident.resolved_at && (
                    <span className="muted">
                        Resolvido em <Time value={incident.resolved_at} />
                    </span>
                )}
            </div>
            <div className="dashboard-grid">
                <div>
                    <Panel title="Eventos correlacionados">
                        <EventList events={events} />
                    </Panel>
                    <Panel title="Notas da investigação">
                        {notes.length ? (
                            <div className="timeline">
                                {notes.map((item) => (
                                    <article
                                        className="timeline-row"
                                        key={item.id}
                                    >
                                        <span className="user-avatar">
                                            {item.user.name[0]}
                                        </span>
                                        <div>
                                            <strong>{item.user.name}</strong>
                                            <p
                                                style={{
                                                    whiteSpace: 'pre-wrap',
                                                }}
                                            >
                                                {item.body}
                                            </p>
                                        </div>
                                        <Time value={item.created_at} />
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <Empty title="Registre a investigação">
                                Compartilhe hipóteses, ações realizadas e
                                resultados com a equipe.
                            </Empty>
                        )}
                        {project.permissions?.update && (
                            <form
                                className="panel-body form-stack"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    note.post(addNote.url(args), {
                                        preserveScroll: true,
                                        onSuccess: () => note.reset(),
                                    });
                                }}
                            >
                                <FormErrors errors={note.errors} />
                                <Field label="Adicionar nota">
                                    <textarea
                                        required
                                        rows={4}
                                        maxLength={10000}
                                        value={note.data.body}
                                        onChange={(e) =>
                                            note.setData('body', e.target.value)
                                        }
                                        placeholder="O que você descobriu? Qual é o próximo passo?"
                                    />
                                </Field>
                                <div>
                                    <button
                                        className="button primary"
                                        disabled={note.processing}
                                    >
                                        Adicionar nota
                                    </button>
                                </div>
                            </form>
                        )}
                    </Panel>
                </div>
                <Panel title="Resposta ao incidente">
                    <div className="panel-body">
                        <p className="muted" style={{ marginBottom: 20 }}>
                            Avance o estado conforme a investigação. Resolva
                            apenas após confirmar a recuperação.
                        </p>
                        {project.permissions?.update ? (
                            <form
                                className="form-stack"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    status.post(updateStatus.url(args));
                                }}
                            >
                                <FormErrors errors={status.errors} />
                                <Field label="Estado">
                                    <select
                                        value={status.data.status}
                                        onChange={(e) =>
                                            status.setData(
                                                'status',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        {[
                                            'investigating',
                                            'identified',
                                            'monitoring',
                                            'resolved',
                                        ].map((value) => (
                                            <option key={value} value={value}>
                                                {label(value)}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <button
                                    className="button primary"
                                    disabled={status.processing}
                                >
                                    Atualizar estado
                                </button>
                            </form>
                        ) : (
                            <p className="muted">
                                Apenas gestores e proprietários podem atualizar
                                incidentes.
                            </p>
                        )}
                    </div>
                </Panel>
            </div>
        </AppLayout>
    );
}
