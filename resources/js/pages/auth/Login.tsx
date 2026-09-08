import { Link, useForm } from '@inertiajs/react';
import { ArrowRightIcon } from '@phosphor-icons/react';
import { auth } from '@/actions/App/Http/Controllers/Auth/LoginController';
import { create } from '@/actions/App/Http/Controllers/Auth/RegisterController';
import { Field, FormErrors } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout';

export default function Login() {
    const form = useForm({ email: '', password: '' });

    return (
        <AuthLayout title="Entrar">
            <h2>Bom ter você de volta.</h2>
            <p>Entre para acompanhar suas aplicações.</p>
            <FormErrors errors={form.errors} />
            <form
                className="form-stack"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(auth.url(), {
                        onError: () => form.reset('password'),
                    });
                }}
            >
                <Field label="E-mail">
                    <input
                        type="email"
                        autoComplete="username"
                        name="email"
                        required
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        placeholder="voce@empresa.com"
                    />
                </Field>
                <Field label="Senha">
                    <input
                        type="password"
                        autoComplete="current-password"
                        name="password"
                        required
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                        placeholder="Sua senha"
                    />
                </Field>
                <button className="button primary" disabled={form.processing}>
                    {form.processing ? 'Entrando...' : 'Entrar'}
                    <ArrowRightIcon size={17} />
                </button>
            </form>
            <div className="auth-switch">
                Ainda não tem uma conta?
                <Link href={create.url()}>Criar conta</Link>
            </div>
        </AuthLayout>
    );
}
