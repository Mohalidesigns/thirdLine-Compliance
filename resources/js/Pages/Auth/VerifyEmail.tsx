import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verify email" />

            <h1 className="text-xl font-bold mb-1" style={{ color: 'var(--color-text-primary)' }}>
                Verify your email
            </h1>
            <p className="text-sm mb-6" style={{ color: 'var(--color-text-secondary)' }}>
                We sent a verification link to your email address. Click the link to activate your account.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    A new verification link has been sent to your email address.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="flex items-center justify-between gap-4">
                    <PrimaryButton disabled={processing} isLoading={processing}>
                        Resend link
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-sm text-gray-600 hover:text-gray-900 underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded"
                    >
                        Sign out
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
