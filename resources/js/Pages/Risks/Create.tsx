import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FormSection from '@/Components/FormSection';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import Textarea from '@/Components/Textarea';
import Select from '@/Components/Select';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';

interface Props {
    cycle: { id: number; reference: string; name: string; methodology: '3x3' | '5x5' };
    categories: { value: string; label: string }[];
}

type FormData = {
    title: string;
    description: string;
    category: string;
    risk_owner: string;
    inherent_likelihood: string;
    inherent_impact: string;
    mitigation_summary: string;
    residual_likelihood: string;
    residual_impact: string;
    accept_basis: string;
};

type RatingVariant = 'low' | 'medium' | 'high' | 'critical' | 'draft';

function computeRating(likelihood: number, impact: number, methodology: '3x3' | '5x5'): { score: number; rating: RatingVariant } {
    const score = likelihood * impact;
    if (methodology === '3x3') {
        if (score <= 2) return { score, rating: 'low' };
        if (score <= 4) return { score, rating: 'medium' };
        if (score <= 6) return { score, rating: 'high' };
        return { score, rating: 'critical' };
    } else {
        if (score <= 6)  return { score, rating: 'low' };
        if (score <= 12) return { score, rating: 'medium' };
        if (score <= 20) return { score, rating: 'high' };
        return { score, rating: 'critical' };
    }
}

function ScorePreview({
    label,
    likelihood,
    impact,
    methodology,
}: {
    label: string;
    likelihood: number;
    impact: number;
    methodology: '3x3' | '5x5';
}) {
    if (!likelihood || !impact) {
        return (
            <div className="rounded-lg border border-gray-100 bg-gray-50 p-3 text-center">
                <p className="text-xs text-gray-400">{label} — not scored</p>
            </div>
        );
    }
    const { score, rating } = computeRating(likelihood, impact, methodology);
    return (
        <div className="rounded-lg border border-gray-100 bg-gray-50 p-3 text-center">
            <p className="text-[10px] uppercase font-semibold text-gray-400 mb-1">{label}</p>
            <p className="text-2xl font-bold mb-1" style={{ color: 'var(--color-text-primary)' }}>{score}</p>
            <StatusBadge variant={rating} label={rating.charAt(0).toUpperCase() + rating.slice(1)} />
        </div>
    );
}

function LikelihoodImpactSelect({
    id,
    value,
    onChange,
    label,
    size,
    error,
}: {
    id: string;
    value: string;
    onChange: (val: string) => void;
    label: string;
    size: number;
    error?: string;
}) {
    return (
        <div>
            <InputLabel htmlFor={id} value={label} />
            <Select
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                hasError={!!error}
                aria-describedby={error ? `${id}-error` : undefined}
                className="mt-1 w-full"
            >
                <option value="">Select…</option>
                {Array.from({ length: size }, (_, i) => i + 1).map((n) => (
                    <option key={n} value={String(n)}>{n}</option>
                ))}
            </Select>
            <InputError id={`${id}-error`} message={error} />
        </div>
    );
}

