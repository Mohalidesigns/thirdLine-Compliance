# Atheris Compliance Management — MVP Design Specification

**Version:** 1.0  
**Date:** 2026-05-14  
**Stack:** Laravel 13 · Inertia.js · React 18 · TypeScript · Tailwind v4 (CSS-first)  
**Source of truth:** `AuditPro_GRC_Design_System.md` v1.0  

---

## Section A — Design Tokens (`@theme` block for `app.css`)

Replace the existing `:root { ... }` block in `resources/css/app.css` with the following. The `@theme` block makes Tailwind v4 generate utility classes (e.g. `bg-primary`, `text-text-primary`) automatically from every token. Keep the `:root` block intact above it so that `var(--color-*)` still works in arbitrary values and third-party CSS.

```css
/*
 * Tailwind v4 @theme block — generates utility classes from every token.
 * Must appear AFTER the @import directives and BEFORE @layer rules.
 * Reference: AuditPro GRC Design System §2.1, §3.1, §4.3, §5.1, §5.3
 */
@theme {
  /* ── Brand colors ──────────────────────────────────────────────── */
  --color-primary:          #1A365D;   /* dark navy  */
  --color-primary-light:    #2A4A7F;   /* medium navy */
  --color-primary-dark:     #0F2340;   /* deep navy   */
  --color-secondary:        #2D7D46;   /* forest green */
  --color-secondary-light:  #38A169;   /* bright green */
  --color-accent:           #D4AF37;   /* gold/amber   */
  --color-accent-light:     #E2C458;   /* light gold   */

  /* ── Surface colors ────────────────────────────────────────────── */
  --color-bg:               #F7FAFC;
  --color-card:             #FFFFFF;

  /* ── Semantic text ─────────────────────────────────────────────── */
  --color-text-primary:     #2D3748;
  --color-text-secondary:   #718096;

  /* ── Semantic status ───────────────────────────────────────────── */
  --color-error:            #C53030;
  --color-warning:          #DD6B20;
  --color-info:             #319795;
  --color-success:          #2D7D46;

  /* ── Font families ─────────────────────────────────────────────── */
  --font-sans:  'Inter', ui-sans-serif, system-ui, sans-serif;
  --font-mono:  'Roboto Mono', ui-monospace, monospace;

  /* ── Sidebar layout dimensions ─────────────────────────────────── */
  --sidebar-width:           260px;
  --sidebar-collapsed-width:  72px;

  /* ── Border radius scale ───────────────────────────────────────── */
  --radius-sm:   2px;
  --radius:      4px;
  --radius-md:   6px;
  --radius-lg:   8px;
  --radius-xl:  12px;
  --radius-full: 9999px;

  /* ── Shadow scale ──────────────────────────────────────────────── */
  --shadow-card:       0 1px 3px rgba(0,0,0,0.08);
  --shadow-card-hover: 0 4px 12px rgba(0,0,0,0.12);
  --shadow-topbar:     0 1px 3px rgba(0,0,0,0.05);
}
```

> Note: Tailwind v4 maps `--color-*` → `bg-*`, `text-*`, `border-*` utilities, and `--font-*` → `font-*` utilities, automatically. Do not add a `tailwind.config.js`.

---

## Section B — Component CSS Classes

Add the following block to `resources/css/app.css` immediately after the `@theme` block. These classes are used directly in TSX via `className`.

```css
@layer components {

  /* ── Card ──────────────────────────────────────────────────────── */
  .card {
    background-color: var(--color-card);
    border-radius: var(--radius-xl);
    border: 1px solid #EDF2F7; /* gray-100 */
    box-shadow: var(--shadow-card);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
  }
  .card:hover {
    box-shadow: var(--shadow-card-hover);
    transform: translateY(-2px);
  }

  /* ── Stat Card ─────────────────────────────────────────────────── */
  .stat-card {
    @apply card p-6;
  }

  /* ── Badge base ────────────────────────────────────────────────── */
  .badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold;
  }

  /* ── Form label ────────────────────────────────────────────────── */
  .form-label {
    @apply block text-sm font-medium text-gray-700 mb-1;
  }

  /* ── Form input ────────────────────────────────────────────────── */
  .form-input {
    @apply w-full rounded-lg border-gray-300 shadow-sm
           px-3 py-2.5 text-sm text-gray-700 placeholder-gray-400
           focus:border-indigo-500 focus:ring-indigo-500
           disabled:opacity-50 disabled:cursor-not-allowed;
  }

  /* ── Form select ───────────────────────────────────────────────── */
  .form-select {
    @apply form-input appearance-none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25em 1.25em;
    padding-right: 2.5rem;
  }

  /* ── Progress bar ──────────────────────────────────────────────── */
  .progress-bar {
    @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
  }
  .progress-bar-fill {
    @apply h-full rounded-full transition-all duration-500;
    background-color: var(--color-secondary);
  }

  /* ── Page title ────────────────────────────────────────────────── */
  .page-title {
    @apply text-2xl font-bold;
    color: var(--color-text-primary);
  }

  /* ── Page header ───────────────────────────────────────────────── */
  .page-header {
    @apply flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6;
  }

  /* ── Filter bar ────────────────────────────────────────────────── */
  .filter-bar {
    @apply bg-gray-50/80 rounded-xl border border-gray-200 px-5 py-4 mb-6;
  }

  /* ── Filter group ──────────────────────────────────────────────── */
  .filter-group {
    @apply flex flex-col gap-1.5 min-w-0;
  }

  /* ── Data table wrapper ────────────────────────────────────────── */
  .data-table {
    @apply w-full;
    border-collapse: collapse;
  }
  .data-table thead tr {
    @apply bg-gray-50 border-b border-gray-200;
  }
  .data-table thead th {
    @apply px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-left;
  }
  .data-table tbody tr {
    @apply hover:bg-gray-50 transition-colors;
  }
  .data-table tbody td {
    @apply px-4 py-3 border-b border-gray-100 text-sm;
    color: var(--color-text-primary);
  }

  /* ── Sidebar shell ─────────────────────────────────────────────── */
  .sidebar {
    background-color: var(--color-primary);
    width: var(--sidebar-width);
    @apply fixed top-0 left-0 h-full z-40 flex flex-col
           transition-all duration-300 overflow-y-auto;
  }
  .sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
  }

  /* ── Sidebar nav item ──────────────────────────────────────────── */
  .sidebar-nav-item {
    @apply flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-lg
           transition-all duration-200 mx-2;
    color: rgba(255,255,255,0.7);
  }
  .sidebar-nav-item:hover {
    background-color: rgba(255,255,255,0.05);
    color: white;
  }
  .sidebar-nav-item.active {
    background-color: rgba(255,255,255,0.10);
    color: white;
    border-left: 3px solid var(--color-accent);
    padding-left: calc(1rem - 3px); /* compensate for border */
  }

}
```

