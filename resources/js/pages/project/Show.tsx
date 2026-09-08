import { router, useForm, usePage, usePoll } from '@inertiajs/react';
import {
    GitBranchIcon,
    KeyIcon,
    PlusIcon,
    TrashIcon,
} from '@phosphor-icons/react';
import { useState } from 'react';
import { EventList, IncidentList } from '@/components/MonitoringLists';
import ProjectForm from '@/components/ProjectForm';
import { Badge, Empty, Field, FormErrors, Panel, Time } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout';
import { destroy, rotateGithubSecret, rotateToken } from '@/routes/project';
import { store as storeIncident } from '@/routes/project/incidents';
import {
    store as storeRule,
    update as updateRule,
    destroy as destroyRule,
} from '@/routes/project/rules';
import type {
    AppEvent,
    Check,
    Incident,
    Organization,
    Project,
    Rule,
} from '@/types/workspace';

type Props = {
    project: Project;
    organization: Organization;
    events: AppEvent[];
    checks: Check[];
    incidents: Incident[];
    rules: Rule[];
};
export default function Show({
    project,
    organization,
    events,
    checks,
    incidents,
    rules,
}: Props) {
    const page = usePage();
    const [tab, setTab] = useState(
        page.url.includes('tab=settings') ? 'settings' : 'overview',
    );
    const [newIncident, setNewIncident] = useState(false);
    const form = useForm({ title: '', severity: 'high' });
    usePoll(15000, { only: ['project', 'events', 'checks', 'incidents'] });

    return (
        <AppLayout
            title={project.name}
            subtitle={
                project.description ??
                'Saúde, eventos e investigação desta aplicação.'
            }
            organization={organization}
            organizations={[organization]}
            activeTab="projects"
            action={<Badge value={project.status} />}
        >
            <div className="tabs" role="tablist" aria-label="Áreas do projeto">
                {[
                    ['overview', 'Visão geral'],
                    ['events', 'Eventos'],
                    ['checks', 'Verificações'],
                    ['incidents', 'Incidentes'],
                    ['rules', 'Regras de detecção'],
                    ['settings', 'Configurações'],
                ].map(([key, name]) => (
                    <button
                        key={key}
                        role="tab"
                        aria-selected={tab === key}
                        className={tab === key ? 'active' : ''}
                        onClick={() => setTab(key)}
                    >
                        {name}
                    </button>
                ))}
            </div>
            {tab === 'overview' && (
                <>
                    <div className="health-summary">
                        <div>
                            <small>Última verificação</small>
                            <strong className="health-title">
                                <Time value={project.last_checked_at} />
                            </strong>
                            <p>
                                {project.active
                                    ? 'Monitoramento ativo'
                                    : 'Monitoramento pausado'}
                            </p>
                        </div>
                        <div>
                            <small>Latência mais recente</small>
                            <strong>{checks[0]?.latency_ms ?? '—'}</strong>
                            <small>milissegundos</small>
                        </div>
                        <div>
                            <small>Incidentes abertos</small>
                            <strong>
                                {
                                    incidents.filter(
                                        (i) => i.status !== 'resolved',
                                    ).length
                                }
                            </strong>
                        </div>
                        <div>
                            <small>Verificações exibidas</small>
                            <strong>{checks.length}</strong>
                        </div>
                    </div>
                    <div className="dashboard-grid">
                        <Panel title="Eventos recentes">
                            <EventList events={events.slice(0, 8)} />
                        </Panel>
                        <Panel title="Incidentes em andamento">
                            <IncidentList
                                incidents={incidents
                                    .filter((i) => i.status !== 'resolved')
                                    .slice(0, 5)}
                                {...{ organization, project }}
                            />
                        </Panel>
                    </div>
                </>
            )}
            {tab === 'events' && (
                <Panel title="Últimos 100 eventos">
                    <EventList events={events} />
                </Panel>
            )}
            {tab === 'checks' && (
                <Panel title="Últimas 100 verificações">
                    {checks.length ? (
                        <div className="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Horário</th>
                                        <th>Estado</th>
                                        <th>HTTP</th>
                                        <th>Latência</th>
                                        <th>Diagnóstico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {checks.map((check) => (
                                        <tr key={check.id}>
                                            <td>
                                                <Time
                                                    value={check.checked_at}
                                                />
                                            </td>
                                            <td>
                                                <Badge
                                                    value={
                                                        check.status ??
                                                        'unknown'
                                                    }
                                                />
                                            </td>
                                            <td className="mono">
                                                {check.response_status ??
                                                    'Sem resposta'}
                                            </td>
                                            <td className="mono">
                                                {check.latency_ms ?? '-'} ms
                                            </td>
                                            <td>
                                                {check.error ??
                                                    'Verificação concluída'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <Empty title="Aguardando a primeira verificação">
                            Configure o endpoint de saúde e mantenha o scheduler
                            e o worker em execução.
                        </Empty>
                    )}
                </Panel>
            )}
            {tab === 'incidents' && (
                <>
                    <Panel
                        title="Incidentes do projeto"
                        action={
                            project.permissions?.update && (
                                <button
                                    className="button small"
                                    onClick={() => setNewIncident(!newIncident)}
                                >
                                    <PlusIcon size={14} />
                                    Criar incidente
                                </button>
                            )
                        }
                    >
                        {newIncident && (
                            <form
                                className="panel-body form-stack"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    form.post(
                                        storeIncident.url([
                                            organization,
                                            project,
                                        ]),
                                    );
                                }}
                            >
                                <FormErrors errors={form.errors} />
                                <Field label="O que aconteceu?">
                                    <input
                                        required
                                        value={form.data.title}
                                        onChange={(e) =>
                                            form.setData(
                                                'title',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field label="Severidade">
                                    <select
                                        value={form.data.severity}
                                        onChange={(e) =>
                                            form.setData(
                                                'severity',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="low">Baixa</option>
                                        <option value="medium">Média</option>
                                        <option value="high">Alta</option>
                                        <option value="critical">
                                            Crítica
                                        </option>
                                    </select>
                                </Field>
                                <button
                                    disabled={form.processing}
                                    className="button primary"
                                >
                                    Criar incidente
                                </button>
                            </form>
                        )}
                        <IncidentList
                            {...{ incidents, organization, project }}
                        />
                    </Panel>
                </>
            )}
            {tab === 'rules' && <Rules {...{ rules, organization, project }} />}
            {tab === 'settings' && (
                <>
                    <Panel title="Configuração do projeto">
                        <div className="panel-body">
                            {project.permissions?.update ? (
                                <ProjectForm {...{ project, organization }} />
                            ) : (
                                <p className="muted">
                                    Apenas gestores e proprietários podem
                                    alterar este projeto.
                                </p>
                            )}
                        </div>
                    </Panel>
                    <div className="settings-grid">
                        <Panel title="Token de ingestão">
                            <div className="panel-body hint-block">
                                <KeyIcon size={24} />
                                <p>
                                    O token identifica exclusivamente este
                                    projeto. Guarde-o no servidor da sua
                                    aplicação.
                                </p>
                                <p>
                                    {project.has_api_token
                                        ? 'Um token está configurado. Gerar outro revoga o anterior imediatamente.'
                                        : 'Nenhum token foi gerado.'}
                                </p>
                                {project.permissions?.update && (
                                    <button
                                        className="button"
                                        onClick={() => {
                                            if (
                                                !project.has_api_token ||
                                                confirm(
                                                    'Revogar o token atual e gerar outro?',
                                                )
                                            ) {
                                                router.post(
                                                    rotateToken.url([
                                                        organization,
                                                        project,
                                                    ]),
                                                );
                                            }
                                        }}
                                    >
                                        Gerar token
                                    </button>
                                )}
                                <pre>{`POST /api/v1/events\nAuthorization: Bearer SEU_TOKEN\nIdempotency-Key: ID_UNICO\nContent-Type: application/json\n\n{\n  "type": "payment.failed",\n  "severity": "error",\n  "message": "Pagamento recusado",\n  "payload": { "payment_id": "pay_42" }\n}`}</pre>
                            </div>
                        </Panel>
                        <Panel title="Integração GitHub">
                            <div className="panel-body hint-block">
                                <GitBranchIcon size={24} />
                                <p>
                                    Adicione um webhook no repositório usando a
                                    URL abaixo, Content type application/json e
                                    o segredo gerado aqui.
                                </p>
                                <pre>{`${typeof window !== 'undefined' ? window.location.origin : ''}/api/v1/webhooks/github/${project.id}`}</pre>
                                <p>
                                    Selecione os eventos de deployment e
                                    workflow. As entregas são verificadas por
                                    assinatura HMAC.
                                </p>
                                {project.permissions?.update && (
                                    <button
                                        className="button"
                                        onClick={() => {
                                            if (
                                                !project.has_github_secret ||
                                                confirm(
                                                    'Substituir o segredo do GitHub? Atualize o webhook em seguida.',
                                                )
                                            ) {
                                                router.post(
                                                    rotateGithubSecret.url([
                                                        organization,
                                                        project,
                                                    ]),
                                                );
                                            }
                                        }}
                                    >
                                        Gerar segredo GitHub
                                    </button>
                                )}
                            </div>
                        </Panel>
                    </div>
                    {project.permissions?.delete && (
                        <Panel title="Remover projeto">
                            <div className="panel-body hint-block">
                                <p>
                                    O projeto deixará de receber eventos e
                                    verificações. O histórico permanece
                                    armazenado.
                                </p>
                                <button
                                    className="button danger"
                                    onClick={() => {
                                        if (
                                            confirm(
                                                `Remover o projeto ${project.name}?`,
                                            )
                                        ) {
                                            router.delete(
                                                destroy.url([
                                                    organization,
                                                    project,
                                                ]),
                                            );
                                        }
                                    }}
                                >
                                    Remover projeto
                                </button>
                            </div>
                        </Panel>
                    )}
                </>
            )}
        </AppLayout>
    );
}
function Rules({
    rules,
    organization,
    project,
}: {
    rules: Rule[];
    organization: Organization;
    project: Project;
}) {
    const [editing, setEditing] = useState<Rule | null>(null);
    const form = useForm({
        name: '',
        event_type: '',
        threshold: 20,
        window_seconds: 300,
        severity: 'high',
        enabled: true,
    });
    function edit(rule: Rule) {
        setEditing(rule);
        form.setData({
            name: rule.name,
            event_type: rule.event_type ?? '',
            threshold: rule.threshold,
            window_seconds: rule.window_seconds ?? 300,
            severity: rule.severity,
            enabled: rule.enabled,
        });
    }

    return (
        <>
            <Panel title="Regras configuradas">
                {rules.length ? (
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Regra</th>
                                    <th>Condição</th>
                                    <th>Severidade</th>
                                    <th>Estado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rules.map((rule) => (
                                    <tr key={rule.id}>
                                        <td>
                                            <strong>{rule.name}</strong>
                                            <small>{rule.event_type}</small>
                                        </td>
                                        <td>
                                            {rule.threshold} eventos /{' '}
                                            {rule.window_seconds}s
                                        </td>
                                        <td>
                                            <Badge value={rule.severity} />
                                        </td>
                                        <td>
                                            {rule.enabled ? 'Ativa' : 'Pausada'}
                                        </td>
                                        <td>
                                            {project.permissions?.update && (
                                                <div className="button-row">
                                                    <button
                                                        className="button small"
                                                        onClick={() =>
                                                            edit(rule)
                                                        }
                                                    >
                                                        Editar
                                                    </button>
                                                    <button
                                                        className="icon-button"
                                                        aria-label={`Excluir ${rule.name}`}
                                                        onClick={() => {
                                                            if (
                                                                confirm(
                                                                    `Excluir a regra ${rule.name}?`,
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    destroyRule.url(
                                                                        {
                                                                            organization,
                                                                            project,
                                                                            ruleId: rule.id,
                                                                        },
                                                                    ),
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        <TrashIcon size={15} />
                                                    </button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <Empty title="Detecção padrão ativa">
                        Sem regras específicas, eventos de erro usam os limites
                        padrão do servidor. Crie uma regra para ajustar a
                        detecção.
                    </Empty>
                )}
            </Panel>
            {project.permissions?.update && (
                <Panel title={editing ? 'Editar regra' : 'Nova regra'}>
                    <form
                        className="panel-body"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(
                                editing
                                    ? updateRule.url({
                                          organization,
                                          project,
                                          ruleId: editing.id,
                                      })
                                    : storeRule.url([organization, project]),
                                {
                                    onSuccess: () => {
                                        form.reset();
                                        setEditing(null);
                                    },
                                },
                            );
                        }}
                    >
                        <FormErrors errors={form.errors} />
                        <div className="form-grid">
                            <Field label="Nome da regra">
                                <input
                                    required
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    placeholder="Falhas no pagamento"
                                />
                            </Field>
                            <Field
                                label="Tipo de evento"
                                hint="Use o tipo exato ou * para qualquer tipo."
                            >
                                <input
                                    required
                                    value={form.data.event_type}
                                    onChange={(e) =>
                                        form.setData(
                                            'event_type',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="payment.failed"
                                />
                            </Field>
                            <Field label="Quantidade de eventos">
                                <input
                                    type="number"
                                    min={1}
                                    max={100000}
                                    value={form.data.threshold}
                                    onChange={(e) =>
                                        form.setData(
                                            'threshold',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field label="Janela de tempo (segundos)">
                                <input
                                    type="number"
                                    min={1}
                                    max={604800}
                                    value={form.data.window_seconds}
                                    onChange={(e) =>
                                        form.setData(
                                            'window_seconds',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field label="Severidade do incidente">
                                <select
                                    value={form.data.severity}
                                    onChange={(e) =>
                                        form.setData('severity', e.target.value)
                                    }
                                >
                                    <option value="low">Baixa</option>
                                    <option value="medium">Média</option>
                                    <option value="high">Alta</option>
                                    <option value="critical">Crítica</option>
                                </select>
                            </Field>
                            <label className="button-row">
                                <input
                                    type="checkbox"
                                    checked={form.data.enabled}
                                    onChange={(e) =>
                                        form.setData(
                                            'enabled',
                                            e.target.checked,
                                        )
                                    }
                                />
                                Regra ativa
                            </label>
                        </div>
                        <div className="form-actions">
                            {editing && (
                                <button
                                    type="button"
                                    className="button"
                                    onClick={() => {
                                        setEditing(null);
                                        form.reset();
                                    }}
                                >
                                    Cancelar
                                </button>
                            )}
                            <button
                                className="button primary"
                                disabled={form.processing}
                            >
                                {form.processing
                                    ? 'Salvando...'
                                    : 'Salvar regra'}
                            </button>
                        </div>
                    </form>
                </Panel>
            )}
        </>
    );
}
