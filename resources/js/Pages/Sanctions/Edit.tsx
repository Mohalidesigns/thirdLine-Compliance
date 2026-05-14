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

interface Sanction {
    id: number;
    regulator_id: string | number;
    reference: string;
    section: string;
    offence: string;
    party_name: string;
    party_type: string;
    amount_naira: string | number | null;
    penalty_type: string;
    effective_date: string;
    source_url: string;
}

interface Props {
    sanction: Sanction;
    regulators: SelectOption[];
}

type FormData = {
    regulator_id: string;
    reference: string;
    section: string;
    offence: string;
    party_name: string;
    party_type: string;
    amount_naira: string;
    penalty_type: string;
    effective_date: string;
    source_url: string;
};

export default function SanctionsEdit({ sanction, regulators = [] }: Props) {
    const { data, setData, put, processing, errors } = useForm<FormData>({
        regulator_id: String(sanction.regulator_id ?? ''),
        reference: sanction.reference ?? '',
        section: sanction.section ?? '',
        offence: sanction.offence ?? '',
        party_name: sanction.party_name ?? '',
        party_type: sanction.party_type ?? '',
        amount_naira: sanction.amount_naira != null ? String(sanction.amount_naira) : '',
        penalty_type: sanction.penalty_type ?? '',
        effective_date: sanction.effective_date ?? '',
        source_url: sanction.source_url ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('sanctions.update', sanction.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit Sanction: ${sanction.reference}`} />

            <PageHeader
                title={`Edit Sanction: ${sanction.reference}`}
                breadcrumb={[
                    { label: 'Sanctions KB', href: route('sanctions.index') },
                    { label: sanction.reference, href: route('sanctions.show', sanction.id) },
                    { label: 'Edit' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Party Details">
                    <div>
                        <InputLabel htmlFor="party_name" value="Party Name" required />
                        <TextInput
                            id="party_name"
                            value={data.party_name}
                            onChange={(e) => setData('party_name', e.target.value)}
                            hasError={!!errors.party_name}
                            aria-required="true"
                            aria-describedby={errors.party_name ? 'party_name-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="party_name-error" message={errors.party_name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="party_type" value="Party Type" required />
                        <Select
                            id="party_type"
                            value={data.party_type}
                            onChange={(e) => setData('party_type', e.target.value)}
                            hasError={!!errors.party_type}
                            aria-required="true"
                            aria-describedby={errors.party_type ? 'party_type-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select party type…</option>
                            <option value="individual">Individual</option>
                            <option value="institution">Institution</option>
                            <option value="committee">Committee</option>
                        </Select>
                        <InputError id="party_type-error" message={errors.party_type} />
                    </div>

                    <div>
                        <InputLabel htmlFor="regulator_id" value="Regulator" required />
                        <Select
                            id="regulator_id"
                            value={data.regulator_id}
                            onChange={(e) => setData('regulator_id', e.target.value)}
                            hasError={!!errors.regulator_id}
                            aria-required="true"
                            aria-describedby={errors.regulator_id ? 'regulator_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select regulator…</option>
                            {regulators.map((r) => (
                                <option key={r.value} value={r.value}>{r.label}</option>
                            ))}
                        </Select>
                        <InputError id="regulator_id-error" message={errors.regulator_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="reference" value="Reference" required />
                        <TextInput
                            id="reference"
                            value={data.reference}
                            onChange={(e) => setData('reference', e.target.value)}
                            hasError={!!errors.reference}
                            aria-required="true"
                            aria-describedby={errors.reference ? 'reference-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="reference-error" message={errors.reference} />
                    </div>

                    <div>
                        <InputLabel htmlFor="section" value="Section" />
                        <TextInput
                            id="section"
                            value={data.section}
                            onChange={(e) => setData('section', e.target.value)}
                            hasError={!!errors.section}
                            aria-describedby={errors.section ? 'section-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="section-error" message={errors.section} />
                    </div>
                </FormSection>

                <FormSection title="Offence & Penalty">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="offence" value="Offence" required />
                        <Textarea
                            id="offence"
                            value={data.offence}
                            onChange={(e) => setData('offence', e.target.value)}
                            hasError={!!errors.offence}
                            aria-required="true"
                            aria-describedby={errors.offence ? 'offence-error' : undefined}
                            className="mt-1 w-full"
                            rows={4}
                        />
                        <InputError id="offence-error" message={errors.offence} />
                    </div>

                    <div>
                        <InputLabel htmlFor="penalty_type" value="Penalty Type" required />
                        <Select
                            id="penalty_type"
                            value={data.penalty_type}
                            onChange={(e) => setData('penalty_type', e.target.value)}
                            hasError={!!errors.penalty_type}
                            aria-required="true"
                            aria-describedby={errors.penalty_type ? 'penalty_type-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select penalty type…</option>
                            <option value="monetary">Monetary</option>
                            <option value="license_action">License Action</option>
                            <option value="reprimand">Reprimand</option>
                            <option value="other">Other</option>
                        </Select>
                        <InputError id="penalty_type-error" message={errors.penalty_type} />
                    </div>

                    <div>
                        <InputLabel htmlFor="amount_naira" value="Amount (NGN)" />
                        <TextInput
                            id="amount_naira"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.amount_naira}
                            onChange={(e) => setData('amount_naira', e.target.value)}
                            hasError={!!errors.amount_naira}
                            aria-describedby={errors.amount_naira ? 'amount_naira-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="amount_naira-error" message={errors.amount_naira} />
                    </div>
                </FormSection>

                <FormSection title="Dates & References">
                    <div>
                        <InputLabel htmlFor="effective_date" value="Effective Date" required />
                        <TextInput
                            id="effective_date"
                            type="date"
                            value={data.effective_date}
                            onChange={(e) => setData('effective_date', e.target.value)}
                            hasError={!!errors.effective_date}
                            aria-required="true"
                            aria-describedby={errors.effective_date ? 'effective_date-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="effective_date-error" message={errors.effective_date} />
                    </div>

                    <div>
                        <InputLabel htmlFor="source_url" value="Source URL" />
                        <TextInput
                            id="source_url"
                            type="url"
                            value={data.source_url}
                            onChange={(e) => setData('source_url', e.target.value)}
                            hasError={!!errors.source_url}
                            placeholder="https://"
                            aria-describedby={errors.source_url ? 'source_url-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="source_url-error" message={errors.source_url} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('sanctions.show', sanction.id)}>
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
