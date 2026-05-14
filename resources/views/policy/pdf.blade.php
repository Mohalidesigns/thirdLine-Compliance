<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 20mm 15mm;
    }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11pt;
        color: #1A1A2E;
        line-height: 1.5;
        position: relative;
    }
    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-35deg);
        font-size: 54pt;
        color: rgba(26, 54, 93, 0.08);
        white-space: nowrap;
        font-weight: bold;
        z-index: -1;
        letter-spacing: 2px;
    }
    .header {
        border-bottom: 3px solid #1A365D;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .header-title {
        font-size: 18pt;
        font-weight: bold;
        color: #1A365D;
        margin: 0 0 6px;
    }
    .header-meta {
        font-size: 9pt;
        color: #4A5568;
    }
    .header-meta span {
        margin-right: 20px;
    }
    .section-title {
        font-size: 13pt;
        font-weight: bold;
        color: #1A365D;
        border-left: 4px solid #D4AF37;
        padding-left: 8px;
        margin-top: 18px;
        margin-bottom: 8px;
    }
    .content {
        font-size: 11pt;
        line-height: 1.6;
    }
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        border-top: 1px solid #CBD5E0;
        padding-top: 6px;
        font-size: 8pt;
        color: #718096;
        text-align: center;
    }
    .badge {
        display: inline-block;
        background: #1A365D;
        color: #fff;
        font-size: 9pt;
        padding: 2px 8px;
        border-radius: 3px;
        margin-right: 6px;
    }
</style>
</head>
<body>

<div class="watermark">ATHERIS POLICY — v{{ $policy->version }}</div>

<div class="footer">
    {{ $policy->reference }} &mdash; {{ $policy->title }} &mdash; v{{ $policy->version }} &mdash;
    {{ $policy->effective_date ? $policy->effective_date->format('d M Y') : 'Effective date not set' }}
    &mdash; Page <span class="pagenum"></span>
</div>

<div class="header">
    <div class="header-title">{{ $policy->title }}</div>
    <div class="header-meta">
        <span><strong>Ref:</strong> {{ $policy->reference }}</span>
        <span><strong>Version:</strong> {{ $policy->version }}</span>
        <span><strong>Owner:</strong> {{ $policy->owner_team }}</span>
        <span><strong>Category:</strong> {{ $policy->categoryLabel() }}</span>
        @if($policy->effective_date)
        <span><strong>Effective:</strong> {{ $policy->effective_date->format('d M Y') }}</span>
        @endif
        @if($policy->next_review_date)
        <span><strong>Next Review:</strong> {{ $policy->next_review_date->format('d M Y') }}</span>
        @endif
    </div>
</div>

@if($policy->summary)
<div class="section-title">Summary</div>
<div class="content">{{ $policy->summary }}</div>
@endif

@if($policy->body)
<div class="section-title">Policy Content</div>
<div class="content">{!! nl2br(e($policy->body)) !!}</div>
@endif

<div class="section-title">Document Control</div>
<div class="content">
    <p>This document is the official policy of Atheris Compliance Management. Printed copies are uncontrolled.
    The current version is maintained in the Atheris Policy Register.</p>
    <p>Status: <span class="badge">{{ strtoupper(str_replace('_', ' ', $policy->state::$name)) }}</span></p>
</div>

</body>
</html>
