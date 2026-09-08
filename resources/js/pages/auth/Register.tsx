import { Link, useForm } from '@inertiajs/react';
import { ArrowRightIcon } from '@phosphor-icons/react';
import { Field, FormErrors } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout';
import { register, login } from '@/routes';

export default function Register() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <AuthLayout title="Criar conta">
            <h2>
                Seu próximo incidente,
                <br />
                com mais contexto.
            </h2>
            <p>Crie sua conta e conecte o primeiro projeto.</p>
            <FormErrors errors={form.errors} />
            <form
                className="form-stack"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(register.url(), {
                        onError: () =>
                            form.reset('password', 'password_confirmation'),
                    });
                }}
            >
                <Field label="Nome">
                    <input
                        name="name"
                        autoComplete="name"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Como podemos chamar você?"
                    />
                </Field>
                <Field label="E-mail">
                    <input
                        type="email"
                        name="email"
                        autoComplete="username"
                        required
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        placeholder="voce@empresa.com"
                    />
                </Field>
                <Field
                    label="Senha"
                    hint="Use pelo menos 8 caracteres. Prefira uma senha longa e exclusiva."
                >
                    <input
                        type="password"
                        name="password"
                        autoComplete="new-password"
                        minLength={8}
                        required
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                    />
                </Field>
                <Field label="Confirmar senha">
                    <input
                        type="password"
                        name="password_confirmation"
                        autoComplete="new-password"
                        required
                        value={form.data.password_confirmation}
                        onChange={(e) =>
                            form.setData(
                                'password_confirmation',
                                e.target.value,
                            )
                        }
                    />
                </Field>
                <button className="button primary" disabled={form.processing}>
                    {form.processing ? 'Criando conta...' : 'Criar conta'}
                    <ArrowRightIcon size={17} />
                </button>
            </form>
            <div className="auth-switch">
                Já tem uma conta?<Link href={login.url()}>Entrar</Link>
            </div>
        </AuthLayout>
    );
}
