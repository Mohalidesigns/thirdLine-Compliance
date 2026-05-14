import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Sign in" />

            <h1 className="text-xl font-bold mb-1" style={{ color: 'var(--color-text-primary)' }}>
                Sign in
            </h1>
            <p className="text-sm text-gray-600 mb-6">
                Enter your credentials to continue
            </p>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">{status}</div>
            )}

            <form onSubmit={submit} noValidate>
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="email"
                        isFocused={true}
                        hasError={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError id="email-error" message={errors.email} />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        hasError={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError id="password-error" message={errors.password} />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center gap-2">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', (e.target.checked || false) as false)
                            }
                        />
                        <span className="text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <div className="mt-6 flex items-center justify-end gap-4">
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm text-gray-600 hover:text-gray-900 underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded"
                        >
                            Forgot your password?
                        </Link>
                    )}
                    <PrimaryButton disabled={processing} isLoading={processing}>
                        Sign in
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