export default function RisksCreate({ cycle, categories }: Props) {
    const matrixSize = cycle.methodology === '5x5' ? 5 : 3;

    const { data, setData, post, processing, errors } = useForm<FormData>({
        title:                '',
        description:          '',
        category:             '',
        risk_owner:           '',
        inherent_likelihood:  '',
        inherent_impact:      '',
        mitigation_summary:   '',
        residual_likelihood:  '',
        residual_impact:      '',
        accept_basis:         '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('risks.store', { cycle: cycle.id }));
    };

    const inherentL = parseInt(data.inherent_likelihood) || 0;
    const inherentI = parseInt(data.inherent_impact) || 0;
    const residualL = parseInt(data.residual_likelihood) || 0;
    const residualI = parseInt(data.residual_impact) || 0;

    return (
        <AuthenticatedLayout>
            <Head title="Add Risk" />

            <PageHeader
                title="Add Risk"
                breadcrumb={[
                    { label: 'Risk Assessments', href: route('risk-assessments.index') },
                    { label: cycle.reference, href: route('risk-assessments.show', cycle.id) },
                    { label: 'Add Risk' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Risk Details">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="title" value="Risk Title" required />
                        <TextInput
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            aria-describedby={errors.title ? 'title-error' : undefined}
                            placeholder="e.g. Inadequate KYC controls"
                            className="mt-1 w-full"
                        />
                        <InputError id="title-error" message={errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="category" value="Category" required />
                        <Select
                            id="category"
                            value={data.category}
                            onChange={(e) => setData('category', e.target.value)}
                            hasError={!!errors.category}
                            aria-required="true"
                            aria-describedby={errors.category ? 'category-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select category…</option>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>{c.label}</option>
                            ))}
                        </Select>
                        <InputError id="category-error" message={errors.category} />
                    </div>

                    <div>
                        <InputLabel htmlFor="risk_owner" value="Risk Owner" />
                        <TextInput
                            id="risk_owner"
                            value={data.risk_owner}
                            onChange={(e) => setData('risk_owner', e.target.value)}
                            hasError={!!errors.risk_owner}
                            aria-describedby={errors.risk_owner ? 'risk_owner-error' : undefined}
                            placeholder="Team or individual name"
                            className="mt-1 w-full"
                        />
                        <InputError id="risk_owner-error" message={errors.risk_owner} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="description" value="Description" />
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            hasError={!!errors.description}
                            aria-describedby={errors.description ? 'description-error' : undefined}
                            rows={3}
                            placeholder="Describe the risk and its potential impact…"
                            className="mt-1 w-full"
                        />
                        <InputError id="description-error" message={errors.description} />
                    </div>
                </FormSection>

                <FormSection title={`Inherent Scoring (${cycle.methodology})`}>
                    <LikelihoodImpactSelect
                        id="inherent_likelihood"
                        value={data.inherent_likelihood}
                        onChange={(val) => setData('inherent_likelihood', val)}
                        label="Inherent Likelihood"
                        size={matrixSize}
                        error={errors.inherent_likelihood}
                    />

                    <LikelihoodImpactSelect
                        id="inherent_impact"
                        value={data.inherent_impact}
                        onChange={(val) => setData('inherent_impact', val)}
                        label="Inherent Impact"
                        size={matrixSize}
                        error={errors.inherent_impact}
                    />

                    <div className="sm:col-span-2">
                        <ScorePreview
                            label="Inherent Score"
                            likelihood={inherentL}
                            impact={inherentI}
                            methodology={cycle.methodology}
                        />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="mitigation_summary" value="Mitigation Summary" />
                        <Textarea
                            id="mitigation_summary"
                            value={data.mitigation_summary}
                            onChange={(e) => setData('mitigation_summary', e.target.value)}
                            hasError={!!errors.mitigation_summary}
                            aria-describedby={errors.mitigation_summary ? 'mitigation_summary-error' : undefined}
                            rows={3}
                            placeholder="Describe existing controls and mitigations…"
                            className="mt-1 w-full"
                        />
                        <InputError id="mitigation_summary-error" message={errors.mitigation_summary} />
                    </div>
                </FormSection>

                <FormSection title={`Residual Scoring (${cycle.methodology})`}>
                    <LikelihoodImpactSelect
                        id="residual_likelihood"
                        value={data.residual_likelihood}
                        onChange={(val) => setData('residual_likelihood', val)}
                        label="Residual Likelihood"
                        size={matrixSize}
                        error={errors.residual_likelihood}
                    />

                    <LikelihoodImpactSelect
                        id="residual_impact"
                        value={data.residual_impact}
                        onChange={(val) => setData('residual_impact', val)}
                        label="Residual Impact"
                        size={matrixSize}
                        error={errors.residual_impact}
                    />

                    <div className="sm:col-span-2">
                        <ScorePreview
                            label="Residual Score"
                            likelihood={residualL}
                            impact={residualI}
                            methodology={cycle.methodology}
                        />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="accept_basis" value="Acceptance Basis" />
                        <Textarea
                            id="accept_basis"
                            value={data.accept_basis}
                            onChange={(e) => setData('accept_basis', e.target.value)}
                            hasError={!!errors.accept_basis}
                            aria-describedby={errors.accept_basis ? 'accept_basis-error' : undefined}
                            rows={2}
                            placeholder="If risk is accepted, state the basis…"
                            className="mt-1 w-full"
                        />
                        <InputError id="accept_basis-error" message={errors.accept_basis} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('risk-assessments.show', cycle.id)}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Save Risk
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
