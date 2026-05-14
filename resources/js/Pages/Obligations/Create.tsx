import { useForm } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
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
    value: string | number;
    label: string;
}

interface Props {
    instruments: SelectOption[];
}

type FormData = {
    instrument_id: string;
    reference: string;
    title: string;
    description: string;
    due_basis: string;
    frequency: string;
    next_due_date: string;
    responsible_team: string;
    status: string;
};

export default function ObligationsCreate({ instruments = [] }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        instrument_id: '',
        reference: '',
        title: '',
        description: '',
        due_basis: '',
        frequency: '',
        next_due_date: '',
        responsible_team: '',
        status: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('obligations.store'));
    };

    const showFrequency = data.due_basis === 'recurring';

    return (
        <AuthenticatedLayout>
            <Head title="Add Obligation" />

            <PageHeader
                title="Add Obligation"
                breadcrumb={[
                    { label: 'Obligations', href: route('obligations.index') },
                    { label: 'Add' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Obligation Details">
                    <div>
                        <InputLabel htmlFor="instrument_id" value="Instrument" required />
                        <Select
                            id="instrument_id"
                            value={data.instrument_id}
                            onChange={(e) => setData('instrument_id', e.target.value)}
                            hasError={!!errors.instrument_id}
                            aria-required="true"
                            aria-describedby={errors.instrument_id ? 'instrument_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select instrument…</option>
                            {instruments.map((i) => (
                                <option key={i.value} value={i.value}>{i.label}</option>
                            ))}
                        </Select>
                        <InputError id="instrument_id-error" message={errors.instrument_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="reference" value="Reference" required />
                        <TextInput
                            id="reference"
                            value={data.reference}
                            onChange={(e) => setData('reference', e.target.value)}
                            hasError={!!errors.reference}
                            placeholder="e.g., S.12(3)"
                            aria-required="true"
                            aria-describedby={errors.reference ? 'reference-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="reference-error" message={errors.reference} />
                    </div>

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
                        <InputLabel htmlFor="description" value="Description" required />
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            hasError={!!errors.description}
                            aria-required="true"
                            aria-describedby={errors.description ? 'description-error' : undefined}
                            className="mt-1 w-full"
                            rows={4}
                        />
                        <InputError id="description-error" message={errors.description} />
                    </div>
                </FormSection>

                <FormSection title="Schedule & Status">
                    <div>
                        <InputLabel htmlFor="due_basis" value="Due Basis" required />
                        <Select
                            id="due_basis"
                            value={data.due_basis}
                            onChange={(e) => setData('due_basis', e.target.value)}
                            hasError={!!errors.due_basis}
                            aria-required="true"
                            aria-describedby={errors.due_basis ? 'due_basis-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select due basis…</option>
                            <option value="recurring">Recurring</option>
                            <option value="one_off">One-off</option>
                            <option value="event_driven">Event Driven</option>
                        </Select>
                        <InputError id="due_basis-error" message={errors.due_basis} />
                    </div>

                    {showFrequency && (
                        <div>
                            <InputLabel htmlFor="frequency" value="Frequency" />
                            <Select
                                id="frequency"
                                value={data.frequency}
                                onChange={(e) => setData('frequency', e.target.value)}
                                hasError={!!errors.frequency}
                                aria-describedby={errors.frequency ? 'frequency-error' : undefined}
                                className="mt-1 w-full"
                            >
                                <option value="">Select frequency…</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semiannual">Semi-annual</option>
                                <option value="annual">Annual</option>
                                <option value="adhoc">Ad hoc</option>
                            </Select>
                            <InputError id="frequency-error" message={errors.frequency} />
                        </div>
                    )}

                    <div>
                        <InputLabel htmlFor="next_due_date" value="Next Due Date" />
                        <TextInput
                            id="next_due_date"
                            type="date"
                            value={data.next_due_date}
                            onChange={(e) => setData('next_due_date', e.target.value)}
                            hasError={!!errors.next_due_date}
                            aria-describedby={errors.next_due_date ? 'next_due_date-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="next_due_date-error" message={errors.next_due_date} />
                    </div>

                    <div>
                        <InputLabel htmlFor="responsible_team" value="Responsible Team" />
                        <TextInput
                            id="responsible_team"
                            value={data.responsible_team}
                            onChange={(e) => setData('responsible_team', e.target.value)}
                            hasError={!!errors.responsible_team}
                            aria-describedby={errors.responsible_team ? 'responsible_team-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="responsible_team-error" message={errors.responsible_team} />
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
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="satisfied">Satisfied</option>
                            <option value="overdue">Overdue</option>
                        </Select>
                        <InputError id="status-error" message={errors.status} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('obligations.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Save Obligation
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
