import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Confirm identity" />

            <h1 className="text-xl font-bold mb-1" style={{ color: 'var(--color-text-primary)' }}>
                Confirm your identity
            </h1>
            <p className="text-sm mb-6" style={{ color: 'var(--color-text-secondary)' }}>
                This is a secure area. Please confirm your password before continuing.
            </p>

            <form onSubmit={submit} noValidate>
                <div>
                    <InputLabel htmlFor="password" value="Password" required />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        isFocused={true}
                        hasError={!!errors.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing} isLoading={processing}>
                        Confirm
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
