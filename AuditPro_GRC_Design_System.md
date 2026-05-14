# AuditPro GRC — Design System

**Version:** 1.0
**Last Updated:** March 21, 2026
**Stack:** Laravel + Inertia.js + React (JSX) + Tailwind CSS

---

## 1. Design Principles

The AuditPro GRC interface is built on five core principles that guide every design decision:

**Professional Trust** — A deep navy palette and restrained color use project the authority and reliability expected of governance, risk, and compliance software.

**Clarity Over Decoration** — Information density is high in audit work. Every visual element earns its place by improving scanability, reducing cognitive load, or surfacing status at a glance.

**Consistent Rhythm** — A 4 px base spacing unit, a strict type scale, and reusable component primitives keep the interface predictable across dozens of modules and hundreds of pages.

**Progressive Disclosure** — Dashboards surface summaries; drill-down pages expose detail. Filters, modals, and expandable sections let users control how much they see.

**Accessible by Default** — Focus rings, semantic markup, sufficient contrast ratios, and touch-friendly targets ensure usability across devices and ability levels.

---

## 2. Color System

### 2.1 CSS Custom Properties (Source of Truth)

All brand colors are defined as CSS custom properties on `:root` in `resources/css/app.css` and referenced throughout the application with `var(--color-*)`.

| Token | Hex | Role |
|---|---|---|
| `--color-primary` | `#1A365D` | Dark navy. Sidebar, primary buttons, main brand identity. |
| `--color-primary-light` | `#2A4A7F` | Medium navy. Hover/active states on primary surfaces. |
| `--color-primary-dark` | `#0F2340` | Deep navy. High-contrast accents, deepest sidebar shade. |
| `--color-secondary` | `#2D7D46` | Forest green. Success states, positive indicators, Nigerian identity accent. |
| `--color-secondary-light` | `#38A169` | Bright green. Secondary hover states. |
| `--color-accent` | `#D4AF37` | Gold/amber. Active nav indicators, premium highlights, important badges. |
| `--color-accent-light` | `#E2C458` | Light gold. Hover variant of accent. |
| `--color-bg` | `#F7FAFC` | Very light blue-gray. Full-page backgrounds. |
| `--color-card` | `#FFFFFF` | Pure white. Card and surface backgrounds. |
| `--color-text-primary` | `#2D3748` | Charcoal. Body copy, headings. |
| `--color-text-secondary` | `#718096` | Slate gray. Labels, helper text, secondary copy. |
| `--color-error` | `#C53030` | Deep red. Errors, destructive actions, critical findings. |
| `--color-warning` | `#DD6B20` | Orange. Warnings, approaching deadlines. |
| `--color-info` | `#319795` | Teal. Informational callouts. |
| `--color-success` | `#2D7D46` | Forest green (same as secondary). Completions, passing controls. |

### 2.2 Semantic Status Colors (Tailwind Classes)

Status-driven UI — badges, backgrounds, borders — uses Tailwind's color scale rather than custom properties, ensuring fine-grained shade control:

| Semantic Use | Background | Text | Border (where used) |
|---|---|---|---|
| Critical / High Risk | `bg-red-100` | `text-red-800` | `border-red-200` |
| High | `bg-orange-100` | `text-orange-800` | `border-orange-200` |
| Medium | `bg-yellow-100` | `text-yellow-800` | `border-yellow-200` |
| Low / Success | `bg-green-100` | `text-green-800` | `border-green-200` |
| Info / Pending | `bg-blue-100` | `text-blue-700` | `border-blue-200` |
| Completed / Resolved | `bg-purple-100` | `text-purple-700` | `border-purple-200` |
| Draft / Neutral | `bg-gray-100` | `text-gray-700` | `border-gray-200` |
| Overdue | `bg-red-100` | `text-red-700` | `border-red-200` |
| AI / Smart Features | `bg-violet-100` | `text-violet-700` | `border-violet-200` |
| Active Nav Item | — | `text-white` | Left border `var(--color-accent)` |

