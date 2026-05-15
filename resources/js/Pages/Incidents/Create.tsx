import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FormSection from '@/Components/FormSection';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import Textarea from '@/Components/Textarea';
import Select from '@/Components/Select';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/react/24/outline';

// ---- types ----

interface SelectOption<V = string> {
    value: V;
    label: string;
}

interface Props {
    category_options: SelectOption[];
    severity_options: SelectOption[];
    basel_options: SelectOption[];
    risks_options: SelectOption<number>[];
    controls_options: SelectOption<number>[];
    policies_options: SelectOption<number>[];
    users_options: SelectOption<number>[];
}

type FormData = {
    title: string;
    description: string;
    category: string;
    severity: string;
    occurred_at: string;
    detected_at: string;
    is_cyber_incident: boolean;
    is_data_breach: boolean;
    affects_customers: boolean;
    affected_customer_count: string;
    basel_category: string;
    financial_impact: string;
    currency: string;
    linked_risk_id: string;
    linked_control_id: string;
    linked_policy_id: string;
    assigned_to: string;
    reporter_external_source: string;
};

function nowDatetimeLocal(): string {
    const now = new Date();
    // Format: YYYY-MM-DDTHH:MM (no seconds — datetime-local input format)
    const pad = (n: number) => String(n).padStart(2, '0');
    return (
        `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}` +
        `T${pad(now.getHours())}:${pad(now.getMinutes())}`
    );
}

// ---- collapsible linkages section ----

function CollapsibleSection({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <div className="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="flex w-full items-center justify-between px-6 py-4 text-left focus:outline-none focus:ring-2 focus:ring-primary focus:ring-inset rounded-xl"
                aria-expanded={open}
            >
                <span className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                    {title}
                </span>
                {open ? (
                    <ChevronUpIcon aria-hidden className="w-4 h-4 text-gray-400" />
                ) : (
                    <ChevronDownIcon aria-hidden className="w-4 h-4 text-gray-400" />
                )}
            </button>
            {open && (
                <div className="px-6 pb-6 border-t border-gray-100">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 pt-4">
                        {children}
                    </div>
                </div>
            )}
        </div>
    );
}

// ---- main component ----

