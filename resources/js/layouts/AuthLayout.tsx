import { Head, Link } from '@inertiajs/react';
import {
    ActivityIcon,
    BroadcastIcon,
    GitBranchIcon,
    ShieldCheckIcon,
} from '@phosphor-icons/react';
import type { ReactNode } from 'react';
import { login } from '@/routes';

export default function AuthLayout({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="auth-page">
            <Head title={title} />
            <aside className="auth-story">
                <Link href={login.url()} className="brand">
                    <span className="brand-mark">
                        <ActivityIcon size={24} weight="bold" />
                    </span>
                    eventide<span className="brand-period">.</span>
                </Link>
                <div className="auth-story-content">
                    <h1>Todo sinal tem um contexto.</h1>
                    <p>
                        Da primeira falha à resolução, acompanhe o que acontece
                        nas suas aplicações.
                    </p>
                    <div className="auth-principles">
                        <div>
                            <BroadcastIcon size={25} weight="light" />
                            <section>
                                <strong>Detecte a mudança</strong>
                                <small>
                                    Verificações de saúde e eventos da
                                    aplicação.
                                </small>
                            </section>
                        </div>
                        <div>
                            <GitBranchIcon size={25} weight="light" />
                            <section>
                                <strong>Conecte os acontecimentos</strong>
                                <small>
                                    Erros e deployments na mesma investigação.
                                </small>
                            </section>
                        </div>
                        <div>
                            <ShieldCheckIcon size={25} weight="light" />
                            <section>
                                <strong>Responda com contexto</strong>
                                <small>
                                    Incidentes compartilhados com sua equipe.
                                </small>
                            </section>
                        </div>
                    </div>
                </div>
                <footer>Detect. Correlate. Respond.</footer>
            </aside>
            <div className="auth-content">
                <div className="auth-form">{children}</div>
            </div>
        </div>
    );
}
