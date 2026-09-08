import { Link, router, useForm, usePage, usePoll } from '@inertiajs/react';
import {
    ArrowRightIcon,
    BuildingsIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    TrashIcon,
} from '@phosphor-icons/react';
import { useState } from 'react';
import {
    EventList,
    IncidentList,
    ProjectList,
} from '@/components/MonitoringLists';
import ProjectForm from '@/components/ProjectForm';
import { Field, FormErrors, More, Panel, label } from '@/components/ui';
import AppLayout, { NewProjectButton, workspaceUrl } from '@/layouts/AppLayout';
import {
    store as storeOrg,
    update as updateOrg,
    destroy as destroyOrg,
    restore,
} from '@/routes/organization';
import {
    store as addMember,
    destroy as removeMember,
} from '@/routes/organization/members';
import { show as projectShow } from '@/routes/project';
import type {
    AppEvent,
    Incident,
    Organization,
    Project,
} from '@/types/workspace';

type Props = {
    organizations: Organization[];
    organization: Organization | null;
    projects: Project[];
    incidents: Incident[];
    events: AppEvent[];
    activeTab: string;
    stats: {
        projects: number;
        operational: number;
        unhealthy: number;
        open_incidents: number;
    };
    archivedOrganizations?: Organization[];
};
const titles: Record<string, string> = {
    dashboard: 'Visão geral',
    projects: 'Seus projetos',
    incidents: 'Incidentes',
    events: 'Fluxo de eventos',
    organizations: 'Organizações',
    settings: 'Configurações',
};
const subtitles: Record<string, string> = {
    dashboard: 'O estado das suas aplicações, com o contexto que importa.',
    projects: 'Monitore aplicações, APIs e workers em um só lugar.',
    incidents: 'Investigue, acompanhe e resolva com sua equipe.',
    events: 'Os últimos 50 sinais recebidos das suas aplicações.',
    organizations: 'Um espaço compartilhado para cada equipe.',
    settings: 'Gerencie sua equipe e conecte suas aplicações.',
};
export default function Index(props: Props) {
    return (
        <Workspace
            key={`${props.organization?.id}:${props.activeTab}`}
            {...props}
        />
    );
}

