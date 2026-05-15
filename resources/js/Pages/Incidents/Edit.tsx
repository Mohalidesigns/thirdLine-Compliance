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
import { ChevronDownIcon, ChevronUpIcon, InformationCircleIcon } from '@heroicons/react/24/outline';

// ---- types ----

interface SelectOption<V = string> {
    value: V;
    label: string;
}

interface IncidentForEdit {
    id: number;
    code: string;
    title: string;
    description: string | null;
    category: string;
    severity: string;
    occurred_at: string | null;
    detected_at: string;
    is_cyber_incident: boolean;
    is_data_breach: boolean;
    affects_customers: boolean;
    affected_customer_count: number | null;
    basel_category: string | null;
    financial_impact: string | null;    // raw numeric string from backend
    currency: string | null;
    linked_risk_id: number | null;
    linked_control_id: number | null;
    linked_policy_id: number | null;
    assigned_to_id: number | null;
    reporter_external_source: string | null;
    notifications_sent_count: number;  // >0 means classification flags are locked
}

interface Props {
    incident: IncidentForEdit;
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

// Convert a backend datetime string to the datetime-local input value format (YYYY-MM-DDTHH:MM)
function toDatetimeLocal(dateStr: string | null): string {
    if (!dateStr) return '';
    // ISO string: 2024-01-15T09:30:00.000Z → 2024-01-15T09:30
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return (
        `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` +
        `T${pad(d.getHours())}:${pad(d.getMinutes())}`
    );
}

// ---- collapsible section (same pattern as Create) ----

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

// ---- locked flag tooltip ----

function LockedFlagNote() {
    return (
        <p
            className="flex items-center gap-1.5 text-xs text-amber-600 mt-1"
            role="note"
        >
            <InformationCircleIcon aria-hidden className="w-4 h-4 flex-shrink-0" />
            Classification flags are read-only because at least one regulatory notification has
            already been sent. Re-notifying is not automatic — contact Compliance if changes are
            required.
        </p>
    );
}

// ---- main component ----

export default function IncidentsEdit({
    incident,
    category_options,
    severity_options,
    basel_options,
    risks_options,
    controls_options,
    policies_options,
    users_options,
}: Props) {
    // Classification flags are locked once any notification has been sent
    const classificationLocked = incident.notifications_sent_count > 0;

    const { data, setData, put, processing, errors } = useForm<FormData>({
        title: incident.title,
        description: incident.description ?? '',
        category: incident.category,
        severity: incident.severity,
        occurred_at: toDatetimeLocal(incident.occurred_at),
        detected_at: toDatetimeLocal(incident.detected_at),
        is_cyber_incident: incident.is_cyber_incident,
        is_data_breach: incident.is_data_breach,
        affects_customers: incident.affects_customers,
        affected_customer_count: incident.affected_customer_count?.toString() ?? '',
        basel_category: incident.basel_category ?? '',
        financial_impact: incident.financial_impact ?? '',
        currency: incident.currency ?? 'NGN',
        linked_risk_id: incident.linked_risk_id?.toString() ?? '',
        linked_control_id: incident.linked_control_id?.toString() ?? '',
        linked_policy_id: incident.linked_policy_id?.toString() ?? '',
        assigned_to: incident.assigned_to_id?.toString() ?? '',
        reporter_external_source: incident.reporter_external_source ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('incidents.update', incident.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${incident.code}`} />

            <PageHeader
                title={`Edit ${incident.code}`}
                breadcrumb={[
                    { label: 'Incidents', href: route('incidents.index') },
                    { label: incident.code, href: route('incidents.show', incident.id) },
                    { label: 'Edit' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                {/* Code — always read-only */}
                <div className="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm px-6 py-4 flex items-center gap-3">
                    <span className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                        Incident Code:
                    </span>
                    <span
                        className="font-mono text-sm font-semibold"
                        style={{ color: 'var(--color-text-primary)' }}
                    >
                        {incident.code}
                    </span>
                    <span className="text-xs text-gray-400">(read-only)</span>
                </div>

                {/* Section 1: Basics */}
                <FormSection title="Basics" description="Core incident details.">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="title" value="Title" required />
                        <TextInput
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            aria-describedby={errors.title ? 'title-error' : undefined}
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
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.detected_at} />
                    </div>

                    <div>
                        <InputLabel htmlFor="occurred_at" value="Occurred At (optional)" />
                        <TextInput
                            id="occurred_at"
                            type="datetime-local"
                            value={data.occurred_at}
                            onChange={(e) => setData('occurred_at', e.target.value)}
                            hasError={!!errors.occurred_at}
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.occurred_at} />
                    </div>
                </FormSection>

                {/* Section 2: Classification flags */}
                <FormSection
                    title="Classification"
                    description={
                        classificationLocked
                            ? 'These flags are locked because a regulatory notification has already been sent.'
                            : 'Regulatory classification flags.'
                    }
                >
                    <div className="sm:col-span-2 flex flex-col gap-4">
                        {classificationLocked && <LockedFlagNote />}

                        <div>
                            <label
                                className={`flex items-start gap-3 ${classificationLocked ? 'opacity-60' : 'cursor-pointer'}`}
                            >
                                <Checkbox
                                    id="is_cyber_incident"
                                    checked={data.is_cyber_incident}
                                    onChange={
                                        classificationLocked
                                            ? undefined
                                            : (e) => setData('is_cyber_incident', e.target.checked)
                                    }
                                    disabled={classificationLocked}
                                    aria-describedby="is_cyber_incident-help"
                                />
                                <div>
                                    <span
                                        className="text-sm font-medium"
                                        style={{ color: 'var(--color-text-primary)' }}
                                    >
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
                        </div>

                        <div>
                            <label
                                className={`flex items-start gap-3 ${classificationLocked ? 'opacity-60' : 'cursor-pointer'}`}
                            >
                                <Checkbox
                                    id="is_data_breach"
                                    checked={data.is_data_breach}
                                    onChange={
                                        classificationLocked
                                            ? undefined
                                            : (e) => setData('is_data_breach', e.target.checked)
                                    }
                                    disabled={classificationLocked}
                                    aria-describedby="is_data_breach-help"
                                />
                                <div>
                                    <span
                                        className="text-sm font-medium"
                                        style={{ color: 'var(--color-text-primary)' }}
                                    >
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
                        </div>

                        <div>
                            <label className="flex items-start gap-3 cursor-pointer">
                                <Checkbox
                                    id="affects_customers"
                                    checked={data.affects_customers}
                                    onChange={(e) =>
                                        setData('affects_customers', e.target.checked)
                                    }
                                />
                                <span
                                    className="text-sm font-medium"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    Affects Customers
                                </span>
                            </label>
                        </div>

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
                                    placeholder="0"
                                    className="mt-1 w-48"
                                />
                                <InputError message={errors.affected_customer_count} />
                            </div>
                        )}
                    </div>
                </FormSection>

                {/* Section 3: Impact */}
                <FormSection title="Impact">
                    <div>
                        <InputLabel htmlFor="basel_category" value="Basel Category" />
                        <Select
                            id="basel_category"
                            value={data.basel_category}
                            onChange={(e) => setData('basel_category', e.target.value)}
                            hasError={!!errors.basel_category}
                            className="mt-1 w-full"
                        >
                            <option value="">Select Basel category…</option>
                            {basel_options.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.basel_category} />
                    </div>

                    <div>
                        <InputLabel htmlFor="currency" value="Currency" />
                        <TextInput
                            id="currency"
                            value={data.currency}
                            onChange={(e) =>
                                setData('currency', e.target.value.slice(0, 3).toUpperCase())
                            }
                            hasError={!!errors.currency}
                            maxLength={3}
                            placeholder="NGN"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.currency} />
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
                            placeholder="0.00"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.financial_impact} />
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
                            className="mt-1 w-full"
                        >
                            <option value="">None</option>
                            {risks_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.linked_risk_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="linked_control_id" value="Linked Control" />
                        <Select
                            id="linked_control_id"
                            value={data.linked_control_id}
                            onChange={(e) => setData('linked_control_id', e.target.value)}
                            hasError={!!errors.linked_control_id}
                            className="mt-1 w-full"
                        >
                            <option value="">None</option>
                            {controls_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.linked_control_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="linked_policy_id" value="Linked Policy" />
                        <Select
                            id="linked_policy_id"
                            value={data.linked_policy_id}
                            onChange={(e) => setData('linked_policy_id', e.target.value)}
                            hasError={!!errors.linked_policy_id}
                            className="mt-1 w-full"
                        >
                            <option value="">None</option>
                            {policies_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.linked_policy_id} />
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
                            className="mt-1 w-full"
                        >
                            <option value="">Unassigned</option>
                            {users_options.map((o) => (
                                <option key={o.value} value={String(o.value)}>
                                    {o.label}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.assigned_to} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="reporter_external_source"
                            value="External Reporter / Source (optional)"
                        />
                        <TextInput
                            id="reporter_external_source"
                            value={data.reporter_external_source}
                            onChange={(e) =>
                                setData('reporter_external_source', e.target.value)
                            }
                            hasError={!!errors.reporter_external_source}
                            placeholder="e.g. Regulator notification, customer complaint"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.reporter_external_source} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('incidents.show', incident.id)}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