---

## Section C — React Component Specs

### Priority 1 — Must-Have for MVP

---

#### C.1 Buttons

**File:** `resources/js/Components/PrimaryButton.tsx`  
Replace the existing Breeze file completely.

```typescript
interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  isLoading?: boolean;
}
```

Classes:
```
inline-flex items-center gap-2 rounded-lg border border-transparent
bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white
hover:bg-primary-light focus:bg-primary-light active:bg-primary-dark
focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2
disabled:opacity-25 disabled:cursor-not-allowed
transition ease-in-out duration-150
```

States: `isLoading` → replace children with `<span aria-hidden>spinner SVG</span><span>Loading…</span>` and set `disabled`.  
ARIA: `aria-busy={isLoading}`.  
Keyboard: native `<button>` — Enter/Space activate.  

Usage:
```tsx
<PrimaryButton onClick={handleSave}>Save Instrument</PrimaryButton>
<PrimaryButton isLoading={saving}>Saving…</PrimaryButton>
```

---

**File:** `resources/js/Components/SecondaryButton.tsx`  
Replace existing Breeze file.

```typescript
interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {}
```

Classes:
```
inline-flex items-center gap-2 rounded-lg border border-gray-300
bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700
shadow-sm hover:bg-gray-50
focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2
disabled:opacity-25 disabled:cursor-not-allowed
transition ease-in-out duration-150
```

---

**File:** `resources/js/Components/DangerButton.tsx`  
Replace existing Breeze file.

```typescript
interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {}
```

Classes:
```
inline-flex items-center gap-2 rounded-lg border border-transparent
bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white
hover:bg-red-500 active:bg-red-700
focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2
disabled:opacity-25 disabled:cursor-not-allowed
transition ease-in-out duration-150
```

---

**File:** `resources/js/Components/IconButton.tsx` (new)

```typescript
interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  label: string;           // Required — used as aria-label
  size?: 'sm' | 'md';     // sm = p-1.5, md = p-2 (default)
}
```

Classes (`md`):
```
p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100
focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1
disabled:opacity-25 disabled:cursor-not-allowed
transition-colors duration-150
```

ARIA: `aria-label={label}`. Children must be an icon SVG with `aria-hidden="true"`.  
Touch target: 44×44 px minimum — the `p-2` padding on a `w-5 h-5` icon achieves 36px; wrap in a `min-w-[44px] min-h-[44px] flex items-center justify-center` span if needed on mobile.

Usage:
```tsx
<IconButton label="Delete instrument" onClick={handleDelete}>
  <TrashIcon className="w-4 h-4" aria-hidden />
</IconButton>
```

---

#### C.2 Form Controls

**File:** `resources/js/Components/TextInput.tsx`  
Extend existing Breeze file — replace class string only.

```typescript
interface Props extends React.InputHTMLAttributes<HTMLInputElement> {
  isFocused?: boolean;
  hasError?: boolean;
}
```

Classes (base):
```
form-input
```

Error modifier (applied when `hasError` is true, appended):
```
border-red-500 focus:border-red-500 focus:ring-red-500
```

ARIA: caller must link to `InputError` via `aria-describedby={errorId}` when `hasError`.

---

**File:** `resources/js/Components/InputLabel.tsx`  
Keep existing Breeze file, replace class string:

```
form-label
```

If field is required, render a `<span aria-hidden="true" className="text-red-500 ml-0.5">*</span>` after the label text.

---

**File:** `resources/js/Components/InputError.tsx`  
Keep existing structure. Class string:
```
text-sm text-red-600 mt-1
```
Add `role="alert"` and `aria-live="polite"` on the wrapping element so screen readers announce the error on appearance.

---

**File:** `resources/js/Components/Checkbox.tsx`  
Replace class string:
```
rounded border-gray-300 text-primary shadow-sm
focus:ring-primary focus:ring-offset-1
disabled:opacity-50 disabled:cursor-not-allowed
```

---

**File:** `resources/js/Components/Select.tsx` (new — Breeze has no Select)

```typescript
interface Props extends React.SelectHTMLAttributes<HTMLSelectElement> {
  hasError?: boolean;
}
```

Classes:
```
form-select
```

Error modifier when `hasError`:
```
border-red-500 focus:border-red-500 focus:ring-red-500
```

Usage:
```tsx
<Select id="regulator" value={form.data.regulator_id} onChange={...}>
  <option value="">Select regulator…</option>
  {regulators.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
</Select>
```

---

**File:** `resources/js/Components/Textarea.tsx` (new — Breeze has no Textarea)

```typescript
interface Props extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  hasError?: boolean;
}
```

Classes:
```
form-input resize-y min-h-[80px]
```

Error modifier same as `TextInput`.

---

#### C.3 Card & StatCard

**File:** `resources/js/Components/Card.tsx` (new)

```typescript
interface Props {
  children: React.ReactNode;
  className?: string;
  padding?: 'none' | 'sm' | 'md' | 'lg';  // default 'lg' = p-6
  hover?: boolean;   // default false — suppress hover lift for non-clickable cards
}
```

Padding map: `none` → `''`, `sm` → `p-3`, `md` → `p-4`, `lg` → `p-6`.  
When `hover={false}`, append `hover:shadow-none hover:translate-y-0` to cancel `.card` hover.

Card subcomponents:
- `Card.Header` — `className="px-6 py-4 border-b border-gray-100"`
- `Card.Body` — `className="px-6 py-4"`

Usage:
```tsx
<Card>
  <Card.Header><h2 className="text-lg font-semibold">Instruments</h2></Card.Header>
  <Card.Body>…</Card.Body>
</Card>
```

---

**File:** `resources/js/Components/StatCard.tsx` (new)

