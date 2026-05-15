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

interface Cycle {
    id: number;
    reference: string;
    name: string;
    lob: string;
    state: string;
    state_label: string;
    state_color: string;
    cycle_year: number;
    cycle_quarter: number | null;
    methodology: '3x3' | '5x5';
    sla_due_date: string | null;
    sla_days_remaining: number | null;
    risks_count: number;
    high_risk_count: number;
    lead_assessor_name: string | null;
    updated_at: string;
    summary: string | null;
    started_at: string | null;
    closed_at: string | null;
    workshop_notes: Array<{
        id: number;
        recorded_at: string;
        recorded_by_name: string;
        attendees: string[];
        notes: string;
    }>;
}

interface Props {
    cycle: Cycle;
    lobs: { value: string; label: string }[];
    methodologies: { value: '3x3' | '5x5'; label: string }[];
    assessors: { value: number; label: string }[];
}

type FormData = {
    name: string;
    lob: string;
    cycle_year: string;
    cycle_quarter: string;
    methodology: '3x3' | '5x5';
    summary: string;
    lead_assessor_id: string;
    started_at: string;
};

export default function RiskAssessmentsEdit({ cycle, lobs, methodologies, assessors }: Props) {
    const isLocked = cycle.state !== 'planning';
    const currentYear = new Date().getFullYear();

    const { data, setData, put, processing, errors } = useForm<FormData>({
        name:              cycle.name,
        lob:               cycle.lob,
        cycle_year:        String(cycle.cycle_year),
        cycle_quarter:     cycle.cycle_quarter !== null ? String(cycle.cycle_quarter) : '',
        methodology:       cycle.methodology,
        summary:           cycle.summary ?? '',
        lead_assessor_id:  '',
        started_at:        cycle.started_at?.substring(0, 10) ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isLocked) return;
        put(route('risk-assessments.update', cycle.id));
    };

    const yearOptions = Array.from({ length: 5 }, (_, i) => {
        const y = currentYear - 1 + i;
        return { value: String(y), label: String(y) };
    });

    const quarterOptions = [
        { value: '',  label: 'N/A (Annual)' },
        { value: '1', label: 'Q1' },
        { value: '2', label: 'Q2' },
        { value: '3', label: 'Q3' },
        { value: '4', label: 'Q4' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Edit — ${cycle.reference}`} />

            <PageHeader
                title={`Edit — ${cycle.reference}`}
                breadcrumb={[
                    { label: 'Risk Assessments', href: route('risk-assessments.index') },
                    { label: cycle.reference, href: route('risk-assessments.show', cycle.id) },
                    { label: 'Edit' },
                ]}
            />

            {isLocked && (
                <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                    This cycle is in state <strong>{cycle.state_label}</strong> and cannot be edited. Only cycles in the
                    Planning state may be modified.
                </div>
            )}

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Cycle Details">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="name" value="Cycle Name" required />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            hasError={!!errors.name}
                            disabled={isLocked}
                            aria-required="true"
                            aria-describedby={errors.name ? 'name-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="name-error" message={errors.name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="lob" value="Line of Business" required />
                        <Select
                            id="lob"
                            value={data.lob}
                            onChange={(e) => setData('lob', e.target.value)}
                            hasError={!!errors.lob}
                            disabled={isLocked}
                            aria-required="true"
                            aria-describedby={errors.lob ? 'lob-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select LOB…</option>
                            {lobs.map((l) => (
                                <option key={l.value} value={l.value}>{l.label}</option>
                            ))}
                        </Select>
                        <InputError id="lob-error" message={errors.lob} />
                    </div>

                    <div>
                        <InputLabel htmlFor="cycle_year" value="Cycle Year" required />
                        <Select
                            id="cycle_year"
                            value={data.cycle_year}
                            onChange={(e) => setData('cycle_year', e.target.value)}
                            hasError={!!errors.cycle_year}
                            disabled={isLocked}
                            aria-required="true"
                            aria-describedby={errors.cycle_year ? 'cycle_year-error' : undefined}
                            className="mt-1 w-full"
                        >
                            {yearOptions.map((y) => (
                                <option key={y.value} value={y.value}>{y.label}</option>
                            ))}
                        </Select>
                        <InputError id="cycle_year-error" message={errors.cycle_year} />
                    </div>

                    <div>
                        <InputLabel htmlFor="cycle_quarter" value="Quarter" />
                        <Select
                            id="cycle_quarter"
                            value={data.cycle_quarter}
                            onChange={(e) => setData('cycle_quarter', e.target.value)}
                            hasError={!!errors.cycle_quarter}
                            disabled={isLocked}
                            aria-describedby={errors.cycle_quarter ? 'cycle_quarter-error' : undefined}
                            className="mt-1 w-full"
                        >
                            {quarterOptions.map((q) => (
                                <option key={q.value} value={q.value}>{q.label}</option>
                            ))}
                        </Select>
                        <InputError id="cycle_quarter-error" message={errors.cycle_quarter} />
                    </div>

                    <div>
                        <InputLabel htmlFor="methodology" value="Scoring Methodology" required />
                        <fieldset className="mt-1" disabled={isLocked}>
                            <legend className="sr-only">Scoring Methodology</legend>
                            <div className="flex gap-4">
                                {(methodologies.length > 0 ? methodologies : [
                                    { value: '3x3' as const, label: '3×3 Matrix' },
                                    { value: '5x5' as const, label: '5×5 Matrix' },
                                ]).map((m) => (
                                    <label key={m.value} className={`flex items-center gap-2 ${isLocked ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}`}>
                                        <input
                                            type="radio"
                                            name="methodology"
                                            value={m.value}
                                            checked={data.methodology === m.value}
                                            onChange={() => !isLocked && setData('methodology', m.value as '3x3' | '5x5')}
                                            disabled={isLocked}
                                            className="rounded-full border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span className="text-sm font-medium text-gray-700">{m.label}</span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                        <InputError id="methodology-error" message={errors.methodology} />
                    </div>

                    <div>
                        <InputLabel htmlFor="lead_assessor_id" value="Lead Assessor" />
                        <Select
                            id="lead_assessor_id"
                            value={data.lead_assessor_id}
                            onChange={(e) => setData('lead_assessor_id', e.target.value)}
                            hasError={!!errors.lead_assessor_id}
                            disabled={isLocked}
                            aria-describedby={errors.lead_assessor_id ? 'lead_assessor_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select assessor…</option>
                            {assessors.map((a) => (
                                <option key={a.value} value={a.value}>{a.label}</option>
                            ))}
                        </Select>
                        <InputError id="lead_assessor_id-error" message={errors.lead_assessor_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="started_at" value="Start Date" />
                        <TextInput
                            id="started_at"
                            type="date"
                            value={data.started_at}
                            onChange={(e) => setData('started_at', e.target.value)}
                            hasError={!!errors.started_at}
                            disabled={isLocked}
                            aria-describedby={errors.started_at ? 'started_at-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="started_at-error" message={errors.started_at} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="summary" value="Summary / Scope" />
                        <Textarea
                            id="summary"
                            value={data.summary}
                            onChange={(e) => setData('summary', e.target.value)}
                            hasError={!!errors.summary}
                            disabled={isLocked}
                            aria-describedby={errors.summary ? 'summary-error' : undefined}
                            rows={4}
                            className="mt-1 w-full"
                        />
                        <InputError id="summary-error" message={errors.summary} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('risk-assessments.show', cycle.id)}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing} disabled={isLocked}>
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