### 2.3 Extended Tailwind Palette Usage

The full gray scale (`gray-50` through `gray-900`) is used extensively. Other scales used: `blue`, `red`, `green`, `yellow`, `amber`, `orange`, `purple`, `indigo`, `teal`, `violet`. Shades typically range from `50` (backgrounds) to `700`–`800` (text on colored backgrounds).

---

## 3. Typography

### 3.1 Font Families

| Role | Font | Weights Loaded | Fallback |
|---|---|---|---|
| Primary (UI) | **Inter** | 300, 400, 500, 600, 700, 800 | System sans-serif stack via Tailwind's `defaultTheme.fontFamily.sans` |
| Monospace (Data) | **Roboto Mono** | 400, 500, 600 | System monospace stack |

The Tailwind config extends the default sans family with `Figtree` at the top of the stack (Laravel Breeze default), but the CSS imports and actual usage favor **Inter** throughout.

### 3.2 Type Scale

| Level | Tailwind Class | Approx. Size | Weight | Usage |
|---|---|---|---|---|
| Page Title | `text-2xl` | 28 px | `font-bold` (700) | Top-level page headings in `PageHeader` |
| Section Title | `text-lg` | 18 px | `font-semibold` (600) | Card headers, modal titles |
| Card Title | `text-sm` | 14 px | `font-semibold` (600) | StatCard labels, subsection headers |
| Body | `text-sm` | 14 px | `font-normal` (400) | Default paragraph and table cell text |
| Small / Caption | `text-xs` | 12 px | `font-normal` (400) | Helper text, timestamps, secondary info |
| Micro Label | `text-[10px]` | 10 px | `font-semibold` (600) | Filter labels, uppercase tracking labels |
| Uppercase Label | `text-xs` | 12 px | `font-semibold` + `uppercase tracking-wider` | Table column headers, button text |
| Monospace | `font-mono text-xs` or `text-sm` | 12–14 px | 400–600 | Audit IDs, reference codes, technical values |

### 3.3 Text Color Conventions

| Context | Class |
|---|---|
| Primary body text | `text-[var(--color-text-primary)]` or `text-gray-800` / `text-gray-900` |
| Secondary / muted text | `text-[var(--color-text-secondary)]` or `text-gray-500` / `text-gray-600` |
| Placeholder text | `placeholder-gray-400` |
| Link / interactive text | `text-[var(--color-primary)]` or `text-indigo-600` |
| Disabled text | `text-gray-300` or `text-gray-400` |
| On dark surfaces (sidebar) | `text-white` / `text-white/70` |

---

## 4. Spacing & Layout

### 4.1 Base Unit

All spacing follows Tailwind's **4 px base unit**. The most commonly used values:

| Token | Value | Typical Use |
|---|---|---|
| `1` | 4 px | Tight gaps (icon-to-text) |
| `1.5` | 6 px | Badge internal padding, dot indicators |
| `2` | 8 px | Button vertical padding, small gaps |
| `3` | 12 px | Medium internal padding |
| `4` | 16 px | Standard padding/margin, form field spacing (`mt-4`) |
| `5` | 20 px | Filter bar internal padding |
| `6` | 24 px | Card padding (`p-6`), section gaps (`gap-6`) |
| `8` | 32 px | Large section separation |
| `12` | 48 px | Empty state vertical padding |

### 4.2 Page Layout Structure

```
┌──────────────────────────────────────────────────┐
│  Sidebar (fixed left, 260 px / 72 px collapsed)  │
│  ┌────────────────────────────────────────────┐   │
│  │  TopBar (sticky, h-16, white, border-b)    │   │
│  ├────────────────────────────────────────────┤   │
│  │  PageHeader (mb-6)                         │   │
│  │    Breadcrumb → Title → Action Buttons     │   │
│  ├────────────────────────────────────────────┤   │
│  │  FilterBar (optional, mb-6)                │   │
│  ├────────────────────────────────────────────┤   │
│  │  Content Area                              │   │
│  │    Cards / Tables / Forms                  │   │
│  ├────────────────────────────────────────────┤   │
│  │  Pagination (border-t)                     │   │
│  └────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────┘
```

