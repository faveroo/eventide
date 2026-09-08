import { useForm } from '@inertiajs/react';
import { Field, FormErrors } from '@/components/ui';
import { store, update } from '@/routes/project';
import type { Organization, Project } from '@/types/workspace';

export default function ProjectForm({
    organization,
    project,
    onCancel,
}: {
    organization: Organization;
    project?: Project;
    onCancel?: () => void;
}) {
    const form = useForm({
        name: project?.name ?? '',
        description: project?.description ?? '',
        base_url: project?.base_url ?? '',
        check_status_url: project?.check_status_url ?? '',
        active: project?.active ?? true,
        check_interval_seconds: project?.check_interval_seconds ?? 60,
        timeout_seconds: project?.timeout_seconds ?? 10,
        failure_threshold: project?.failure_threshold ?? 3,
        latency_threshold_ms: project?.latency_threshold_ms ?? 2000,
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.post(
                    project
                        ? update.url([organization, project])
                        : store.url(organization),
                );
            }}
        >
            <FormErrors errors={form.errors} />
            <div className="form-grid">
                <Field label="Nome do projeto">
                    <input
                        required
                        name="name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex.: Payments API"
                    />
                </Field>
                <Field label="URL da aplicação">
                    <input
                        type="url"
                        value={form.data.base_url}
                        onChange={(e) =>
                            form.setData('base_url', e.target.value)
                        }
                        placeholder="https://api.empresa.com"
                    />
                </Field>
                <div className="full-width">
                    <Field label="Descrição">
                        <textarea
                            rows={2}
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            placeholder="O que esta aplicação faz?"
                        />
                    </Field>
                </div>
                <div className="full-width">
                    <Field
                        label="Endpoint de saúde"
                        hint="Opcional. Use uma URL pública HTTP ou HTTPS. Endereços privados são bloqueados."
                    >
                        <input
                            type="url"
                            value={form.data.check_status_url}
                            onChange={(e) =>
                                form.setData('check_status_url', e.target.value)
                            }
                            placeholder="https://api.empresa.com/health"
                        />
                    </Field>
                </div>
                <Field label="Intervalo entre verificações (segundos)">
                    <input
                        type="number"
                        min={30}
                        max={86400}
                        value={form.data.check_interval_seconds}
                        onChange={(e) =>
                            form.setData(
                                'check_interval_seconds',
                                Number(e.target.value),
                            )
                        }
                    />
                </Field>
                <Field label="Tempo limite (segundos)">
                    <input
                        type="number"
                        min={1}
                        max={30}
                        value={form.data.timeout_seconds}
                        onChange={(e) =>
                            form.setData(
                                'timeout_seconds',
                                Number(e.target.value),
                            )
                        }
                    />
                </Field>
                <Field label="Falhas consecutivas para abrir incidente">
                    <input
                        type="number"
                        min={1}
                        max={100}
                        value={form.data.failure_threshold}
                        onChange={(e) =>
                            form.setData(
                                'failure_threshold',
                                Number(e.target.value),
                            )
                        }
                    />
                </Field>
                <Field label="Limite de latência (ms)">
                    <input
                        type="number"
                        min={1}
                        max={60000}
                        value={form.data.latency_threshold_ms}
                        onChange={(e) =>
                            form.setData(
                                'latency_threshold_ms',
                                Number(e.target.value),
                            )
                        }
                    />
                </Field>
                <label className="button-row">
                    <input
                        type="checkbox"
                        checked={form.data.active}
                        onChange={(e) =>
                            form.setData('active', e.target.checked)
                        }
                    />
                    Monitoramento ativo
                </label>
            </div>
            <div className="form-actions">
                {onCancel && (
                    <button type="button" className="button" onClick={onCancel}>
                        Cancelar
                    </button>
                )}
                <button className="button primary" disabled={form.processing}>
                    {form.processing
                        ? 'Salvando...'
                        : project
                          ? 'Salvar alterações'
                          : 'Criar projeto'}
                </button>
            </div>
        </form>
    );
}
