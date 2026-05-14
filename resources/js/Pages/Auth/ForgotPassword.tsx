import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Reset password" />

            <h1 className="text-xl font-bold mb-1" style={{ color: 'var(--color-text-primary)' }}>
                Reset password
            </h1>
            <p className="text-sm mb-6" style={{ color: 'var(--color-text-secondary)' }}>
                Enter your email and we will send you a reset link.
            </p>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">{status}</div>
            )}

            <form onSubmit={submit} noValidate>
                <div>
                    <InputLabel htmlFor="email" value="Email" required />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        isFocused={true}
                        hasError={!!errors.email}
                        aria-required="true"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing} isLoading={processing}>
                        Send reset link
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