```typescript
interface Props {
  title: string;
  value: number | string;
  icon: React.ReactNode;       // Heroicon, w-5 h-5
  color: 'blue' | 'red' | 'green' | 'amber' | 'purple' | 'teal';
  trend?: { value: string; direction: 'up' | 'down' | 'neutral' };
  className?: string;
}
```

Color-to-class map:
```
blue:   bg-blue-100 text-blue-600
red:    bg-red-100 text-red-600
green:  bg-green-100 text-green-600
amber:  bg-amber-100 text-amber-600
purple: bg-purple-100 text-purple-600
teal:   bg-teal-100 text-teal-600
```

Layout classes:
```
stat-card flex items-center justify-between
```

Left block:
```
Title:  text-sm font-medium text-gray-500
Value:  text-2xl font-bold mt-1 text-text-primary
Trend:  mt-3 flex items-center gap-1 text-xs
  up:   text-green-600
  down: text-red-600
  neutral: text-gray-500
```

Right block (icon box):
```
w-10 h-10 rounded-lg flex items-center justify-center {colorClasses}
```

ARIA: `<article aria-label="{title}: {value}">`.

---

#### C.4 StatusBadge

**File:** `resources/js/Components/StatusBadge.tsx` (new)

```typescript
type StatusVariant =
  | 'critical' | 'high' | 'medium' | 'low'
  | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

interface Props {
  variant: StatusVariant;
  label?: string;    // if omitted, renders a title-cased version of variant
  dot?: boolean;     // prepend colored dot (RatingBadge pattern) — default false
  size?: 'sm' | 'md'; // sm = text-[10px], md = text-xs (default)
}
```

Variant-to-class map:
```
critical:  bg-red-100    text-red-800    border-red-200
high:      bg-orange-100 text-orange-800 border-orange-200
medium:    bg-yellow-100 text-yellow-800 border-yellow-200
low:       bg-green-100  text-green-800  border-green-200
info:      bg-blue-100   text-blue-700   border-blue-200
completed: bg-purple-100 text-purple-700 border-purple-200
draft:     bg-gray-100   text-gray-700   border-gray-200
overdue:   bg-red-100    text-red-700    border-red-200
ai:        bg-violet-100 text-violet-700 border-violet-200
```

Base classes:
```
badge border
```

Dot (when `dot=true`): `<span className="w-1.5 h-1.5 rounded-full mr-1.5 {dotColor}" aria-hidden />`.  
Dot colors: critical/overdue → `bg-red-500`, high → `bg-orange-500`, medium → `bg-yellow-500`, low → `bg-green-500`.

ARIA: `<span role="status">` for live data; plain `<span>` for static labels.

Usage:
```tsx
<StatusBadge variant="high" />
<StatusBadge variant="completed" label="Resolved" />
<StatusBadge variant="medium" dot />
```

---

#### C.5 PageHeader

**File:** `resources/js/Components/PageHeader.tsx` (new)

```typescript
interface BreadcrumbItem {
  label: string;
  href?: string;   // if absent, renders as plain text (current page)
}

interface Props {
  title: string;
  subtitle?: string;
  breadcrumb?: BreadcrumbItem[];
  actions?: React.ReactNode;   // PrimaryButton / SecondaryButton row
}
```

Structure classes:
```
Container:    page-header
Breadcrumb:   flex items-center gap-1.5 text-sm text-gray-500 mb-1
  Link:       hover:text-gray-700 transition-colors
  Separator:  text-gray-300 select-none (›)
  Current:    text-gray-700 font-medium (no href)
Title:        page-title
Subtitle:     text-sm text-text-secondary mt-1
Actions:      flex items-center gap-3 flex-shrink-0
```

ARIA: `<nav aria-label="Breadcrumb">` around breadcrumb; title rendered as `<h1>`.

Usage:
```tsx
<PageHeader
  title="Instruments Register"
  subtitle="All regulatory instruments and their linked obligations"
  breadcrumb={[{ label: 'Library', href: '/instruments' }, { label: 'Instruments' }]}
  actions={<PrimaryButton>Import XLSX</PrimaryButton>}
/>
```

---

#### C.6 FilterBar

**File:** `resources/js/Components/FilterBar.tsx` (new)

```typescript
interface FilterConfig {
  id: string;
  label: string;
  type: 'text' | 'select' | 'date';
  placeholder?: string;
  options?: { value: string; label: string }[];  // for type='select'
  flex?: number;   // CSS flex-grow, default 1
}

interface Props {
  filters: FilterConfig[];
  values: Record<string, string>;
  onChange: (id: string, value: string) => void;
  onReset: () => void;
  isActive: boolean;   // true when any value !== ''
}
```

Structure:
```
filter-bar
  flex flex-wrap items-end gap-4
    per filter:
      filter-group (flex={config.flex})
        <label className="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
        <input|select className="w-full rounded-lg border-0 bg-white shadow-sm
          ring-1 ring-gray-200 focus:ring-2 focus:ring-primary
          text-sm py-2.5 px-3 text-gray-700 placeholder-gray-400">
    Reset button (only when isActive):
      px-5 py-2.5 text-sm font-semibold text-gray-600
      bg-white rounded-lg ring-1 ring-gray-200
      hover:bg-gray-100 hover:text-gray-800
      self-end
```

ARIA: each `<label>` has `htmlFor` matching the `<input>` id. Reset button: `aria-label="Clear all filters"`.  
Keyboard: Tab between filters; Escape in any filter input calls `onReset`.

---

#### C.7 DataTable

**File:** `resources/js/Components/DataTable.tsx` (new — this is the core workhorse)

```typescript
interface Column<T> {
  key: keyof T | string;
  header: string;
  sortable?: boolean;
  width?: string;           // e.g. 'w-24'
  render?: (row: T) => React.ReactNode;
}

interface Props<T extends { id: number | string }> {
  columns: Column<T>[];
  data: T[];
  sortKey?: string;
  sortDir?: 'asc' | 'desc';
  onSort?: (key: string) => void;
  isLoading?: boolean;
  emptyState?: React.ReactNode;   // pass <EmptyState> component
  rowKey?: (row: T) => string;    // default: row.id.toString()
  onRowClick?: (row: T) => void;
  stickyHeader?: boolean;         // default false
}
```