export default function IncidentsCreate({
    category_options,
    severity_options,
    basel_options,
    risks_options,
    controls_options,
    policies_options,
    users_options,
}: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        title: '',
        description: '',
        category: '',
        severity: '',
        occurred_at: '',
        detected_at: nowDatetimeLocal(),
        is_cyber_incident: false,
        is_data_breach: false,
        affects_customers: false,
        affected_customer_count: '',
        basel_category: '',
        financial_impact: '',
        currency: 'NGN',
        linked_risk_id: '',
        linked_control_id: '',
        linked_policy_id: '',
        assigned_to: '',
        reporter_external_source: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('incidents.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Report Incident" />

            <PageHeader
                title="Report Incident"
                breadcrumb={[
                    { label: 'Incidents', href: route('incidents.index') },
                    { label: 'Report' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                {/* Section 1: Basics */}
                <FormSection
                    title="Basics"
                    description="Core incident details."
                >
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="title" value="Title" required />
                        <TextInput
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            aria-describedby={errors.title ? 'title-error' : undefined}
                            placeholder="e.g. Unauthorised system access detected"
                            className="mt-1 w-full"
                        />
                        <InputError id="title-error" message={errors.title} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="description" value="Description" />
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            hasError={!!errors.description}
                            aria-describedby={errors.description ? 'description-error' : undefined}
                            rows={5}
                            placeholder="Describe what happened, who was affected, and the immediate impact…"
                            className="mt-1 w-full"
                        />
                        <InputError id="description-error" message={errors.description} />
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
                            {category_options.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="category-error" message={errors.category} />
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
                            {severity_options.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="severity-error" message={errors.severity} />
                    </div>

                    <div>
                        <InputLabel htmlFor="detected_at" value="Detected At" required />
                        <TextInput
                            id="detected_at"
                            type="datetime-local"
                            value={data.detected_at}
                            onChange={(e) => setData('detected_at', e.target.value)}
                            hasError={!!errors.detected_at}
                            aria-required="true"
                            aria-describedby={errors.detected_at ? 'detected_at-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="detected_at-error" message={errors.detected_at} />
                    </div>

                    <div>
                        <InputLabel htmlFor="occurred_at" value="Occurred At (optional)" />
                        <TextInput
                            id="occurred_at"
                            type="datetime-local"
                            value={data.occurred_at}
                            onChange={(e) => setData('occurred_at', e.target.value)}
                            hasError={!!errors.occurred_at}
                            aria-describedby={errors.occurred_at ? 'occurred_at-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="occurred_at-error" message={errors.occurred_at} />
                    </div>
                </FormSection>

                {/* Section 2: Classification flags */}
                <FormSection
                    title="Classification"
                    description="Regulatory classification flags. These determine which notification obligations are triggered."
                >
                    <div className="sm:col-span-2 flex flex-col gap-4">
                        {/* Cyber incident */}
                        <div>
                            <label className="flex items-start gap-3 cursor-pointer">
                                <Checkbox
                                    id="is_cyber_incident"
                                    checked={data.is_cyber_incident}
                                    onChange={(e) => setData('is_cyber_incident', e.target.checked)}
                                    aria-describedby="is_cyber_incident-help"
                                />
                                <div>
                                    <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                        Cyber Incident
                                    </span>
                                    <p
                                        id="is_cyber_incident-help"
                                        className="text-xs mt-0.5"
                                        style={{ color: 'var(--color-text-secondary)' }}
                                    >
                                        Triggers CBN cyber incident 4 h / 24 h regulatory notifications.
                                    </p>
                                </div>
                            </label>
                            <InputError message={errors.is_cyber_incident} />
                        </div>

                        {/* Data breach */}
                        <div>
                            <label className="flex items-start gap-3 cursor-pointer">
                                <Checkbox
                                    id="is_data_breach"
                                    checked={data.is_data_breach}
                                    onChange={(e) => setData('is_data_breach', e.target.checked)}
                                    aria-describedby="is_data_breach-help"
                                />
                                <div>
                                    <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                        Data Breach
                                    </span>
                                    <p
                                        id="is_data_breach-help"
                                        className="text-xs mt-0.5"
                                        style={{ color: 'var(--color-text-secondary)' }}
                                    >
                                        Triggers NDPC 72 h data breach notification requirement.
                                    </p>
                                </div>
                            </label>
                            <InputError message={errors.is_data_breach} />
                        </div>

                        {/* Affects customers */}
                        <div>
                            <label className="flex items-start gap-3 cursor-pointer">
                                <Checkbox
                                    id="affects_customers"
                                    checked={data.affects_customers}
                                    onChange={(e) => setData('affects_customers', e.target.checked)}
                                />
                                <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                    Affects Customers
                                </span>
                            </label>
                            <InputError message={errors.affects_customers} />
                        </div>

                        {/* Conditional customer count */}
                        {data.affects_customers && (
                            <div className="ml-7">
                                <InputLabel
                                    htmlFor="affected_customer_count"
                                    value="Affected Customer Count"
                                />
                                <TextInput
                                    id="affected_customer_count"
                                    type="number"
                                    min="0"
                                    value={data.affected_customer_count}
                                    onChange={(e) =>
                                        setData('affected_customer_count', e.target.value)
                                    }
                                    hasError={!!errors.affected_customer_count}
                                    aria-describedby={
                                        errors.affected_customer_count
                                            ? 'affected_customer_count-error'
                                            : undefined
                                    }
                                    placeholder="0"
                                    className="mt-1 w-48"
                                />
                                <InputError
                                    id="affected_customer_count-error"
                                    message={errors.affected_customer_count}
                                />
                            </div>
                        )}
                    </div>
                </FormSection>

                {/* Section 3: Impact */}
                <FormSection
                    title="Impact"
                    description="Financial impact and Basel II operational risk categorisation."
                >
                    <div>
                        <InputLabel htmlFor="basel_category" value="Basel Category" />
                        <Select
                            id="basel_category"
                            value={data.basel_category}
                            onChange={(e) => setData('basel_category', e.target.value)}
                            hasError={!!errors.basel_category}
                            aria-describedby={errors.basel_category ? 'basel_category-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select Basel category…</option>
                            {basel_options.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="basel_category-error" message={errors.basel_category} />
                    </div>

                    <div>
                        <InputLabel htmlFor="currency" value="Currency" />
                        <TextInput
                            id="currency"
                            value={data.currency}
                            onChange={(e) => setData('currency', e.target.value.slice(0, 3).toUpperCase())}
                            hasError={!!errors.currency}
                            aria-describedby={errors.currency ? 'currency-error' : undefined}
                            maxLength={3}
                            placeholder="NGN"
                            className="mt-1 w-full"
                        />
                        <InputError id="currency-error" message={errors.currency} />
                    </div>

                    <div>
                        <InputLabel htmlFor="financial_impact" value="Financial Impact (optional)" />
                        <TextInput
                            id="financial_impact"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.financial_impact}
                            onChange={(e) => setData('financial_impact', e.target.value)}
                            hasError={!!errors.financial_impact}
                            aria-describedby={
                                errors.financial_impact ? 'financial_impact-error' : undefined
                            }
                            placeholder="0.00"
                            className="mt-1 w-full"
                        />
                        <InputError id="financial_impact-error" message={errors.financial_impact} />
                    </div>
                </FormSection>

                {/* Section 4: Linkages — collapsible */}
                <CollapsibleSection title="Linkages (optional)">
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
                            {risks_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="linked_risk_id-error" message={errors.linked_risk_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="linked_control_id" value="Linked Control" />
                        <Select
                            id="linked_control_id"
                            value={data.linked_control_id}
                            onChange={(e) => setData('linked_control_id', e.target.value)}
                            hasError={!!errors.linked_control_id}
                            aria-describedby={
                                errors.linked_control_id ? 'linked_control_id-error' : undefined
                            }
                            className="mt-1 w-full"
                        >
                            <option value="">None</option>
                            {controls_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="linked_control_id-error" message={errors.linked_control_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="linked_policy_id" value="Linked Policy" />
                        <Select
                            id="linked_policy_id"
                            value={data.linked_policy_id}
                            onChange={(e) => setData('linked_policy_id', e.target.value)}
                            hasError={!!errors.linked_policy_id}
                            aria-describedby={
                                errors.linked_policy_id ? 'linked_policy_id-error' : undefined
                            }
                            className="mt-1 w-full"
                        >
                            <option value="">None</option>
                            {policies_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="linked_policy_id-error" message={errors.linked_policy_id} />
                    </div>
                </CollapsibleSection>

                {/* Section 5: Assignment */}
                <FormSection title="Assignment">
                    <div>
                        <InputLabel htmlFor="assigned_to" value="Assigned To" />
                        <Select
                            id="assigned_to"
                            value={data.assigned_to}
                            onChange={(e) => setData('assigned_to', e.target.value)}
                            hasError={!!errors.assigned_to}
                            aria-describedby={errors.assigned_to ? 'assigned_to-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Unassigned</option>
                            {users_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError id="assigned_to-error" message={errors.assigned_to} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="reporter_external_source"
                            value="External Reporter / Source (optional)"
                        />
                        <TextInput
                            id="reporter_external_source"
                            value={data.reporter_external_source}
                            onChange={(e) => setData('reporter_external_source', e.target.value)}
                            hasError={!!errors.reporter_external_source}
                            aria-describedby={
                                errors.reporter_external_source
                                    ? 'reporter_external_source-error'
                                    : undefined
                            }
                            placeholder="e.g. Regulator notification, customer complaint"
                            className="mt-1 w-full"
                        />
                        <InputError
                            id="reporter_external_source-error"
                            message={errors.reporter_external_source}
                        />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('incidents.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Report Incident
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
