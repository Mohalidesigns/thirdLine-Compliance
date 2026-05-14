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

interface Props {
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

export default function PoliciesCreate({ categories = [] }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        title:            '',
        category:         '',
        owner_team:       '',
        summary:          '',
        body:             '',
        effective_date:   '',
        next_review_date: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('policies.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="New Policy" />

            <PageHeader
                title="New Policy"
                breadcrumb={[
                    { label: 'Policies', href: route('policies.index') },
                    { label: 'New' },
                ]}
            />

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
                    <PrimaryButton type="submit" isLoading={processing}>
                        Create Policy
                    </PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