### 4.3 Sidebar Dimensions

| Property | Value |
|---|---|
| Normal width | `260px` (`--sidebar-width`) |
| Collapsed width | `72px` (`--sidebar-collapsed-width`) |
| Collapse transition | `transition-all duration-300` |
| Mobile behavior | Slides in from left (`-translate-x-full` → `translate-x-0`) |
| Breakpoint for persistent sidebar | `lg:` (1024 px) |

### 4.4 Grid Patterns

| Pattern | Classes |
|---|---|
| Stat cards row | `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6` |
| Two-column form | `grid grid-cols-1 sm:grid-cols-2 gap-4` |
| Dashboard widgets | `grid grid-cols-1 lg:grid-cols-2 gap-6` |
| Button pair (AI panel) | `grid grid-cols-2 gap-2` |

---

## 5. Borders, Radii & Shadows

### 5.1 Border Radius

| Token | Value | Usage |
|---|---|---|
| `rounded-sm` | 2 px | Minimal rounding (rare) |
| `rounded` | 4 px | Default Tailwind (used sparingly) |
| `rounded-md` | 6 px | Input fields, small interactive elements |
| `rounded-lg` | 8 px | **Most common.** Buttons, cards (smaller), modals, inputs, sidebar nav items |
| `rounded-xl` | 12 px | Large cards, filter bars, prominent surfaces |
| `rounded-full` | 9999 px | Badges, pills, avatars, circular indicators |

### 5.2 Borders

| Context | Class |
|---|---|
| Card outline | `border border-gray-100` |
| Table row separator | `border-b border-gray-100` |
| Table header separator | `border-b border-gray-200` |
| Input default | `border-gray-300` |
| Section divider | `border-t border-gray-100` or `border-b border-gray-200` |
| Active sidebar item | `border-l-[3px] border-[var(--color-accent)]` |
| Colored surface border | `border-{color}-200` (matches background shade) |

### 5.3 Shadows