function Workspace(props: Props) {
    const {
        organizations,
        organization,
        projects,
        incidents,
        events,
        activeTab,
        stats,
        archivedOrganizations = [],
    } = props;
    const { url } = usePage();
    const [create, setCreate] = useState(url.includes('create=1'));
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('all');
    usePoll(15000, { only: ['projects', 'incidents', 'events', 'stats'] });
    const orgForm = useForm({ name: '' });
    const unresolved = incidents.filter(
        (incident) => incident.status !== 'resolved',
    );
    const projectItems = projects.filter(
        (project) =>
            project.name.toLowerCase().includes(query.toLowerCase()) &&
            (status === 'all' || project.status === status),
    );
    const eventItems = events.filter(
        (event) =>
            `${event.type} ${event.message ?? ''}`
                .toLowerCase()
                .includes(query.toLowerCase()) &&
            (status === 'all' || event.severity === status),
    );
    const incidentItems = incidents.filter(
        (incident) =>
            incident.title.toLowerCase().includes(query.toLowerCase()) &&
            (status === 'all' || incident.status === status),
    );
    const filters =
        activeTab === 'projects'
            ? ['operational', 'degraded', 'down', 'unknown']
            : activeTab === 'incidents'
              ? ['investigating', 'identified', 'monitoring', 'resolved']
              : ['error', 'critical', 'warning', 'info'];

    return (
        <AppLayout
            title={titles[activeTab]}
            subtitle={subtitles[activeTab]}
            {...{ organizations, organization, activeTab }}
            action={
                organization?.permissions?.createProject &&
                ['dashboard', 'projects'].includes(activeTab) ? (
                    activeTab === 'dashboard' ? (
                        <NewProjectButton organization={organization} />
                    ) : (
                        <button
                            className="button primary"
                            onClick={() => setCreate(!create)}
                        >
                            <PlusIcon size={17} />
                            Novo projeto
                        </button>
                    )
                ) : undefined
            }
        >
            {(!organization || activeTab === 'organizations') && (
                <>
                    <Panel title="Criar organização">
                        <form
                            className="panel-body"
                            onSubmit={(e) => {
                                e.preventDefault();
                                orgForm.post(storeOrg.url(), {
                                    onSuccess: () => orgForm.reset(),
                                });
                            }}
                        >
                            <FormErrors errors={orgForm.errors} />
                            <div className="inline-form">
                                <Field label="Nome da organização">
                                    <input
                                        required
                                        value={orgForm.data.name}
                                        onChange={(e) =>
                                            orgForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Ex.: Equipe de plataforma"
                                    />
                                </Field>
                                <button
                                    className="button primary"
                                    disabled={orgForm.processing}
                                >
                                    Criar organização
                                    <ArrowRightIcon size={15} />
                                </button>
                            </div>
                        </form>
                    </Panel>
                    {organizations.length > 0 && (
                        <Panel title="Seus workspaces">
                            {organizations.map((org) => (
                                <Link
                                    className="incident-item button-row"
                                    key={org.id}
                                    href={workspaceUrl('dashboard', org)}
                                >
                                    <BuildingsIcon size={20} />
                                    <strong>{org.name}</strong>
                                    <ArrowRightIcon size={16} />
                                </Link>
                            ))}
                        </Panel>
                    )}
                    {archivedOrganizations.length > 0 && (
                        <Panel title="Organizações arquivadas">
                            {archivedOrganizations.map((org) => (
                                <div
                                    className="incident-item button-row"
                                    key={org.id}
                                >
                                    <strong>{org.name}</strong>
                                    <button
                                        className="button small"
                                        onClick={() =>
                                            router.post(restore.url(org))
                                        }
                                    >
                                        Restaurar
                                    </button>
                                </div>
                            ))}
                        </Panel>
                    )}
                </>
            )}
            {organization && activeTab === 'dashboard' && (
                <>
                    <div className="health-summary">
                        <div>
                            <small>Saúde do workspace</small>
                            <strong className="health-title">
                                <CheckCircleIcon size={20} />
                                {!projects.length
                                    ? 'Pronto para conectar'
                                    : unresolved.length
                                      ? 'Atenção necessária'
                                      : 'Acompanhe seus sinais'}
                            </strong>
                            <p>{projects.length} projetos nesta organização</p>
                        </div>
                        <div>
                            <small>Operacionais</small>
                            <strong>
                                {
                                    projects.filter(
                                        (p) => p.status === 'operational',
                                    ).length
                                }
                            </strong>
                        </div>
                        <div>
                            <small>Com degradação</small>
                            <strong>
                                {
                                    projects.filter((p) =>
                                        ['degraded', 'down'].includes(p.status),
                                    ).length
                                }
                            </strong>
                        </div>
                        <div>
                            <small>Incidentes abertos</small>
                            <strong>{stats.open_incidents}</strong>
                        </div>
                    </div>
                    <div className="dashboard-grid">
                        <div>
                            <Panel
                                title="Saúde dos projetos"
                                action={
                                    <More
                                        href={workspaceUrl(
                                            'projects',
                                            organization,
                                        )}
                                    >
                                        Ver projetos
                                    </More>
                                }
                            >
                                <ProjectList
                                    projects={projects.slice(0, 6)}
                                    organization={organization}
                                />
                            </Panel>
                            <Panel
                                title="Atividade recente"
                                action={
                                    <More
                                        href={workspaceUrl(
                                            'events',
                                            organization,
                                        )}
                                    >
                                        Ver eventos
                                    </More>
                                }
                            >
                                <EventList events={events.slice(0, 6)} />
                            </Panel>
                        </div>
                        <Panel
                            title="Fila de incidentes"
                            action={
                                <span className="muted mono">
                                    {stats.open_incidents}
                                </span>
                            }
                        >
                            <IncidentList
                                incidents={unresolved.slice(0, 5)}
                                organization={organization}
                            />
                        </Panel>
                    </div>
                </>
            )}
            {organization &&
                ['projects', 'incidents', 'events'].includes(activeTab) && (
                    <>
                        {activeTab === 'projects' &&
                            create &&
                            organization.permissions?.createProject && (
                                <Panel title="Novo projeto">
                                    <div className="panel-body">
                                        <ProjectForm
                                            organization={organization}
                                            onCancel={() => setCreate(false)}
                                        />
                                    </div>
                                </Panel>
                            )}
                        <div className="toolbar">
                            <label className="search">
                                <MagnifyingGlassIcon size={17} />
                                <input
                                    className="search-input"
                                    aria-label="Buscar nesta lista"
                                    placeholder="Buscar nesta lista..."
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                />
                            </label>
                            <select
                                className="filter-select"
                                aria-label="Filtrar estado"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                            >
                                <option value="all">Todos os estados</option>
                                {filters.map((value) => (
                                    <option key={value} value={value}>
                                        {label(value)}
                                    </option>
                                ))}
                            </select>
                            <span className="muted" style={{ fontSize: 11 }}>
                                Atualização a cada 15 segundos
                            </span>
                        </div>
                        <Panel
                            title={
                                activeTab === 'projects'
                                    ? 'Aplicações monitoradas'
                                    : activeTab === 'incidents'
                                      ? 'Últimos 50 incidentes'
                                      : 'Eventos recebidos'
                            }
                        >
                            {activeTab === 'projects' ? (
                                <ProjectList
                                    projects={projectItems}
                                    organization={organization}
                                />
                            ) : activeTab === 'incidents' ? (
                                <IncidentList
                                    incidents={incidentItems}
                                    organization={organization}
                                />
                            ) : (
                                <EventList events={eventItems} />
                            )}
                        </Panel>
                    </>
                )}
            {organization && activeTab === 'settings' && (
                <Settings
                    key={organization.id}
                    organization={organization}
                    projects={projects}
                />
            )}
        </AppLayout>
    );
}
function Settings({
    organization,
    projects,
}: {
    organization: Organization;
    projects: Project[];
}) {
    const form = useForm({ name: organization.name });
    const member = useForm({ email: '', role: 'member' });

    return (
        <>
            <div className="settings-grid">
                <Panel title="Organização">
                    <form
                        className="panel-body form-stack"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(updateOrg.url(organization));
                        }}
                    >
                        <FormErrors errors={form.errors} />
                        <Field label="Nome">
                            <input
                                disabled={!organization.permissions?.update}
                                required
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                        </Field>
                        {organization.permissions?.update && (
                            <div>
                                <button
                                    className="button primary"
                                    disabled={form.processing}
                                >
                                    Salvar alterações
                                </button>
                            </div>
                        )}
                    </form>
                </Panel>
                <Panel title="Conectar aplicações">
                    <div className="panel-body hint-block">
                        <p>
                            Os tokens e os segredos do GitHub pertencem a cada
                            projeto. Abra um projeto para configurar sua
                            integração.
                        </p>
                        {projects.map((project) => (
                            <Link
                                key={project.id}
                                className="incident-item"
                                href={projectShow.url([organization, project], {
                                    query: { tab: 'settings' },
                                })}
                            >
                                {project.name} <ArrowRightIcon size={14} />
                            </Link>
                        ))}
                        {!projects.length && (
                            <p>
                                Cadastre o primeiro projeto para gerar
                                credenciais.
                            </p>
                        )}
                    </div>
                </Panel>
            </div>
            <Panel title="Membros da equipe">
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Pessoa</th>
                                <th>Papel</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            {organization.members?.map((person) => (
                                <tr key={person.id}>
                                    <td>
                                        <strong>{person.name}</strong>
                                        <small>{person.email}</small>
                                    </td>
                                    <td>
                                        {label(
                                            typeof person.role === 'string'
                                                ? person.role
                                                : person.role?.name,
                                        )}
                                    </td>
                                    <td>
                                        {organization.permissions
                                            ?.manageMembers &&
                                            person.id !==
                                                organization.owner_id && (
                                                <button
                                                    className="icon-button"
                                                    aria-label={`Remover ${person.name}`}
                                                    onClick={() => {
                                                        if (
                                                            confirm(
                                                                `Remover ${person.name} desta organização?`,
                                                            )
                                                        ) {
                                                            router.delete(
                                                                removeMember.url(
                                                                    {
                                                                        organization,
                                                                        userId: person.id,
                                                                    },
                                                                ),
                                                            );
                                                        }
                                                    }}
                                                >
                                                    <TrashIcon size={16} />
                                                </button>
                                            )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {organization.permissions?.manageMembers && (
                    <form
                        className="panel-body"
                        onSubmit={(e) => {
                            e.preventDefault();
                            member.post(addMember.url(organization), {
                                onSuccess: () => member.reset(),
                            });
                        }}
                    >
                        <FormErrors errors={member.errors} />
                        <div className="inline-form">
                            <Field
                                label="E-mail do membro"
                                hint="A pessoa precisa ter uma conta. Reenvie para alterar seu papel."
                            >
                                <input
                                    type="email"
                                    required
                                    value={member.data.email}
                                    onChange={(e) =>
                                        member.setData('email', e.target.value)
                                    }
                                />
                            </Field>
                            <Field label="Papel">
                                <select
                                    value={member.data.role}
                                    onChange={(e) =>
                                        member.setData('role', e.target.value)
                                    }
                                >
                                    <option value="member">Membro</option>
                                    <option value="project-manager">
                                        Gestor de projetos
                                    </option>
                                    <option value="owner">Proprietário</option>
                                </select>
                            </Field>
                            <button
                                className="button primary"
                                disabled={member.processing}
                            >
                                Salvar membro
                            </button>
                        </div>
                    </form>
                )}
            </Panel>
            {organization.permissions?.delete && (
                <Panel title="Arquivar organização">
                    <div className="panel-body hint-block">
                        <p>
                            Arquivar interrompe o acesso e o monitoramento dos
                            projetos. Você poderá restaurar a organização
                            depois.
                        </p>
                        <button
                            className="button danger"
                            onClick={() => {
                                if (
                                    confirm(
                                        `Arquivar ${organization.name} e suspender seu monitoramento?`,
                                    )
                                ) {
                                    router.delete(destroyOrg.url(organization));
                                }
                            }}
                        >
                            Arquivar organização
                        </button>
                    </div>
                </Panel>
            )}
        </>
    );
}