Structure:
```
overflow-x-auto
  <table className="data-table" role="table">
    <thead>
      <tr>
        {columns.map col =>
          <th scope="col" aria-sort={sortable ? (active ? dir : 'none') : undefined}>
            sortable ? (
              <button className="flex items-center gap-1 group w-full text-left
                                 hover:text-gray-700 transition-colors"
                      onClick={() => onSort(col.key)}>
                {col.header}
                <SortIcon className="w-3 h-3 text-gray-300 group-hover:text-gray-500" />
              </button>
            ) : col.header
          }
      </tr>
    </thead>
    <tbody>
      {isLoading ? <SkeletonRows count={5} cols={columns.length} /> :
       data.length === 0 ? <tr><td colSpan={columns.length}>{emptyState}</td></tr> :
       data.map row => (
         <tr key={rowKey(row)}
             className={onRowClick ? 'cursor-pointer' : ''}
             onClick={onRowClick ? () => onRowClick(row) : undefined}
             tabIndex={onRowClick ? 0 : undefined}
             onKeyDown={onRowClick ? e => e.key==='Enter' && onRowClick(row) : undefined}
             role={onRowClick ? 'button' : undefined}>
           {columns.map col => <td key={col.key}>{col.render ? col.render(row) : row[col.key]}</td>}
         </tr>
       )
      }
    </tbody>
  </table>
```

`SkeletonRows`: each cell renders `<div className="h-4 bg-gray-200 rounded animate-pulse" />`.

ARIA on sortable headers: `aria-sort="ascending" | "descending" | "none"`.  
Keyboard: when `onRowClick` is present, `tr` is `role="button"` with `tabIndex={0}` — Enter activates.

---

#### C.8 Pagination

**File:** `resources/js/Components/Pagination.tsx` (new)

```typescript
interface PaginationLink {
  url: string | null;
  label: string;   // Laravel's paginator outputs HTML entities — sanitize to text
  active: boolean;
}

interface Props {
  links: PaginationLink[];
  from: number;
  to: number;
  total: number;
}
```

Structure:
```
<nav aria-label="Pagination" className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
  <span className="text-sm text-text-secondary">
    Showing {from}–{to} of {total}
  </span>
  <div className="flex items-center gap-1">
    {links.map link =>
      link.url === null
        ? <span className="px-3 py-1.5 text-sm text-gray-300 cursor-not-allowed">{sanitizedLabel}</span>
        : link.active
          ? <span className="px-3 py-1.5 text-sm font-semibold rounded-md
                             bg-primary text-white" aria-current="page">{sanitizedLabel}</span>
          : <Link href={link.url} className="px-3 py-1.5 text-sm rounded-md
                                             text-gray-600 hover:bg-gray-100
                                             focus:outline-none focus:ring-2 focus:ring-primary"
                  preserveScroll>{sanitizedLabel}</Link>
    }
  </div>
</nav>
```

Laravel paginator `label` values include `&laquo; Previous` and `Next &raquo;` — strip HTML entities and replace with `←` / `→` for display; use the raw labels as `aria-label` for screen readers.

---

#### C.9 EmptyState

**File:** `resources/js/Components/EmptyState.tsx` (new)

```typescript
interface Props {
  icon: React.ReactNode;   // Heroicon, w-8 h-8 text-gray-400
  title: string;
  description: string;
  action?: React.ReactNode;   // PrimaryButton or Link
}
```

Classes:
```
text-center py-12 px-6
  Icon wrapper: inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4
  Title:        text-sm font-semibold text-gray-800 mb-1
  Description:  text-sm text-gray-500 mb-4 max-w-sm mx-auto
  Action:       mt-4 (wraps whatever ReactNode is passed)
```

Usage:
```tsx
<EmptyState
  icon={<DocumentTextIcon className="w-8 h-8 text-gray-400" aria-hidden />}
  title="No instruments yet"
  description="Import an XLSX file or add an instrument manually."
  action={<PrimaryButton>Import XLSX</PrimaryButton>}
/>
```

---

#### C.10 FlashNotification

**File:** `resources/js/Components/FlashNotification.tsx` (new)

```typescript
interface Flash {
  success?: string;
  error?: string;
  warning?: string;
  info?: string;
}

interface Props {
  flash: Flash;   // from Inertia usePage().props
}
```

Reads `usePage().props.flash` internally. Renders active toasts as a stack.  
Auto-dismiss after 5 000 ms using `setTimeout`. Manual dismiss via X button.

Container:
```
fixed top-20 right-4 z-50 flex flex-col gap-3 pointer-events-none
```

Toast:
```
w-80 rounded-lg border shadow-lg overflow-hidden pointer-events-auto
```

Variant classes:
```
success: bg-green-50 border-green-200  | icon text-green-600  | title text-green-800
error:   bg-red-50   border-red-200    | icon text-red-600    | title text-red-800
warning: bg-amber-50 border-amber-200  | icon text-amber-600  | title text-amber-800
info:    bg-blue-50  border-blue-200   | icon text-blue-600   | title text-blue-800
```

Content:
```
p-4 flex items-start gap-3
  Icon:    flex-shrink-0 mt-0.5 w-5 h-5 (Heroicon: CheckCircle/XCircle/ExclamationTriangle/InformationCircle)
  Body:
    Title:   text-sm font-semibold
    Message: text-xs mt-0.5 text-gray-600
  Dismiss: ml-auto flex-shrink-0 p-1 rounded hover:bg-black/5
           aria-label="Dismiss notification"
```

Progress bar (countdown):
```
h-1 w-full transition-all duration-100 ease-linear
success: bg-green-400 | error: bg-red-400 | warning: bg-amber-400 | info: bg-blue-400
```

ARIA: toast container has `role="region" aria-label="Notifications" aria-live="polite"`.

Icon choices (Heroicons outline, `w-5 h-5`): `CheckCircleIcon`, `XCircleIcon`, `ExclamationTriangleIcon`, `InformationCircleIcon`.

---

### Priority 2 — Build If Time Permits

---

#### C.11 Modal & ConfirmDialog

**File:** `resources/js/Components/Modal.tsx`  
Existing Breeze Modal uses Headless UI — keep the transition logic, update visual classes only:

Panel classes (replace existing `DialogPanel` className):
```
mb-6 transform overflow-hidden rounded-xl bg-white shadow-xl
transition-all sm:mx-auto sm:w-full {maxWidthClass}
```