| Token | CSS Equivalent | Usage |
|---|---|---|
| `shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Cards at rest, inputs |
| `shadow-md` | — | Card hover states |
| `shadow-lg` | — | Modals, overlay panels |
| `shadow-xl` | — | Dropdown menus, tooltip-like panels |
| `shadow-2xl` | — | Floating panels (offline indicator detail) |
| Custom card default | `0 1px 3px rgba(0,0,0,0.08)` | `.card` class in CSS |
| Custom card hover | `0 4px 12px rgba(0,0,0,0.12)` | `.card:hover` in CSS |
| Topbar | `0 1px 3px rgba(0,0,0,0.05)` | Sticky header shadow |

---

## 6. Component Library

### 6.1 Buttons

#### Primary Button (`PrimaryButton.jsx`)

```
bg-gray-800 text-white border border-transparent
px-4 py-2 rounded-lg
text-xs font-semibold uppercase tracking-widest
hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900
focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
disabled:opacity-25
transition ease-in-out duration-150
```

Also used with `bg-[var(--color-primary)]` for brand-colored variant in some pages.

#### Secondary Button (`SecondaryButton.jsx`)

```
bg-white text-gray-700 border border-gray-300
px-4 py-2 rounded-lg shadow-sm
text-xs font-semibold uppercase tracking-widest
hover:bg-gray-50
focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
disabled:opacity-25
transition ease-in-out duration-150
```

#### Danger Button (`DangerButton.jsx`)

```
bg-red-600 text-white border border-transparent
px-4 py-2 rounded-lg
text-xs font-semibold uppercase tracking-widest
hover:bg-red-500 active:bg-red-700
focus:ring-2 focus:ring-red-500 focus:ring-offset-2
disabled:opacity-25
transition ease-in-out duration-150
```

#### Success / Green Button (CSS)

```
bg-[var(--color-secondary)] text-white
hover:bg-[var(--color-secondary-light)]
```

#### Icon Button (Pattern)

```
p-2 or p-1.5 rounded-lg
text-gray-400 hover:text-gray-700 hover:bg-gray-100
transition-colors
```

### 6.2 Form Controls

#### Text Input (`TextInput.jsx`)

```
form-input (CSS class) or inline:
px-3 py-2.5 rounded-lg
border-gray-300 shadow-sm
focus:border-indigo-500 focus:ring-indigo-500
text-sm text-gray-700
placeholder-gray-400
w-full
```

#### Input Label (`InputLabel.jsx`)

```
block text-sm font-medium text-gray-700 mb-1
```

(Aliased as `.form-label` in CSS.)

#### Input Error (`InputError.jsx`)

```
text-sm text-red-600 mt-1
```

#### Checkbox (`Checkbox.jsx`)

```
rounded border-gray-300
text-indigo-600 shadow-sm
focus:ring-indigo-500
```

#### Select / Dropdown Input (`.form-select` in CSS)

Same sizing and focus behavior as `TextInput`. Uses `appearance-none` for custom arrow styling where needed.

### 6.3 Badges

#### Status Badge (`StatusBadge.jsx`)

Base structure:

```
inline-flex items-center
px-2.5 py-0.5 rounded-full
text-xs font-semibold
```

Color maps by status keyword — see Section 2.2 for the full status-to-color table.

#### Rating Badge (`RatingBadge.jsx`)

Same base as `StatusBadge` but prepends a **colored dot indicator**:

```
w-1.5 h-1.5 rounded-full mr-1.5
```

Dot colors: red (critical), orange (high), yellow (medium), green (low).

### 6.4 Cards

#### Standard Card (`.card` CSS class)

```
bg-white rounded-xl
shadow: 0 1px 3px rgba(0,0,0,0.08)
border: 1px solid #EDF2F7 (≈ gray-100)
transition: all 0.2s ease
hover → shadow: 0 4px 12px rgba(0,0,0,0.12), translateY(-2px)
```

Internal sections:

| Section | Padding | Extras |
|---|---|---|
| Header | `px-6 py-4` | `border-b border-gray-100` |
| Body | `px-6 py-4` | — |

#### Stat Card (`StatCard.jsx`)

```
.stat-card container (card base)
Layout: flex items-center justify-between

Left side:
  Title  → text-sm font-medium text-gray-500
  Value  → text-2xl font-bold mt-1 text-[var(--color-text-primary)]
  Trend  → mt-3 flex items-center gap-1 text-xs (green for up, red for down)

Right side:
  Icon box → w-10 h-10 rounded-lg flex items-center justify-center
  Color variants: bg-blue-100/text-blue-600, bg-red-100/text-red-600,
                  bg-green-100/text-green-600, bg-amber-100/text-amber-600,
                  bg-purple-100/text-purple-600, bg-teal-100/text-teal-600
```

### 6.5 Data Table (`DataTable.jsx`)

```
Container: overflow-x-auto
Table: .data-table (CSS) → w-full

Header row (thead):
  bg-gray-50 border-b border-gray-200
  Cell: px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider
  Sortable: inline arrow icon (w-3 h-3) toggles asc/desc

Body rows (tbody):
  hover:bg-gray-50 transition-colors
  Cell: px-4 py-3 border-b border-gray-100 text-sm

Striped (optional): alternating bg-gray-50/50
```

### 6.6 Pagination (`Pagination.jsx`)

```
Container: flex items-center justify-between px-4 py-3 border-t border-gray-100

