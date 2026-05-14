import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import Textarea from '@/Components/Textarea';
import Select from '@/Components/Select';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface Policy {
    id: number;
    reference: string;
    title: string;
    category: string;
    category_label: string;
    owner_team: string;
    version: number;
    state: string;
    state_label: string;
    state_color: string;
    effective_date: string | null;
    next_review_date: string | null;
    summary: string | null;
    body: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    policy: Policy;
    categories: { value: string; label: string }[];
}

type FormData = {
    title: string;
    category: string;
    owner_team: string;
    summary: string;
    body: string;
    effective_date: string;
    next_review_date: string;
};

export default function PoliciesEdit({ policy, categories = [] }: Props) {
    const isDraft = policy.state === 'draft';

    const { data, setData, put, processing, errors } = useForm<FormData>({
        title:            policy.title,
        category:         policy.category,
        owner_team:       policy.owner_team,
        summary:          policy.summary ?? '',
        body:             policy.body ?? '',
        effective_date:   policy.effective_date ?? '',
        next_review_date: policy.next_review_date ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!isDraft) return;
        put(route('policies.update', policy.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${policy.reference}`} />

            <PageHeader
                title={`Edit ${policy.reference}`}
                breadcrumb={[
                    { label: 'Policies', href: route('policies.index') },
                    { label: policy.reference, href: route('policies.show', policy.id) },
                    { label: 'Edit' },
                ]}
            />

            {isDraft ? (
                <div className="mb-4 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <ExclamationTriangleIcon aria-hidden className="mt-0.5 w-4 h-4 flex-shrink-0 text-blue-500" />
                    <span>Editing draft. Once submitted for review, edits will be locked.</span>
                </div>
            ) : (
                <div className="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <ExclamationTriangleIcon aria-hidden className="mt-0.5 w-4 h-4 flex-shrink-0 text-amber-500" />
                    <span>
                        This policy is no longer editable; create a new version instead.
                    </span>
                </div>
            )}

            <form onSubmit={handleSubmit} noValidate>
                <Card hover={false} padding="none" className="mb-6">
                    <Card.Header>
                        <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                            Policy Details
                        </h2>
                    </Card.Header>
                    <Card.Body>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">

                            <div className="sm:col-span-2">
                                <InputLabel htmlFor="title" value="Title" required />
                                <TextInput
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    hasError={!!errors.title}
                                    maxLength={500}
                                    disabled={!isDraft}
                                    aria-required="true"
                                    aria-describedby={errors.title ? 'title-error' : undefined}
                                    className="mt-1 w-full"
                                />
                                <InputError id="title-error" message={errors.title} />
                            </div>

                            <div>
                                <InputLabel htmlFor="category" value="Category" required />
                                <Select
                                    id="category"
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    hasError={!!errors.category}
                                    disabled={!isDraft}
                                    aria-required="true"
                                    aria-describedby={errors.category ? 'category-error' : undefined}
                                    className="mt-1 w-full"
                                >
                                    <option value="">Select category…</option>
                                    {categories.map((c) => (
                                        <option key={c.value} value={c.value}>{c.label}</option>
                                    ))}
                                </Select>
                                <InputError id="category-error" message={errors.category} />
                            </div>

                            <div>
                                <InputLabel htmlFor="owner_team" value="Owner Team" required />
                                <TextInput
                                    id="owner_team"
                                    value={data.owner_team}
                                    onChange={(e) => setData('owner_team', e.target.value)}
                                    hasError={!!errors.owner_team}
                                    placeholder="e.g., Compliance"
                                    disabled={!isDraft}
                                    aria-required="true"
                                    aria-describedby={errors.owner_team ? 'owner_team-error' : undefined}
                                    className="mt-1 w-full"
                                />
                                <InputError id="owner_team-error" message={errors.owner_team} />
                            </div>

                            <div>
                                <InputLabel htmlFor="effective_date" value="Effective Date" />
                                <TextInput
                                    id="effective_date"
                                    type="date"
                                    value={data.effective_date}
                                    onChange={(e) => setData('effective_date', e.target.value)}
                                    hasError={!!errors.effective_date}
                                    disabled={!isDraft}
                                    aria-describedby={errors.effective_date ? 'effective_date-error' : undefined}
                                    className="mt-1 w-full"
                                />
                                <InputError id="effective_date-error" message={errors.effective_date} />
                            </div>

                            <div>
                                <InputLabel htmlFor="next_review_date" value="Next Review Date" />
                                <TextInput
                                    id="next_review_date"
                                    type="date"
                                    value={data.next_review_date}
                                    onChange={(e) => setData('next_review_date', e.target.value)}
                                    hasError={!!errors.next_review_date}
                                    disabled={!isDraft}
                                    aria-describedby={errors.next_review_date ? 'next_review_date-error' : undefined}
                                    className="mt-1 w-full"
                                />
                                <InputError id="next_review_date-error" message={errors.next_review_date} />
                            </div>

                            <div className="sm:col-span-2">
                                <InputLabel htmlFor="summary" value="Summary" />
                                <p className="text-xs mb-1" style={{ color: 'var(--color-text-secondary)' }}>
                                    Brief overview shown on lists
                                </p>
                                <Textarea
                                    id="summary"
                                    value={data.summary}
                                    onChange={(e) => setData('summary', e.target.value)}
                                    hasError={!!errors.summary}
                                    disabled={!isDraft}
                                    rows={3}
                                    aria-describedby={errors.summary ? 'summary-error' : undefined}
                                    className="mt-1 w-full"
                                />
                                <InputError id="summary-error" message={errors.summary} />
                            </div>

                            <div className="sm:col-span-2">
                                <InputLabel htmlFor="body" value="Body" />
                                <p className="text-xs mb-1" style={{ color: 'var(--color-text-secondary)' }}>
                                    Full policy text — markdown supported
                                </p>
                                <Textarea
                                    id="body"
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    hasError={!!errors.body}
                                    disabled={!isDraft}
                                    rows={12}
                                    aria-describedby={errors.body ? 'body-error' : undefined}
                                    className="mt-1 w-full"
                                />
                                <InputError id="body-error" message={errors.body} />
                            </div>

                        </div>
                    </Card.Body>
                </Card>

                <div className="flex items-center justify-end gap-3 mt-2">
                    <Link href={route('policies.index')}>
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton type="submit" isLoading={processing} disabled={!isDraft || processing}>
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
