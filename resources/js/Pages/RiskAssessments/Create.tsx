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

interface Props {
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

export default function RiskAssessmentsCreate({ lobs, methodologies, assessors }: Props) {
    const currentYear = new Date().getFullYear();

    const { data, setData, post, processing, errors } = useForm<FormData>({
        name:              '',
        lob:               '',
        cycle_year:        String(currentYear),
        cycle_quarter:     '',
        methodology:       '3x3',
        summary:           '',
        lead_assessor_id:  '',
        started_at:        '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('risk-assessments.store'));
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
            <Head title="New Assessment Cycle" />

            <PageHeader
                title="New Assessment Cycle"
                breadcrumb={[
                    { label: 'Risk Assessments', href: route('risk-assessments.index') },
                    { label: 'New Cycle' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Cycle Details">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="name" value="Cycle Name" required />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            hasError={!!errors.name}
                            aria-required="true"
                            aria-describedby={errors.name ? 'name-error' : undefined}
                            placeholder="e.g. Annual RCSA 2026 — Retail Banking"
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
                        <fieldset className="mt-1">
                            <legend className="sr-only">Scoring Methodology</legend>
                            <div className="flex gap-4">
                                {(methodologies.length > 0 ? methodologies : [
                                    { value: '3x3' as const, label: '3×3 Matrix' },
                                    { value: '5x5' as const, label: '5×5 Matrix' },
                                ]).map((m) => (
                                    <label key={m.value} className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="methodology"
                                            value={m.value}
                                            checked={data.methodology === m.value}
                                            onChange={() => setData('methodology', m.value as '3x3' | '5x5')}
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
                            aria-describedby={errors.summary ? 'summary-error' : undefined}
                            rows={4}
                            placeholder="Describe the scope and objectives of this assessment cycle…"
                            className="mt-1 w-full"
                        />
                        <InputError id="summary-error" message={errors.summary} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('risk-assessments.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Create Cycle
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
