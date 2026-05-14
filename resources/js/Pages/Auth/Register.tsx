import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Create account" />

            <h1 className="text-xl font-bold mb-1" style={{ color: 'var(--color-text-primary)' }}>
                Create account
            </h1>
            <p className="text-sm text-gray-600 mb-6">
                Set up your Atheris compliance account
            </p>

            <form onSubmit={submit} noValidate>
                <div>
                    <InputLabel htmlFor="name" value="Full name" required />
                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        hasError={!!errors.name}
                        aria-required="true"
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="email" value="Email" required />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="email"
                        hasError={!!errors.email}
                        aria-required="true"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" required />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        hasError={!!errors.password}
                        aria-required="true"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirm password" required />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        hasError={!!errors.password_confirmation}
                        aria-required="true"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div className="mt-6 flex items-center justify-end gap-4">
                    <Link
                        href={route('login')}
                        className="text-sm text-gray-600 hover:text-gray-900 underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded"
                    >
                        Already registered?
                    </Link>
                    <PrimaryButton disabled={processing} isLoading={processing}>
                        Create account
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
