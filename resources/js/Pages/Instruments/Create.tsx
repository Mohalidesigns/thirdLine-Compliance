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
    regulators: SelectOption[];
    instrument_types: SelectOption[];
    natures: SelectOption[];
    statuses: SelectOption[];
    risk_ratings: SelectOption[];
    areas_of_focus: SelectOption[];
}

type FormData = {
    source_title: string;
    regulator_id: string;
    instrument_type_id: string;
    nature_id: string;
    status_id: string;
    area_of_focus_id: string;
    risk_rating_id: string;
    risk_rating_explain: string;
    objectives: string;
    commercial_bank_relevance: string;
    commercial_bank_compliance_context: string;
    applicability: string;
    date_issue: string;
    date_commence: string;
    link_url: string;
};

export default function InstrumentsCreate({
    regulators = [],
    instrument_types = [],
    natures = [],
    statuses = [],
    risk_ratings = [],
    areas_of_focus = [],
}: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        source_title: '',
        regulator_id: '',
        instrument_type_id: '',
        nature_id: '',
        status_id: '',
        area_of_focus_id: '',
        risk_rating_id: '',
        risk_rating_explain: '',
        objectives: '',
        commercial_bank_relevance: '',
        commercial_bank_compliance_context: '',
        applicability: '',
        date_issue: '',
        date_commence: '',
        link_url: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('instruments.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Add Instrument" />

            <PageHeader
                title="Add Instrument"
                breadcrumb={[
                    { label: 'Library', href: route('instruments.index') },
                    { label: 'Instruments', href: route('instruments.index') },
                    { label: 'Add' },
                ]}
            />

            <form onSubmit={handleSubmit} noValidate>
                <FormSection title="Basic Details">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="source_title" value="Source Title" required />
                        <TextInput
                            id="source_title"
                            value={data.source_title}
                            onChange={(e) => setData('source_title', e.target.value)}
                            hasError={!!errors.source_title}
                            aria-required="true"
                            aria-describedby={errors.source_title ? 'source_title-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="source_title-error" message={errors.source_title} />
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
                        <InputLabel htmlFor="instrument_type_id" value="Instrument Type" required />
                        <Select
                            id="instrument_type_id"
                            value={data.instrument_type_id}
                            onChange={(e) => setData('instrument_type_id', e.target.value)}
                            hasError={!!errors.instrument_type_id}
                            aria-required="true"
                            aria-describedby={errors.instrument_type_id ? 'instrument_type_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select type…</option>
                            {instrument_types.map((t) => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </Select>
                        <InputError id="instrument_type_id-error" message={errors.instrument_type_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="nature_id" value="Nature" required />
                        <Select
                            id="nature_id"
                            value={data.nature_id}
                            onChange={(e) => setData('nature_id', e.target.value)}
                            hasError={!!errors.nature_id}
                            aria-required="true"
                            aria-describedby={errors.nature_id ? 'nature_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select nature…</option>
                            {natures.map((n) => (
                                <option key={n.value} value={n.value}>{n.label}</option>
                            ))}
                        </Select>
                        <InputError id="nature_id-error" message={errors.nature_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="status_id" value="Status" required />
                        <Select
                            id="status_id"
                            value={data.status_id}
                            onChange={(e) => setData('status_id', e.target.value)}
                            hasError={!!errors.status_id}
                            aria-required="true"
                            aria-describedby={errors.status_id ? 'status_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select status…</option>
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>{s.label}</option>
                            ))}
                        </Select>
                        <InputError id="status_id-error" message={errors.status_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="area_of_focus_id" value="Area of Focus" required />
                        <Select
                            id="area_of_focus_id"
                            value={data.area_of_focus_id}
                            onChange={(e) => setData('area_of_focus_id', e.target.value)}
                            hasError={!!errors.area_of_focus_id}
                            aria-required="true"
                            aria-describedby={errors.area_of_focus_id ? 'area_of_focus_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select area of focus…</option>
                            {areas_of_focus.map((a) => (
                                <option key={a.value} value={a.value}>{a.label}</option>
                            ))}
                        </Select>
                        <InputError id="area_of_focus_id-error" message={errors.area_of_focus_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="applicability" value="Applicability" required />
                        <Select
                            id="applicability"
                            value={data.applicability}
                            onChange={(e) => setData('applicability', e.target.value)}
                            hasError={!!errors.applicability}
                            aria-required="true"
                            aria-describedby={errors.applicability ? 'applicability-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select applicability…</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="Partially">Partially</option>
                        </Select>
                        <InputError id="applicability-error" message={errors.applicability} />
                    </div>
                </FormSection>

                <FormSection title="Risk & Context">
                    <div>
                        <InputLabel htmlFor="risk_rating_id" value="Risk Rating" required />
                        <Select
                            id="risk_rating_id"
                            value={data.risk_rating_id}
                            onChange={(e) => setData('risk_rating_id', e.target.value)}
                            hasError={!!errors.risk_rating_id}
                            aria-required="true"
                            aria-describedby={errors.risk_rating_id ? 'risk_rating_id-error' : undefined}
                            className="mt-1 w-full"
                        >
                            <option value="">Select risk rating…</option>
                            {risk_ratings.map((r) => (
                                <option key={r.value} value={r.value}>{r.label}</option>
                            ))}
                        </Select>
                        <InputError id="risk_rating_id-error" message={errors.risk_rating_id} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="risk_rating_explain" value="Risk Rating Explanation" />
                        <Textarea
                            id="risk_rating_explain"
                            value={data.risk_rating_explain}
                            onChange={(e) => setData('risk_rating_explain', e.target.value)}
                            hasError={!!errors.risk_rating_explain}
                            aria-describedby={errors.risk_rating_explain ? 'risk_rating_explain-error' : undefined}
                            className="mt-1 w-full"
                            rows={3}
                        />
                        <InputError id="risk_rating_explain-error" message={errors.risk_rating_explain} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="objectives" value="Objectives" />
                        <Textarea
                            id="objectives"
                            value={data.objectives}
                            onChange={(e) => setData('objectives', e.target.value)}
                            hasError={!!errors.objectives}
                            aria-describedby={errors.objectives ? 'objectives-error' : undefined}
                            className="mt-1 w-full"
                            rows={3}
                        />
                        <InputError id="objectives-error" message={errors.objectives} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="commercial_bank_relevance" value="Commercial Bank Relevance" />
                        <Textarea
                            id="commercial_bank_relevance"
                            value={data.commercial_bank_relevance}
                            onChange={(e) => setData('commercial_bank_relevance', e.target.value)}
                            hasError={!!errors.commercial_bank_relevance}
                            aria-describedby={errors.commercial_bank_relevance ? 'commercial_bank_relevance-error' : undefined}
                            className="mt-1 w-full"
                            rows={3}
                        />
                        <InputError id="commercial_bank_relevance-error" message={errors.commercial_bank_relevance} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="commercial_bank_compliance_context" value="Commercial Bank Compliance Context" />
                        <Textarea
                            id="commercial_bank_compliance_context"
                            value={data.commercial_bank_compliance_context}
                            onChange={(e) => setData('commercial_bank_compliance_context', e.target.value)}
                            hasError={!!errors.commercial_bank_compliance_context}
                            aria-describedby={errors.commercial_bank_compliance_context ? 'commercial_bank_compliance_context-error' : undefined}
                            className="mt-1 w-full"
                            rows={3}
                        />
                        <InputError id="commercial_bank_compliance_context-error" message={errors.commercial_bank_compliance_context} />
                    </div>
                </FormSection>

                <FormSection title="Dates & References">
                    <div>
                        <InputLabel htmlFor="date_issue" value="Date of Issue" />
                        <TextInput
                            id="date_issue"
                            type="date"
                            value={data.date_issue}
                            onChange={(e) => setData('date_issue', e.target.value)}
                            hasError={!!errors.date_issue}
                            aria-describedby={errors.date_issue ? 'date_issue-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="date_issue-error" message={errors.date_issue} />
                    </div>

                    <div>
                        <InputLabel htmlFor="date_commence" value="Date of Commencement" />
                        <TextInput
                            id="date_commence"
                            type="date"
                            value={data.date_commence}
                            onChange={(e) => setData('date_commence', e.target.value)}
                            hasError={!!errors.date_commence}
                            aria-describedby={errors.date_commence ? 'date_commence-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="date_commence-error" message={errors.date_commence} />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="link_url" value="Link URL" />
                        <TextInput
                            id="link_url"
                            type="url"
                            value={data.link_url}
                            onChange={(e) => setData('link_url', e.target.value)}
                            hasError={!!errors.link_url}
                            placeholder="https://"
                            aria-describedby={errors.link_url ? 'link_url-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="link_url-error" message={errors.link_url} />
                    </div>
                </FormSection>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('instruments.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing}>
                        Save Instrument
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
