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
import Card from '@/Components/Card';
import Checkbox from '@/Components/Checkbox';

interface Props {
    types: { value: string; label: string }[];
    natures: { value: string; label: string }[];
    frequencies: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    obligations: { value: number; label: string }[];
}

type FormData = {
    title: string;
    description: string;
    control_type: string;
    nature: string;
    frequency: string;
    owner_team: string;
    status: string;
    linked_obligation_ids: number[];
};

export default function ControlsCreate({ types, natures, frequencies, statuses, obligations }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        title:                    '',
        description:              '',
        control_type:             '',
        nature:                   '',
        frequency:                '',
        owner_team:               '',
        status:                   '',
        linked_obligation_ids:    [],
    });

    const [obligationSearch, setObligationSearch] = useState('');

    const filteredObligations = obligations.filter((o) =>
        o.label.toLowerCase().includes(obligationSearch.toLowerCase())
    );

    const toggleObligation = (id: number) => {
        setData('linked_obligation_ids', data.linked_obligation_ids.includes(id)
            ? data.linked_obligation_ids.filter((i) => i !== id)
            : [...data.linked_obligation_ids, id]
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('controls.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Add Control" />

            <PageHeader
                title="Add Control"
                breadcrumb={[
                    { label: 'Controls', href: route('controls.index') },
                    { label: 'Add' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Control Details">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="title" value="Control Title" required />
                        <TextInput
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            aria-describedby={errors.title ? 'title-error' : undefined}
                            placeholder="e.g. Monthly reconciliation of transaction records"
                            className="mt-1 w-full"
                        />
                        <InputError id="title-error" message={errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="control_type" value="Control Type" required />
                        <Select
                            id="control_type"
                            value={data.control_type}
                            onChange={(e) => setData('control_type', e.target.value)}
                            hasError={!!errors.control_type}
                            aria-required="true"
                            aria-describedby={errors.control_type ? 'control_type-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select type…</option>
                            {types.map((t) => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </Select>
                        <InputError id="control_type-error" message={errors.control_type} />
                    </div>

                    <div>
                        <InputLabel htmlFor="nature" value="Nature" required />
                        <Select
                            id="nature"
                            value={data.nature}
                            onChange={(e) => setData('nature', e.target.value)}
                            hasError={!!errors.nature}
                            aria-required="true"
                            aria-describedby={errors.nature ? 'nature-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select nature…</option>
                            {natures.map((n) => (
                                <option key={n.value} value={n.value}>{n.label}</option>
                            ))}
                        </Select>
                        <InputError id="nature-error" message={errors.nature} />
                    </div>

                    <div>
                        <InputLabel htmlFor="frequency" value="Testing Frequency" required />
                        <Select
                            id="frequency"
                            value={data.frequency}
                            onChange={(e) => setData('frequency', e.target.value)}
                            hasError={!!errors.frequency}
                            aria-required="true"
                            aria-describedby={errors.frequency ? 'frequency-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select frequency…</option>
                            {frequencies.map((f) => (
                                <option key={f.value} value={f.value}>{f.label}</option>
                            ))}
                        </Select>
                        <InputError id="frequency-error" message={errors.frequency} />
                    </div>

                    <div>
                        <InputLabel htmlFor="owner_team" value="Owner Team" required />
                        <TextInput
                            id="owner_team"
                            value={data.owner_team}
                            onChange={(e) => setData('owner_team', e.target.value)}
                            hasError={!!errors.owner_team}
                            aria-required="true"
                            aria-describedby={errors.owner_team ? 'owner_team-error' : undefined}
                            placeholder="e.g. Compliance Operations"
                            className="mt-1 w-full"
                        />
                        <InputError id="owner_team-error" message={errors.owner_team} />
                    </div>

                    <div>
                        <InputLabel htmlFor="status" value="Status" required />
                        <Select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            hasError={!!errors.status}
                            aria-required="true"
                            aria-describedby={errors.status ? 'status-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select status…</option>
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>{s.label}</option>
                            ))}
                        </Select>
                        <InputError id="status-error" message={errors.status} />
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
                            placeholder="Describe the control, its purpose, and how it operates…"
                            className="mt-1 w-full"
                        />
                        <InputError id="description-error" message={errors.description} />
                    </div>
                </FormSection>

                {obligations.length > 0 && (
                    <div className="mb-6">
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                        Linked Obligations
                                    </h3>
                                    {data.linked_obligation_ids.length > 0 && (
                                        <span className="badge border bg-blue-100 text-blue-700 border-blue-200 text-xs">
                                            {data.linked_obligation_ids.length} selected
                                        </span>
                                    )}
                                </div>
                            </Card.Header>
                            <Card.Body>
                                <div className="mb-3">
                                    <TextInput
                                        type="text"
                                        placeholder="Search obligations…"
                                        value={obligationSearch}
                                        onChange={(e) => setObligationSearch(e.target.value)}
                                        aria-label="Search obligations"
                                        className="w-full"
                                    />
                                </div>
                                <div
                                    className="max-h-48 overflow-y-auto divide-y divide-gray-50"
                                    role="group"
                                    aria-label="Obligations"
                                >
                                    {filteredObligations.length === 0 ? (
                                        <p className="text-xs text-gray-400 py-3 text-center">No obligations match your search.</p>
                                    ) : (
                                        filteredObligations.map((ob) => (
                                            <label
                                                key={ob.value}
                                                className="flex items-center gap-3 py-2 px-1 cursor-pointer hover:bg-gray-50 rounded"
                                            >
                                                <Checkbox
                                                    checked={data.linked_obligation_ids.includes(ob.value)}
                                                    onChange={() => toggleObligation(ob.value)}
                                                    aria-label={ob.label}
                                                />
                                                <span className="text-sm" style={{ color: 'var(--color-text-primary)' }}>
                                                    {ob.label}
                                                </span>
                                            </label>
                                        ))
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    </div>
                )}

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('controls.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Save Control
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