Page links:
  Base: px-3 py-1.5 text-sm rounded-md
  Active: bg-[var(--color-primary)] text-white font-semibold
  Inactive: text-gray-600 hover:bg-gray-100
  Disabled: text-gray-300 cursor-not-allowed
```

### 6.7 Filter Bar (`FilterBar.jsx`)

```
Container (.filter-bar):
  bg-gray-50/80 rounded-xl border border-gray-200
  px-5 py-4 mb-6

Filter group (.filter-group):
  flex flex-col gap-1.5 min-w-0
  Optional flex-1 for stretching

Filter label:
  text-[10px] font-semibold text-gray-400 uppercase tracking-wider

Filter input:
  w-full rounded-lg border-0 bg-white shadow-sm
  ring-1 ring-gray-200 focus:ring-2 focus:ring-[var(--color-primary)]
  text-sm py-2.5 px-3 text-gray-700 placeholder-gray-400

Reset button:
  px-5 py-2.5 text-sm font-semibold text-gray-600
  bg-white rounded-lg ring-1 ring-gray-200
  hover:bg-gray-100 hover:text-gray-800
  Only visible when filters are active
```

### 6.8 Modals & Dialogs

#### Modal (`Modal.jsx`)

```
Overlay:
  fixed inset-0 z-50
  bg-gray-500/75

Panel:
  rounded-lg bg-white shadow-xl overflow-hidden
  sm:mx-auto sm:w-full sm:max-w-{size}
  Size options: sm (max-w-sm), md (max-w-md), lg (max-w-lg),
                xl (max-w-xl), 2xl (max-w-2xl)

Enter transition: ease-out duration-300
  opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95
  → opacity-100 translate-y-0 sm:scale-100

Leave transition: ease-in duration-200 (reverse)
```

#### Confirm Dialog (`ConfirmDialog.jsx`)

```
Header: flex items-center gap-3
  Icon container: w-10 h-10 rounded-full flex items-center justify-center
    Danger: bg-red-100, icon text-red-600
    Warning: bg-yellow-100, icon text-yellow-600
    Info: bg-blue-100, icon text-blue-600
  Title: text-lg font-semibold text-gray-900
  Message: text-sm text-gray-500

Body: p-6
Footer: flex justify-end gap-3
```

### 6.9 Page Header (`PageHeader.jsx`)

```
Container (.page-header):
  flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6

Breadcrumb:
  flex items-center gap-1.5 text-sm text-gray-500 mb-1
  Separator: "›" or chevron icon

Title (.page-title):
  text-2xl font-bold text-[var(--color-text-primary)]

Subtitle:
  text-sm text-[var(--color-text-secondary)] mt-1

Actions slot (right side):
  flex items-center gap-3
```

### 6.10 Empty State (`EmptyState.jsx`)

```
Container: text-center py-12 px-6

Icon area:
  inline-flex items-center justify-center
  w-16 h-16 rounded-full bg-gray-100 mb-4
  Icon: w-8 h-8 text-gray-400

Title: text-sm font-semibold text-gray-800 mb-1
Description: text-sm text-gray-500 mb-4 max-w-sm mx-auto
Action: PrimaryButton or link to creation flow
```

### 6.11 Flash Notifications / Toasts (`FlashNotification.jsx`)

```
Container:
  fixed top-20 right-4 z-50
  flex flex-col gap-3 pointer-events-none

Toast:
  w-80 rounded-lg border shadow-lg overflow-hidden pointer-events-auto
  Variant backgrounds:
    Success: bg-green-50 border-green-200
    Error: bg-red-50 border-red-200
    Warning: bg-amber-50 border-amber-200
    Info: bg-blue-50 border-blue-200

Content: p-4 flex items-start gap-3
  Icon: flex-shrink-0 mt-0.5 w-5 h-5
  Title: text-sm font-semibold
  Message: text-xs mt-0.5

Progress bar:
  h-1 w-full at bottom
  Color matches variant
  transition-all duration-100 ease-linear (auto-dismiss countdown)
