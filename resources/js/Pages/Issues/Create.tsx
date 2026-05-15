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

interface SelectOption {
    value: string;
    label: string;
}

interface LinkedItem {
    id: number;
    reference: string;
    title: string;
}

interface Props {
    severities: SelectOption[];
    controls: LinkedItem[];
    obligations: LinkedItem[];
    risks: LinkedItem[];
}

type FormData = {
    title: string;
    description: string;
    severity: string;
    linked_control_id: string;
    linked_obligation_id: string;
    linked_risk_id: string;
    due_at: string;
};

export default function IssuesCreate({ severities, controls, obligations, risks }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        title: '',
        description: '',
        severity: '',
        linked_control_id: '',
        linked_obligation_id: '',
        linked_risk_id: '',
        due_at: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('issues.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Raise Issue" />

            <PageHeader
                title="Raise Issue"
                breadcrumb={[
                    { label: 'Issues', href: route('issues.index') },
                    { label: 'Raise' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Issue Details">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="title" value="Title" required />
                        <TextInput
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            aria-describedby={errors.title ? 'title-error' : undefined}
                            placeholder="e.g. Unresolved reconciliation discrepancy"
                            className="mt-1 w-full"
                        />
                        <InputError id="title-error" message={errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="severity" value="Severity" required />
                        <Select
                            id="severity"
                            value={data.severity}
                            onChange={(e) => setData('severity', e.target.value)}
                            hasError={!!errors.severity}
                            aria-required="true"
                            aria-describedby={errors.severity ? 'severity-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select severity…</option>
                            {severities.map((s) => (
                                <option key={s.value} value={s.value}>{s.label}</option>
                            ))}
                        </Select>
                        <InputError id="severity-error" message={errors.severity} />
                    </div>

                    <div>
                        <InputLabel htmlFor="due_at" value="Due Date" />
                        <TextInput
                            id="due_at"
                            type="date"
                            value={data.due_at}
                            onChange={(e) => setData('due_at', e.target.value)}
                            hasError={!!errors.due_at}
                            aria-describedby={errors.due_at ? 'due_at-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="due_at-error" message={errors.due_at} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="description" value="Description" />
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            hasError={!!errors.description}
                            aria-describedby={errors.description ? 'description-error' : undefined}
                            rows={4}
                            placeholder="Describe the issue, its root cause, and potential impact…"
                            className="mt-1 w-full"
                        />
                        <InputError id="description-error" message={errors.description} />
                    </div>
                </FormSection>

                <FormSection title="Linked Items (optional)">
                    {controls.length > 0 && (
                        <div>
                            <InputLabel htmlFor="linked_control_id" value="Linked Control" />
                            <Select
                                id="linked_control_id"
                                value={data.linked_control_id}
                                onChange={(e) => setData('linked_control_id', e.target.value)}
                                hasError={!!errors.linked_control_id}
                                aria-describedby={errors.linked_control_id ? 'linked_control_id-error' : undefined}
                                className="mt-1 w-full"
                            >
                                <option value="">None</option>
                                {controls.map((c) => (
                                    <option key={c.id} value={String(c.id)}>
                                        {c.reference} — {c.title}
                                    </option>
                                ))}
                            </Select>
                            <InputError id="linked_control_id-error" message={errors.linked_control_id} />
                        </div>
                    )}

                    {obligations.length > 0 && (
                        <div>
                            <InputLabel htmlFor="linked_obligation_id" value="Linked Obligation" />
                            <Select
                                id="linked_obligation_id"
                                value={data.linked_obligation_id}
                                onChange={(e) => setData('linked_obligation_id', e.target.value)}
                                hasError={!!errors.linked_obligation_id}
                                aria-describedby={errors.linked_obligation_id ? 'linked_obligation_id-error' : undefined}
                                className="mt-1 w-full"
                            >
                                <option value="">None</option>
                                {obligations.map((o) => (
                                    <option key={o.id} value={String(o.id)}>
                                        {o.reference} — {o.title}
                                    </option>
                                ))}
                            </Select>
                            <InputError id="linked_obligation_id-error" message={errors.linked_obligation_id} />
                        </div>
                    )}

                    {risks.length > 0 && (
                        <div>
                            <InputLabel htmlFor="linked_risk_id" value="Linked Risk" />
                            <Select
                                id="linked_risk_id"
                                value={data.linked_risk_id}
                                onChange={(e) => setData('linked_risk_id', e.target.value)}
                                hasError={!!errors.linked_risk_id}
                                aria-describedby={errors.linked_risk_id ? 'linked_risk_id-error' : undefined}
                                className="mt-1 w-full"
                            >
                                <option value="">None</option>
                                {risks.map((r) => (
                                    <option key={r.id} value={String(r.id)}>
                                        {r.reference} — {r.title}
                                    </option>
                                ))}
                            </Select>
                            <InputError id="linked_risk_id-error" message={errors.linked_risk_id} />
                        </div>
                    )}
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('issues.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Raise Issue
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
