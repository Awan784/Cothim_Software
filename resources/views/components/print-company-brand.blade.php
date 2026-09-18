@props([
    'settings' => null,
    'compact' => false,
])

@php
    $settings = $settings ?? app(\App\Services\SettingsService::class);
    $logoUrl = $settings->logoUrl();
    $metaLines = $settings->printMetaLines();
@endphp

@once
    <style>
        .brand-block { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
        .brand-logo { height: 58px; width: auto; max-width: 140px; object-fit: contain; }
        .brand-block.is-compact .brand-logo { height: 42px; max-width: 110px; }
        .brand-block.is-compact .company-name { font-size: 14pt; }
        .brand-block.is-compact .company-meta { font-size: 8pt; }
        .brand-block .company-name { margin: 0 0 4px; }
        .brand-block .company-meta { margin: 0; }
    </style>
@endonce

<div {{ $attributes->class(['brand-block', 'is-compact' => $compact]) }}>
    @if($logoUrl)
        <img class="brand-logo" src="{{ $logoUrl }}" alt="{{ $settings->companyName() }}">
    @endif
    <div>
        <h1 class="company-name">{{ $settings->companyName() }}</h1>
        @if($metaLines !== [])
            <p class="company-meta">
                @foreach($metaLines as $line)
                    {{ $line }}@if(! $loop->last)<br>@endif
                @endforeach
            </p>
        @endif
    </div>
</div>
