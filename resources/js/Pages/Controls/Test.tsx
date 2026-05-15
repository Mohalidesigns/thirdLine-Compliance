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
import { InformationCircleIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface Props {
    control: {
        id: number;
        reference: string;
        title: string;
        frequency: string;
        last_tested_at: string | null;
    };
    suggested_sample_size: number | null;
}

type FormData = {
    outcome: string;
    tested_at: string;
    period_start: string;
    period_end: string;
    sample_size: string;
    population_size: string;
    findings: string;
    evidence_url: string;
};

const outcomeOptions = [
    { value: 'passed',         label: 'Passed' },
    { value: 'partial',        label: 'Partial' },
    { value: 'failed',         label: 'Failed' },
    { value: 'not_applicable', label: 'Not Applicable' },
];

function formatDatetimeLocal(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
}

export default function ControlsTest({ control, suggested_sample_size }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        outcome:         '',
        tested_at:       formatDatetimeLocal(),
        period_start:    '',
        period_end:      '',
        sample_size:     suggested_sample_size !== null ? String(suggested_sample_size) : '',
        population_size: '',
        findings:        '',
        evidence_url:    '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('controls.recordTest', control.id));
    };

    const findingsRequired = data.outcome !== '' && data.outcome !== 'passed' && data.outcome !== 'not_applicable';
    const willCreateIssue = data.outcome === 'failed';

    return (
        <AuthenticatedLayout>
            <Head title={`Test Control — ${control.reference}`} />

            <PageHeader
                title={`Test Control — ${control.reference}`}
                subtitle={control.title}
                breadcrumb={[
                    { label: 'Controls', href: route('controls.index') },
                    { label: control.reference, href: route('controls.show', control.id) },
                    { label: 'Test' },
                ]}
            />

            {suggested_sample_size !== null && (
                <div className="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 flex items-start gap-3">
                    <InformationCircleIcon aria-hidden className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm font-semibold text-blue-800">Suggested Sample Size</p>
                        <p className="text-sm text-blue-700 mt-0.5">
                            Based on the {control.frequency} frequency, a sample size of{' '}
                            <strong>{suggested_sample_size}</strong> is recommended for this test.
                        </p>
                    </div>
                </div>
            )}

            {willCreateIssue && (
                <div className="mb-6 rounded-xl border border-orange-200 bg-orange-50 px-5 py-4 flex items-start gap-3">
                    <ExclamationTriangleIcon aria-hidden className="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" />
                    <p className="text-sm text-orange-800">
                        <span className="font-semibold">Note:</span> Submitting a Failed outcome will automatically create an Issue linked to this control.
                    </p>
                </div>
            )}

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Test Results">
                    <div>
                        <InputLabel htmlFor="outcome" value="Outcome" required />
                        <Select
                            id="outcome"
                            value={data.outcome}
                            onChange={(e) => setData('outcome', e.target.value)}
                            hasError={!!errors.outcome}
                            aria-required="true"
                            aria-describedby={errors.outcome ? 'outcome-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select outcome…</option>
                            {outcomeOptions.map((o) => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </Select>
                        <InputError id="outcome-error" message={errors.outcome} />
                    </div>

                    <div>
                        <InputLabel htmlFor="tested_at" value="Test Date & Time" required />
                        <TextInput
                            id="tested_at"
                            type="datetime-local"
                            value={data.tested_at}
                            onChange={(e) => setData('tested_at', e.target.value)}
                            hasError={!!errors.tested_at}
                            aria-required="true"
                            aria-describedby={errors.tested_at ? 'tested_at-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="tested_at-error" message={errors.tested_at} />
                    </div>

                    <div>
                        <InputLabel htmlFor="period_start" value="Period Start" required />
                        <TextInput
                            id="period_start"
                            type="date"
                            value={data.period_start}
                            onChange={(e) => setData('period_start', e.target.value)}
                            hasError={!!errors.period_start}
                            aria-required="true"
                            aria-describedby={errors.period_start ? 'period_start-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="period_start-error" message={errors.period_start} />
                    </div>

                    <div>
                        <InputLabel htmlFor="period_end" value="Period End" required />
                        <TextInput
                            id="period_end"
                            type="date"
                            value={data.period_end}
                            onChange={(e) => setData('period_end', e.target.value)}
                            hasError={!!errors.period_end}
                            aria-required="true"
                            aria-describedby={errors.period_end ? 'period_end-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="period_end-error" message={errors.period_end} />
                    </div>

                    <div>
                        <InputLabel htmlFor="sample_size" value="Sample Size" />
                        <TextInput
                            id="sample_size"
                            type="number"
                            min="1"
                            value={data.sample_size}
                            onChange={(e) => setData('sample_size', e.target.value)}
                            hasError={!!errors.sample_size}
                            aria-describedby={errors.sample_size ? 'sample_size-error' : undefined}
                            placeholder={suggested_sample_size !== null ? String(suggested_sample_size) : 'e.g. 25'}
                            className="mt-1 w-full"
                        />
                        <InputError id="sample_size-error" message={errors.sample_size} />
                    </div>

                    <div>
                        <InputLabel htmlFor="population_size" value="Population Size" />
                        <TextInput
                            id="population_size"
                            type="number"
                            min="1"
                            value={data.population_size}
                            onChange={(e) => setData('population_size', e.target.value)}
                            hasError={!!errors.population_size}
                            aria-describedby={errors.population_size ? 'population_size-error' : undefined}
                            placeholder="Total population tested against"
                            className="mt-1 w-full"
                        />
                        <InputError id="population_size-error" message={errors.population_size} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="findings" value="Findings" required={findingsRequired} />
                        <Textarea
                            id="findings"
                            value={data.findings}
                            onChange={(e) => setData('findings', e.target.value)}
                            hasError={!!errors.findings}
                            aria-required={findingsRequired}
                            aria-describedby={errors.findings ? 'findings-error' : undefined}
                            rows={4}
                            placeholder={findingsRequired ? 'Required for non-passing outcomes — describe what was found…' : 'Optional — describe any observations…'}
                            className="mt-1 w-full"
                        />
                        <InputError id="findings-error" message={errors.findings} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="evidence_url" value="Evidence URL" />
                        <TextInput
                            id="evidence_url"
                            type="url"
                            value={data.evidence_url}
                            onChange={(e) => setData('evidence_url', e.target.value)}
                            hasError={!!errors.evidence_url}
                            aria-describedby={errors.evidence_url ? 'evidence_url-error' : undefined}
                            placeholder="https://drive.example.com/evidence"
                            className="mt-1 w-full"
                        />
                        <InputError id="evidence_url-error" message={errors.evidence_url} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('controls.show', control.id)}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Submit Test Result
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