```

### 6.12 Navigation Components

#### Sidebar NavItem

```
.sidebar-nav-item:
  flex items-center gap-3
  px-4 py-2.5
  text-sm font-medium rounded-lg
  transition-all duration-200

Active:
  bg-white/10 text-white
  border-l-[3px] border-[var(--color-accent)]

Inactive:
  text-white/70
  hover:bg-white/5 hover:text-white
```

#### TopBar

```
sticky top-0 z-30
bg-white border-b border-gray-200 h-16
Content: flex items-center justify-between h-full px-4 sm:px-6

Right side: search icon, notification bell (with badge count), user avatar dropdown
```

#### NavLink (`NavLink.jsx`)

Inline link variant for horizontal navigation:

```
Active: border-b-2 border-indigo-400 text-gray-900
Inactive: border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300
Padding: px-1 pt-1 pb-0.5
```

### 6.13 Dropdown (`Dropdown.jsx`)

```
Trigger: renders any child as toggle element

Content panel:
  absolute z-50
  rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5
  py-1

Alignment: left or right
Width: w-48 (default)

Dropdown links:
  block w-full px-4 py-2 text-start text-sm text-gray-700
  hover:bg-gray-100
  focus:bg-gray-100
```

### 6.14 Charts & Data Visualization

#### Donut Chart (`DonutChart.jsx`)

SVG-based ring chart with configurable segments. Center displays total count with descriptive label. Legend rendered below with color dot + label + value rows, spaced with `gap-6`.

#### Progress Ring (`ProgressRing.jsx`)

SVG circular progress indicator:

```
Background circle: stroke="#E2E8F0" (gray-200)
Progress arc: animated stroke with transition-all duration-1000
Center: percentage value in bold text
```

#### Progress Bar (CSS `.progress-bar`)

```
Height: 8px
Border radius: rounded-full
Background: gray-200
Fill (.progress-bar-fill): bg-[var(--color-secondary)] or green
Animation: transition-all duration-500
overflow-hidden on container
```

### 6.15 Specialized / Domain Components

#### AI Assistant Panel (`AiAssistantPanel.jsx`)

```
Available state: bg-indigo-50 border-indigo-200
Offline state: bg-gray-50 border-gray-200
Header: px-4 py-3 border-b border-inherit
Status pill: text-xs px-2 py-0.5 rounded-full
```

#### AI Report Generator (`AiReportGenerator.jsx`)

```
Container: bg-indigo-50 border border-indigo-200 rounded-lg
Header: px-4 py-3 bg-indigo-100 border-b border-indigo-200
Content: p-4 space-y-4
Section buttons: grid grid-cols-2 gap-2
```

#### Duplicate Finding Warning (`DuplicateFindingWarning.jsx`)

```
Check trigger: inline-flex items-center gap-2 px-3 py-1.5 text-xs
  bg-amber-50 border border-amber-200 text-amber-800 rounded-lg

Results panel: bg-amber-50 border border-amber-300 rounded-lg p-3
Duplicate item: bg-white rounded p-2 border border-amber-200

Similarity score:
  ≥80%: bg-red-100 text-red-700
  ≥60%: bg-amber-100 text-amber-700
  <60%: bg-gray-100 text-gray-600
```

#### External Link Modal (`GenerateExternalLinkModal.jsx`)

```
Header: flex items-center justify-between mb-5
Generate section: bg-purple-50 border border-purple-200 rounded-lg p-4 mb-5
Link display: font-mono text-purple-700
Status table badges: color-coded for active/expired/revoked/completed
```

#### Regulatory Reference Suggester (`RegulatoryReferenceSuggester.jsx`)

```
Selected chips:
  inline-flex items-center gap-1
  px-2 py-1 text-xs font-medium
  bg-[var(--color-primary)]/10 text-[var(--color-primary)]
  rounded-md border border-[var(--color-primary)]/20