`maxWidth` options: `sm` / `md` / `lg` / `xl` / `2xl`.  
Focus trap: Headless UI `Dialog` handles this automatically.  
Close on Escape: Headless UI handles this automatically.  
Close on backdrop click: existing `closeable` prop.

---

**File:** `resources/js/Components/ConfirmDialog.tsx` (new)

```typescript
interface Props {
  show: boolean;
  onClose: () => void;
  onConfirm: () => void;
  variant?: 'danger' | 'warning' | 'info';
  title: string;
  message: string;
  confirmLabel?: string;   // default 'Confirm'
  cancelLabel?: string;    // default 'Cancel'
  isLoading?: boolean;
}
```

Header icon box:
```
danger:  bg-red-100    icon: ExclamationTriangleIcon text-red-600
warning: bg-yellow-100 icon: ExclamationTriangleIcon text-yellow-600
info:    bg-blue-100   icon: InformationCircleIcon   text-blue-600
```

Layout:
```
<Modal show={show} maxWidth="md" onClose={onClose}>
  <div className="p-6">
    Header: flex items-center gap-3 mb-4
      Icon box: w-10 h-10 rounded-full flex items-center justify-center
      Title: text-lg font-semibold text-gray-900
    Message: text-sm text-gray-500 mb-6
    Footer: flex justify-end gap-3
      <SecondaryButton onClick={onClose}>{cancelLabel}</SecondaryButton>
      {variant==='danger' ? <DangerButton> : <PrimaryButton>}{confirmLabel}
  </div>
</Modal>
```

ARIA: `aria-describedby` linking message paragraph to `Dialog`.

---

#### C.12 Dropdown & NavLink

**File:** `resources/js/Components/Dropdown.tsx`  
Existing Breeze Dropdown is functionally correct. Visual changes only:

Panel classes:
```
absolute z-50 mt-2 rounded-lg bg-white shadow-xl
ring-1 ring-black ring-opacity-5 py-1 focus:outline-none
```

Width variants: `w-48` (default), `w-56` (user menu), `w-72` (notification panel).

`Dropdown.Link` classes:
```
block w-full px-4 py-2 text-start text-sm text-gray-700
hover:bg-gray-50 focus:bg-gray-50 focus:outline-none
```

---

**File:** `resources/js/Components/NavLink.tsx`  
Keep existing — used in GuestLayout horizontal navigation.

---

#### C.13 Charts (Dashboard M18 — defer SVG until data is available)

**File:** `resources/js/Components/DonutChart.tsx` (new, Priority 2)

```typescript
interface Segment {
  label: string;
  value: number;
  color: string;    // Tailwind hex or CSS color
}

interface Props {
  segments: Segment[];
  total?: number;   // if omitted, computed from segments
  centerLabel?: string;
  size?: number;    // SVG viewBox size, default 120
}
```

For MVP, if the full SVG donut is too complex to implement, substitute a `<Card>` containing a `<ul>` of colored legend rows with a `ProgressBar` per row. Flag this as a temporary degraded fallback.

---

**File:** `resources/js/Components/ProgressBar.tsx` (new, Priority 2)

```typescript
interface Props {
  value: number;    // 0–100
  color?: 'green' | 'blue' | 'red' | 'amber';   // default 'green'
  showLabel?: boolean;
  label?: string;   // e.g. "68% compliant"
}
```

Color map for fill:
```
green: bg-secondary
blue:  bg-blue-500
red:   bg-red-500
amber: bg-amber-500
```

ARIA: `<div role="progressbar" aria-valuenow={value} aria-valuemin={0} aria-valuemax={100} aria-label={label}>`.

---

## Section D — Layout Specs

### D.1 `AuthenticatedLayout.tsx` — Full Replacement

**File:** `resources/js/Layouts/AuthenticatedLayout.tsx`  
The existing Breeze layout is a horizontal topbar-only layout. Replace entirely with a left-sidebar + topbar shell.

```typescript
interface Props {
  children: React.ReactNode;
  header?: React.ReactNode;   // Kept for backward-compat with Breeze pages — render in main content area
}
```

**Shell structure (ASCII):**

