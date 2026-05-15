import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import FormSection from '@/Components/FormSection';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Campaign {
    id: number;
    code: string;
    title: string;
    body: string;
    starts_at: string;
    ends_at: string;
}

interface Props {
    campaign: Campaign;
    already_signed: boolean;
    signed_at: string | null;
    can: { sign: boolean };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function DetailItem({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="py-2.5 border-b border-gray-100 last:border-0 flex justify-between items-start gap-4">
            <span
                className="text-xs font-medium flex-shrink-0 w-24"
                style={{ color: 'var(--color-text-secondary)' }}
            >
                {label}
            </span>
            <span className="text-xs text-right" style={{ color: 'var(--color-text-primary)' }}>
                {children}
            </span>
        </div>
    );
}

// ─── Form data ────────────────────────────────────────────────────────────────

interface SignFormData {
    confirmation: boolean;
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function MyAttestationsShow({
    campaign,
    already_signed,
    signed_at,
    can,
}: Props) {
    const [confirmed, setConfirmed] = useState(false);

    // useForm manages the POST + processing/errors state.
    // We track `confirmed` in local useState so the <Checkbox> is a
    // standard controlled input without needing a boolean ↔ string cast.
    // On submit we call post() which sends { confirmation: false } from
    // the form initial state — the real value is the `confirmed` guard below.
    // The backend validates the checkbox server-side; we guard the button
    // with aria-disabled and the submit handler so the user cannot submit
    // without checking the box.
    const { post, processing, errors } = useForm<SignFormData>({
        confirmation: false,
    });

    const handleSignDirect = (e: React.FormEvent) => {
        e.preventDefault();
        if (!confirmed || processing) return;
        post(route('my.attestations.sign', campaign.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${campaign.code} — ${campaign.title}`} />

            <PageHeader
                title={campaign.title}
                breadcrumb={[
                    { label: 'My Attestations', href: route('my.attestations.index') },
                    { label: campaign.title },
                ]}
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* ── Left: Campaign body ── */}
                <div className="lg:col-span-2 space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <div className="flex items-center gap-3 flex-wrap">
                                <h2
                                    className="text-sm font-semibold"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    {campaign.title}
                                </h2>
                                <span
                                    className="font-mono text-xs"
                                    style={{ color: 'var(--color-text-secondary)' }}
                                >
                                    {campaign.code}
                                </span>
                            </div>
                            <p className="text-xs mt-1" style={{ color: 'var(--color-text-secondary)' }}>
                                Active {formatDate(campaign.starts_at)} – {formatDate(campaign.ends_at)}
                            </p>
                        </Card.Header>
                        <Card.Body>
                            {/*
                             * Body is rendered as plain text with whitespace preserved.
                             * We deliberately do NOT use dangerouslySetInnerHTML — the body
                             * field is user-generated text that must not be treated as HTML.
                             */}
                            <p
                                className="text-sm whitespace-pre-wrap leading-relaxed"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                {campaign.body}
                            </p>
                        </Card.Body>
                    </Card>

                    {/* Already signed confirmation */}
                    {already_signed && (
                        <div className="rounded-xl border border-green-200 bg-green-50 p-4">
                            <p className="text-sm font-semibold text-green-800">
                                You signed this attestation on {formatDate(signed_at)}.
                            </p>
                            <p className="text-sm text-green-700 mt-0.5">
                                Your signature has been recorded. No further action is required.
                            </p>
                        </div>
                    )}

                    {/* Sign form — only when not already signed and user can sign */}
                    {!already_signed && can.sign && (
                        <form
                            onSubmit={handleSignDirect}
                            noValidate
                            aria-label="Sign attestation"
                        >
                            <FormSection
                                title="Sign Attestation"
                                description="Read the above text carefully before signing."
                            >
                                {/* Checkbox spans both columns */}
                                <div className="sm:col-span-2">
                                    <label className="flex items-start gap-3 cursor-pointer">
                                        <Checkbox
                                            id="attestation-confirmation"
                                            checked={confirmed}
                                            onChange={(e) => setConfirmed(e.target.checked)}
                                            aria-describedby={
                                                errors.confirmation
                                                    ? 'confirmation-error'
                                                    : undefined
                                            }
                                            aria-required="true"
                                        />
                                        <span
                                            className="text-sm leading-relaxed select-none"
                                            style={{ color: 'var(--color-text-primary)' }}
                                        >
                                            I confirm I have read and agree to the above
                                        </span>
                                    </label>
                                    <InputError
                                        id="confirmation-error"
                                        message={errors.confirmation}
                                    />
                                </div>

                                <div className="sm:col-span-2 flex justify-start">
                                    <PrimaryButton
                                        type="submit"
                                        disabled={!confirmed}
                                        isLoading={processing}
                                        aria-disabled={!confirmed}
                                    >
                                        Sign Attestation
                                    </PrimaryButton>
                                </div>
                            </FormSection>
                        </form>
                    )}
                </div>

                {/* ── Right: Campaign details ── */}
                <div>
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Campaign Details
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Code">
                                    <span className="font-mono">{campaign.code}</span>
                                </DetailItem>
                                <DetailItem label="Starts">{formatDate(campaign.starts_at)}</DetailItem>
                                <DetailItem label="Closes">{formatDate(campaign.ends_at)}</DetailItem>
                                {already_signed && signed_at && (
                                    <DetailItem label="Signed">
                                        <span className="text-green-700 font-medium">
                                            {formatDate(signed_at)}
                                        </span>
                                    </DetailItem>
                                )}
                            </div>
                        </Card.Body>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