AI badge:
  inline-flex items-center gap-1
  px-2 py-1 text-xs font-medium
  bg-violet-100 text-violet-700 rounded-full
```

---

## 7. Iconography

### 7.1 Icon Library

Primary icon source: **Heroicons** (outline style), rendered as inline SVGs. Legacy HTML mockups use **FontAwesome**.

### 7.2 Icon Sizes

| Token | Dimensions | Usage |
|---|---|---|
| `w-3 h-3` | 12 × 12 px | Sort indicators, micro-icons |
| `w-4 h-4` | 16 × 16 px | Inline with `text-sm` body text, button icons |
| `w-5 h-5` | 20 × 20 px | Standard toolbar/action icons, notification icons |
| `w-6 h-6` | 24 × 24 px | Navigation, sidebar icons |
| `w-8 h-8` | 32 × 32 px | Empty state illustrations |

### 7.3 Icon Styling

```
Default outline: stroke-width={1.5} fill="none" stroke="currentColor"
Filled variant: fill="currentColor" (used sparingly)
Color: inherits text color via currentColor
```

---

## 8. Animations & Transitions

### 8.1 Standard Durations

| Duration | Usage |
|---|---|
| `duration-100` | Toast progress bar countdown |
| `duration-150` | Button hover/focus, form input focus (`ease-in-out`) |
| `duration-200` | Modal leave, sidebar nav item hover, card hover, general interactive feedback |
| `duration-300` | Modal enter, sidebar collapse/expand, page-level transitions |
| `duration-500` | Progress bar fill animation |
| `duration-1000` | Progress ring SVG stroke animation |

### 8.2 Easing

```
ease-in-out   → buttons, form elements
ease-out      → modal/dropdown entrance
ease-in       → modal/dropdown exit
ease-linear   → progress bar countdown
```

### 8.3 Transform Patterns

| Pattern | Classes |
|---|---|
| Card hover lift | `hover:translate-y-[-2px]` |
| Modal entrance | `translate-y-4 sm:scale-95` → `translate-y-0 sm:scale-100` |
| Sidebar slide (mobile) | `-translate-x-full` → `translate-x-0` |

### 8.4 Keyframe Animations

| Animation | Class | Description |
|---|---|---|
| Spin | `animate-spin` | Loading spinners (circular rotation) |
| Pulse | `animate-pulse` | Notification dots, status heartbeats |
| Bell ring | Custom `@keyframes bell-ring` | Notification bell icon oscillation (±14°) |

---

## 9. Responsive Design

### 9.1 Breakpoints

The project uses Tailwind's default breakpoints with a **mobile-first** approach:

| Prefix | Min Width | Key Behavior Change |
|---|---|---|
| (base) | 0 px | Single column, full-width elements, sidebar hidden |
| `sm:` | 640 px | Two-column grids, side-by-side form fields, page header becomes horizontal |
| `lg:` | 1024 px | Sidebar becomes persistent, four-column stat grids, two-column dashboard widgets |

### 9.2 Mobile Patterns

```
Sidebar: overlay with backdrop on mobile, fixed persistent on lg:
Stat cards: stack vertically → 2 cols at sm: → 4 cols at lg:
Page header: vertical stack → horizontal flex at sm:
Tables: horizontal scroll (overflow-x-auto) on narrow screens
Navigation: hamburger menu toggle on mobile
Touch targets: minimum p-4 padding for tap areas
```

---

## 10. Scrollbar Customization

### 10.1 Global Scrollbar (WebKit)

```css
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #CBD5E0;      /* gray-400 */
  border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
  background: #A0AEC0;      /* gray-500 */
}
```

### 10.2 Sidebar Scrollbar (Dark Surface)

```css
.sidebar ::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
}
.sidebar ::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}
```

---

## 11. Layout Composition Recipes

### 11.1 Standard Index / List Page

```
<AuthenticatedLayout>
  <PageHeader title="..." subtitle="..." actions={<PrimaryButton />} />
  <FilterBar filters={[...]} onFilter={...} onReset={...} />
  <div className="card">
    <DataTable columns={[...]} data={[...]} sortable />
  </div>
  <Pagination links={...} />