```
┌─────────────────────────────────────────────────────┐
│  .sidebar (260px fixed left, bg-primary, z-40)      │
│  ┌──────────────────────────────────────────────┐   │
│  │  Logo row  h-16  px-6                        │   │
│  │  ┌──  SidebarLogo: text-white font-bold  ──┐ │   │
│  ├──────────────────────────────────────────────┤   │
│  │  <nav aria-label="Main navigation">          │   │
│  │    Section label: MAIN MENU                  │   │
│  │    NavItem: Dashboard                        │   │
│  │    NavItem: Library                          │   │
│  │    NavItem: Obligations                      │   │
│  │    NavItem: Sanctions KB                     │   │
│  │    NavItem: Calendar                         │   │
│  │    ─────────────────────────────────────     │   │
│  │    Section label: ACCOUNT                    │   │
│  │    NavItem: Profile                          │   │
│  ├──────────────────────────────────────────────┤   │
│  │  Collapse toggle  (bottom, mx-2)             │   │
│  └──────────────────────────────────────────────┘   │
│                                                     │
│  Main wrapper: ml-[260px] flex flex-col min-h-screen│
│  ┌──────────────────────────────────────────────┐   │
│  │  TopBar  h-16  sticky top-0 z-30             │   │
│  │  bg-white border-b border-gray-200           │   │
│  │  shadow: var(--shadow-topbar)                │   │
│  │  Left: HamburgerButton (mobile only)         │   │
│  │  Right: SearchIcon · BellIcon · UserDropdown │   │
│  ├──────────────────────────────────────────────┤   │
│  │  <main className="flex-1 p-6 bg-bg">         │   │
│  │    {children}                                │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

**Sidebar nav items (5 MVP modules + profile):**

| Label | Heroicon (outline) | Route name |
|---|---|---|
| Dashboard | `Squares2X2Icon` | `dashboard` |
| Library | `BookOpenIcon` | `instruments.index` |
| Obligations | `ClipboardDocumentListIcon` | `obligations.index` |
| Sanctions KB | `ShieldExclamationIcon` | `sanctions.index` |
| Calendar | `CalendarDaysIcon` | `calendar.index` |
| Profile | `UserCircleIcon` | `profile.edit` |

Section dividers are plain `<div className="mx-4 my-3 border-t border-white/10">` with an optional section label above using `text-[10px] font-semibold text-white/40 uppercase tracking-wider px-4 mb-1`.

**State classes for each nav item:** use `.sidebar-nav-item` + `.active` (from Section B).

**Collapsed state** (`sidebarCollapsed` state variable):
- Sidebar width: `var(--sidebar-collapsed-width)` (72px)
- Hide all label text: `hidden` on label spans
- Hide section labels entirely
- Icon remains centered: remove `gap-3`, icon `mx-auto`
- Logo area shows icon-only mark (or initials)
- Main wrapper margin: `ml-[72px]`

**Mobile behavior:**
- Sidebar hidden off-screen by default: `lg:-translate-x-0 -translate-x-full`
- Mobile overlay backdrop: `fixed inset-0 bg-black/50 z-30 lg:hidden` toggled by hamburger
- Sidebar has `z-40` and is above backdrop
- Hamburger in TopBar: `Bars3Icon` → `XMarkIcon` toggle

**Responsive main wrapper:**
```
ml-0 lg:ml-[var(--sidebar-width)] flex flex-col min-h-screen
transition-all duration-300
```
(When collapsed at lg+: `lg:ml-[var(--sidebar-collapsed-width)]`.)

---

### D.2 `GuestLayout.tsx` — Visual Update Only

**File:** `resources/js/Layouts/GuestLayout.tsx`  
Keep existing structure. Apply these visual changes:

Outer container (replace `bg-gray-100`):
```
flex min-h-screen flex-col items-center bg-bg pt-6 sm:justify-center sm:pt-0
```

Logo link: render the product name "Atheris Compliance" in `text-primary font-bold text-xl` instead of the ApplicationLogo SVG (or keep SVG — either is acceptable).

Auth card (replace existing `mt-6` div):
```
mt-6 w-full overflow-hidden bg-white px-6 py-8 shadow-card sm:max-w-md rounded-xl border border-gray-100
```

Card heading pattern (used inside Login/Register pages):
```
<h1 className="text-xl font-bold text-text-primary mb-1">Sign in</h1>
<p className="text-sm text-text-secondary mb-6">Enter your credentials to continue</p>
```

---

### D.3 `ExternalLayout.tsx` — Deferred

**File:** `resources/js/Layouts/ExternalLayout.tsx`  
Deferred to Phase 2 (regulator portal). Do not create. When Phase 2 starts, this layout shares the `.card` surface on a `bg-bg` background with minimal chrome — no sidebar, no topbar user menu. Spec separately.

---

## Section E — Page Composition Recipes

### E.1 M18 Dashboard

**Page:** `resources/js/Pages/Dashboard.tsx`  
**Route:** `dashboard`  
**Layout:** `AuthenticatedLayout`

```
PageHeader
  title="Dashboard"
  subtitle="Compliance overview — {current month}"
  actions=<SecondaryButton icon=<ArrowPathIcon>> Refresh</SecondaryButton>

KPI grid: grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6
  StatCard title="Total Instruments" icon=BookOpenIcon     color="blue"
  StatCard title="Open Obligations"  icon=ClipboardDocumentListIcon color="amber"
  StatCard title="Sanctions Entries" icon=ShieldExclamationIcon     color="red"
  StatCard title="Upcoming Deadlines" icon=CalendarDaysIcon color="purple"

Widget grid: grid grid-cols-1 lg:grid-cols-2 gap-6
  Card: Obligations by Risk Rating    → DonutChart (Priority 2) or ProgressBar list (MVP fallback)
  Card: Instruments by Regulator      → DataTable (top 10, no pagination)
  Card: Obligations by Nature         → DataTable (top 10, no pagination)
  Card: Calendar — Next 7 Days        → DataTable of upcoming due dates

Empty state (no data at all):
  EmptyState icon=Squares2X2Icon
             title="No data yet"
             description="Import instruments and link obligations to populate the dashboard."
             action=<Link href="/instruments">Go to Library</Link>
```

Inertia props contract: `{ stats: { instruments, obligations, sanctions, deadlines }, byRegulator: [], byNature: [], byRisk: [], upcoming: [] }`

---

### E.2 M01 Library — Instruments Index

**Page:** `resources/js/Pages/Instruments/Index.tsx`  
**Route:** `instruments.index`  
**Layout:** `AuthenticatedLayout`

```
PageHeader
  breadcrumb=[{ label:'Library' }]
  title="Instruments Register"
  subtitle="Regulatory instruments grouped by regulator"
  actions=<>
    <SecondaryButton icon=<ArrowUpTrayIcon>>Import XLSX</SecondaryButton>
    <PrimaryButton   icon=<PlusIcon>       >Add Instrument</PrimaryButton>
  </>

FilterBar filters=[
  { id:'search',     label:'Search',     type:'text',   placeholder:'Title, reference…', flex:2 },
  { id:'regulator',  label:'Regulator',  type:'select', options:regulators },
  { id:'item_type',  label:'Type',       type:'select', options:itemTypes },
  { id:'risk_rating',label:'Risk',       type:'select', options:riskRatings },
]

Card (hover=false)
  DataTable columns=[
    { key:'reference', header:'Reference', sortable:true, width:'w-28', render: monospace span },
    { key:'title',     header:'Title',     sortable:true },
    { key:'regulator', header:'Regulator', sortable:true },
    { key:'item_type', header:'Type',      sortable:true, render: StatusBadge },
    { key:'risk_rating',header:'Risk',     sortable:true, render: StatusBadge variant from risk },
    { key:'effective_date', header:'Effective', sortable:true },
    { key:'actions',   header:'',         render: IconButton row (View/Edit/Delete) },
  ]
  emptyState=<EmptyState icon=BookOpenIcon
                          title="No instruments found"
                          description="No instruments match your filters, or none have been added yet."
                          action=<PrimaryButton>Add Instrument</PrimaryButton>>

Pagination links={links} from={from} to={to} total={total}
```

Inertia props contract: `{ instruments: PaginatedResource<Instrument>, filters: FilterValues, regulators: [], itemTypes: [], riskRatings: [] }`

---

### E.3 M03 Obligations Index

**Page:** `resources/js/Pages/Obligations/Index.tsx`  
**Route:** `obligations.index`  
**Layout:** `AuthenticatedLayout`

```
PageHeader
  breadcrumb=[{ label:'Obligations' }]
  title="Obligations Register"
  subtitle="Compliance obligations linked to regulatory instruments"
  actions=<PrimaryButton icon=<PlusIcon>>Add Obligation</PrimaryButton>

