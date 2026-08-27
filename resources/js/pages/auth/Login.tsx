import { useForm } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { auth } from '@/actions/App/Http/Controllers/Auth/LoginController';
import { create } from '@/actions/App/Http/Controllers/Auth/RegisterController';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    function submit(e: React.SubmitEvent<HTMLFormElement>) {
        e.preventDefault();
        post(auth.url(), {
            onError: () => reset('password'),
        });
    }

    return (
        <div className="flex min-h-screen flex-col items-center justify-center gap-3">
            <div>
                <span className="text-5xl font-bold"> Login </span>
            </div>
            <div className="w-sm">
                <form onSubmit={submit}>
                    <div className="flex flex-col gap-3">
                        <label htmlFor="email">Email</label>
                        <input
                            className="border p-1"
                            type="email"
                            id="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        {errors.email && <div>{errors.email}</div>}

                        <label htmlFor="password">Password</label>
                        <input
                            className="border p-1"
                            type="password"
                            id="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                        {errors.password && <div>{errors.password}</div>}
                    </div>

                    <div className="mt-2">
                        <button
                            className="w-full rounded bg-sky-500 p-3"
                            type="submit"
                            disabled={processing}
                        >
                            Login
                        </button>
                    </div>
                </form>
                <div className="my-2">
                    dont have account?
                    <Link href={create.url()}>register here</Link>
                </div>
            </div>
        </div>
    );
}