</AuthenticatedLayout>
```

### 11.2 Dashboard Page

```
<AuthenticatedLayout>
  <PageHeader title="Dashboard" />
  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <StatCard icon="..." color="blue" title="..." value={...} trend={...} />
    <!-- repeat for each KPI -->
  </div>
  <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div className="card"><DonutChart ... /></div>
    <div className="card"><DataTable ... /></div>
  </div>
</AuthenticatedLayout>
```

### 11.3 Create / Edit Form Page

```
<AuthenticatedLayout>
  <PageHeader title="Create ..." breadcrumb={[...]} />
  <div className="card p-6">
    <form>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <InputLabel value="Field Name" />
          <TextInput ... />
          <InputError message={errors.field} />
        </div>
        <!-- repeat -->
      </div>
      <div className="flex justify-end gap-3 mt-6">
        <SecondaryButton>Cancel</SecondaryButton>
        <PrimaryButton>Save</PrimaryButton>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
```

### 11.4 Detail / Show Page

```
<AuthenticatedLayout>
  <PageHeader title="..." breadcrumb={[...]} actions={<SecondaryButton />} />
  <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div className="lg:col-span-2 space-y-6">
      <div className="card">
        <!-- Primary detail content -->
      </div>
      <div className="card">
        <DataTable ... />
      </div>
    </div>
    <div className="space-y-6">
      <div className="card">
        <!-- Sidebar metadata: status, dates, assignments -->
      </div>
      <AiAssistantPanel />
    </div>
  </div>
</AuthenticatedLayout>
```

### 11.5 Modal Confirmation Flow

```
<ConfirmDialog
  show={showDelete}
  onClose={() => setShowDelete(false)}
  variant="danger"
  title="Delete Finding"
  message="This action cannot be undone."
  confirmLabel="Delete"
  onConfirm={handleDelete}
/>
```

---

## 12. Accessibility Checklist

| Concern | Implementation |
|---|---|
| Focus visibility | `focus:ring-2 focus:ring-{color}-500 focus:ring-offset-2` on all interactive elements |
| Color contrast | Dark text on light backgrounds; white text on dark/colored surfaces |
| Semantic HTML | `<nav>`, `<main>`, `<table>`, `<form>`, `<button>` used appropriately |
| Form labels | Every input paired with `<InputLabel>` via `htmlFor` |
| Error messaging | `<InputError>` renders `aria-live` compatible red text below fields |
| Keyboard navigation | Modal traps focus; dropdowns close on `Escape` |
| Touch targets | Minimum 40 × 40 px effective area on mobile |
| Screen reader text | `sr-only` class used for icon-only buttons |

---

## 13. File & Folder Reference

| Path | Purpose |
|---|---|
| `resources/css/app.css` | CSS custom properties, base component classes (`.card`, `.badge`, `.form-input`, etc.), scrollbar styles, keyframes |
| `tailwind.config.js` | Font family override (Figtree/Inter), `@tailwindcss/forms` plugin |
| `resources/js/Components/` | All reusable UI primitives (buttons, inputs, badges, tables, modals, charts, etc.) |
| `resources/js/Layouts/` | `AuthenticatedLayout` (sidebar + topbar shell), `GuestLayout` (centered auth pages), `ExternalLayout` (public auditee pages) |
| `resources/js/Pages/` | Feature pages composed from layout + components |
| `resources/js/utils.js` | Shared utility functions |
| `resources/js/constants/` | Framework-specific data constants (ISO, PCI-DSS, NDPA, etc.) |

---

*This document is the single source of truth for visual and interaction design in AuditPro GRC. All new features and components should conform to these patterns. When in doubt, reference the existing component implementations in `resources/js/Components/`.*