FilterBar filters=[
  { id:'search',     label:'Search',    type:'text',   placeholder:'Obligation text…', flex:2 },
  { id:'instrument', label:'Instrument',type:'select', options:instruments },
  { id:'nature',     label:'Nature',    type:'select', options:natures },
  { id:'status',     label:'Status',    type:'select', options:statuses },
  { id:'due_before', label:'Due Before',type:'date' },
]

Card (hover=false)
  DataTable columns=[
    { key:'ref_code',    header:'Ref',        sortable:true, render: monospace },
    { key:'description', header:'Obligation', sortable:false },
    { key:'instrument',  header:'Instrument', sortable:true },
    { key:'nature',      header:'Nature',     sortable:true, render: StatusBadge },
    { key:'due_date',    header:'Due',        sortable:true },
    { key:'status',      header:'Status',     sortable:true, render: StatusBadge },
    { key:'actions',     header:'',           render: IconButton row },
  ]
  emptyState=<EmptyState icon=ClipboardDocumentListIcon
                          title="No obligations found"
                          description="Add obligations manually or link them from an instrument.">

Pagination
```

Inertia props contract: `{ obligations: PaginatedResource<Obligation>, filters: FilterValues, instruments: [], natures: [], statuses: [] }`

---

### E.4 M28 Sanctions KB

**Page:** `resources/js/Pages/Sanctions/Index.tsx`  
**Route:** `sanctions.index`  
**Layout:** `AuthenticatedLayout`

```
PageHeader
  breadcrumb=[{ label:'Sanctions KB' }]
  title="Sanctions Knowledge Base"
  subtitle="417 sanction entries — full-text searchable"
  actions=<SecondaryButton icon=<ArrowDownTrayIcon>>Export</SecondaryButton>

FilterBar filters=[
  { id:'search',   label:'Full-text search', type:'text', placeholder:'Name, jurisdiction, provision…', flex:3 },
  { id:'category', label:'Category',         type:'select', options:categories },
  { id:'severity', label:'Severity',         type:'select', options:severities },
]

Exposure summary bar (above table — only when search has results):
  Card padding='sm' hover=false
    flex items-center gap-6 text-sm
      "X matches" in font-semibold
      StatusBadge variant per highest severity found

Card (hover=false)
  DataTable columns=[
    { key:'entry_no',     header:'#',           width:'w-12', render: monospace },
    { key:'category',     header:'Category',    sortable:true },
    { key:'provision',    header:'Provision',   sortable:false },
    { key:'description',  header:'Description', render: truncated to 120 chars },
    { key:'jurisdiction', header:'Jurisdiction',sortable:true },
    { key:'severity',     header:'Severity',    sortable:true, render: StatusBadge },
    { key:'actions',      header:'',            render: IconButton (View) },
  ]
  emptyState=<EmptyState icon=ShieldExclamationIcon
                          title="No entries match your search"
                          description="Try different keywords or clear the filters.">

Pagination
```

Inertia props contract: `{ sanctions: PaginatedResource<Sanction>, filters: FilterValues, categories: [], severities: [], exposureSummary: { count, highestSeverity } }`

---

### E.5 M17 Compliance Calendar

**Page:** `resources/js/Pages/Calendar/Index.tsx`  
**Route:** `calendar.index`  
**Layout:** `AuthenticatedLayout`

```
PageHeader
  breadcrumb=[{ label:'Calendar' }]
  title="Compliance Calendar"
  subtitle="{month year}"
  actions=<>
    <SecondaryButton onClick=prevMonth><ChevronLeftIcon/></SecondaryButton>
    <SecondaryButton onClick=today>Today</SecondaryButton>
    <SecondaryButton onClick=nextMonth><ChevronRightIcon/></SecondaryButton>
    ViewToggle: [ Month | List ] (two SecondaryButtons, active one uses primary style)
  </>

View: Month
  7-column grid (Su Mo Tu We Th Fr Sa)
  Each day cell: min-h-[80px] rounded-lg border border-gray-100 p-1
    Day number: text-xs font-semibold text-gray-500 (today: bg-primary text-white rounded-full w-6 h-6)
    Due-date chips: StatusBadge (overdue/medium/low) truncated to 2 per cell + "+N more" link

View: List (simpler, MVP default if calendar grid is complex)
  DataTable columns=[
    { key:'due_date',    header:'Due Date',   sortable:true },
    { key:'obligation',  header:'Obligation', sortable:false },
    { key:'instrument',  header:'Instrument', sortable:true },
    { key:'status',      header:'Status',     sortable:true, render: StatusBadge },
    { key:'days_until',  header:'Days',       sortable:true, render: colored span (red<7, amber<30, green≥30) },
  ]

emptyState=<EmptyState icon=CalendarDaysIcon
                        title="No due dates this period"
                        description="Obligations with due dates will appear here.">
```

Inertia props contract: `{ events: CalendarEvent[], month: number, year: number, viewMode: 'month'|'list' }`

> **Implementation note for frontend-engineer:** Ship the List view first. The Month grid view is visually complex; it can be a follow-up. `viewMode` preference should persist in `localStorage`, not Inertia state.

---

## Section F — Accessibility Requirements

**Target:** WCAG 2.1 Level AA.

### F.1 Color Contrast — Verify These Ratios

| Foreground | Background | Expected Ratio | Minimum |
|---|---|---|---|
| `--color-text-primary` (#2D3748) on `--color-bg` (#F7FAFC) | ≥ 9.4:1 | 4.5:1 |
| `--color-text-primary` (#2D3748) on `--color-card` (#FFFFFF) | ≥ 9.7:1 | 4.5:1 |
| White (#FFF) on `--color-primary` (#1A365D) | ≥ 10.5:1 | 4.5:1 |
| `text-gray-500` (#718096) on `--color-bg` | ≈ 4.6:1 | 4.5:1 — **marginal, verify** |
| `text-red-800` on `bg-red-100` (StatusBadge critical) | ≥ 5.9:1 | 4.5:1 |
| `text-yellow-800` on `bg-yellow-100` (StatusBadge medium) | ≈ 5.2:1 | 4.5:1 |
| `--color-accent` (#D4AF37) on `--color-primary` (#1A365D) | ≥ 3.2:1 | 3:1 (UI component) |

Use [https://webaim.org/resources/contrastchecker/](https://webaim.org/resources/contrastchecker/) to verify.

### F.2 Focus Indicators

- Every interactive element must have `focus:ring-2 focus:ring-primary focus:ring-offset-2`.
- Never use `outline: none` or `focus:outline-none` without the ring replacement.
- Sidebar nav items on dark background: `focus:ring-2 focus:ring-accent focus:ring-offset-primary`.
- Focus ring contrast against adjacent background must be ≥ 3:1.

### F.3 Keyboard-Only Operation

- All buttons, links, and form controls reachable by Tab.
- Modal focus trap: Headless UI Dialog handles this — do not remove.
- Dropdown closes on Escape: Headless UI handles this.
- DataTable sortable column headers are `<button>` elements (not divs).
- Clickable table rows have `tabIndex={0}` and respond to Enter.
- FilterBar Escape key clears all filters.
- Sidebar keyboard navigation: sidebar nav items are `<a>` or `<button>` — not `<div>`.

### F.4 ARIA Conventions

- `<nav aria-label="Main navigation">` for sidebar.
- `<nav aria-label="Breadcrumb">` for PageHeader breadcrumb.
- `<nav aria-label="Pagination">` for Pagination.
- `<main>` wraps the primary content area.
- `role="status"` on StatusBadge when used in live-updating contexts.
- `aria-live="polite"` on FlashNotification container and InputError wrappers.
- `aria-busy="true"` on DataTable container while `isLoading`.
- All icon-only buttons have `aria-label` (IconButton enforces this via required prop).
- Images and decorative icons use `aria-hidden="true"`.

### F.5 Form Requirements

- Every `<input>`, `<select>`, `<textarea>` has an associated `<InputLabel>` via `htmlFor`.
- Placeholder text is supplemental, never the sole label.
- Error messages use `InputError` with `aria-live="polite"`.
- Required fields are marked with `aria-required="true"` and a visual asterisk.

---

## Section G — Explicitly Deferred (Do Not Build for MVP)

| Item | Reason / Phase |
|---|---|
| Dark mode | Phase 2. No token flip strategy defined yet. Do not add `dark:` classes speculatively. |
| Storybook / component catalog | Post-MVP developer tooling. |
| `AiAssistantPanel` | Phase 2 — AI integration not contracted for MVP. |
| `AiReportGenerator` | Phase 2. |
| `DuplicateFindingWarning` | Phase 2 workbench component. |
| `GenerateExternalLinkModal` | Phase 2 — requires external portal. |
| `RegulatoryReferenceSuggester` | Phase 2 AI feature. |
| `ExternalLayout` | Phase 2 regulator portal. |
| Full ECharts/Recharts dashboard widgets | Use `ProgressBar` list fallback for MVP. Proper SVG charts in Phase 2. |
| M18 Dashboard pivot tabs (Regulator / Nature / Item Type / Risk) | MVP shows static top-10 tables per widget. Tab switching deferred. |
| Calendar month-grid view | List view ships first. Month grid is Phase 1.5. |
| Notification bell with live badge count | TopBar renders the bell icon but with no live badge for MVP — wire up later. |
| User roles / permissions UI | Backend RBAC not modeled yet — do not add role-gated UI. |
| Bulk-select checkboxes in DataTable | Needed for bulk-delete/export; deferred to post-MVP. |
| `RatingBadge` as separate component | Subsumed by `StatusBadge` with `dot` prop. |
| `ProgressRing` SVG component | Priority 2 chart — only if time permits. |
| Offline indicator panel | Phase 2. |
| Print / PDF export styling | Phase 2. |

---

## Handoff to Frontend Engineer

### Components ready to build (top-down priority order)

1. Design tokens — paste `@theme` block into `app.css` (Section A)
2. Component CSS classes — paste `@layer components` block into `app.css` (Section B)
3. `PrimaryButton`, `SecondaryButton`, `DangerButton`, `IconButton` (replace Breeze files)
4. `TextInput`, `InputLabel`, `InputError`, `Checkbox` (update Breeze files) + `Select`, `Textarea` (new)
5. `Card`, `StatCard`
6. `StatusBadge`
7. `PageHeader`
8. `FilterBar`
9. `DataTable` + `EmptyState`
10. `Pagination`
11. `FlashNotification`
12. `AuthenticatedLayout` (full replacement — do this before building any pages)
13. `GuestLayout` (visual update only)
14. Pages in order: Dashboard → Library → Obligations → Sanctions KB → Calendar

### Tokens the frontend must add to its config

No `tailwind.config.js` exists (Tailwind v4 CSS-first). The `@theme` block in Section A is the entire token config — paste it into `app.css`. No other config file changes needed.

### External assets and licenses

| Asset | Source | License |
|---|---|---|
| Inter font | Google Fonts (already in `app.css` `@import`) | OFL-1.1 — free for commercial use |
| Roboto Mono font | Google Fonts (already in `app.css` `@import`) | Apache 2.0 — free for commercial use |
| Heroicons | `npm install @heroicons/react` | MIT |
| Headless UI | `npm install @headlessui/react` (already installed via Breeze) | MIT |

### Open questions (do not block implementation)

- `[NEEDS DECISION]` Application logo / wordmark: the spec uses "Atheris Compliance" text as the sidebar logo. If a proper SVG logo exists, supply it before building the sidebar.
- `[NEEDS DECISION]` `--color-accent` (#D4AF37 gold) on `--color-primary` (#1A365D) meets the 3:1 minimum for UI components but does not pass 4.5:1 for text. Active nav item label text is white (passes) — the accent is only the left border. Confirm this is acceptable or provide an alternative.
- `[NEEDS DECISION]` `text-gray-500` (#718096) on `--color-bg` is marginal at ≈4.6:1. Consider using `text-gray-600` (#4A5568) for secondary labels to add headroom. Backend/design sign-off needed before frontend locks it in.
- `[NEEDS DECISION]` Sanctions KB: 417 rows — decide default page size (25 / 50 / 100) and whether full-text search fires on-type (debounced) or on submit.
- `[NEEDS DECISION]` Calendar: confirm List view as the MVP default. Month grid can be deferred but a product decision is needed so frontend-engineer doesn't build it speculatively.
