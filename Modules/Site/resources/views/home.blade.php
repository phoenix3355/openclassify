@extends('app::layouts.app')
@section('content')
@php
    $menuCategories = $categories->take(8);
    $heroListing = $featuredListings->first() ?? $recentListings->first();
    $heroImage = $heroListing?->primaryImageData('gallery');
    $listingCards = $featuredListings
        ->concat($recentListings)
        ->unique('id')
        ->take(8)
        ->values();
    $demoEnabled = (bool) config('demo.enabled');
    $prepareDemoRoute = $demoEnabled ? route('demo.prepare') : null;
    $prepareDemoRedirect = url()->full();
    $hasDemoSession = (bool) session('is_demo_session') || filled(session('demo_uuid'));
    $demoLandingMode = $demoEnabled && !auth()->check() && !$hasDemoSession;
    $demoTurnstileProtectionEnabled = (bool) config('demo.turnstile.enabled', false);
    $demoTurnstileSiteKey = trim((string) config('demo.turnstile.site_key', ''));
    $prepareDemoTurnstileRequired = $demoLandingMode && $demoTurnstileProtectionEnabled;
    $prepareDemoTurnstileRenderable = $prepareDemoTurnstileRequired && $demoTurnstileSiteKey !== '';
    $demoTtlMinutes = (int) config('demo.ttl_minutes', 360);
    $demoTtlHours = intdiv($demoTtlMinutes, 60);
    $demoTtlRemainderMinutes = $demoTtlMinutes % 60;
    $demoTtlLabelParts = [];

    if ($demoTtlHours > 0) {
        $demoTtlLabelParts[] = $demoTtlHours.' '.\Illuminate\Support\Str::plural('hour', $demoTtlHours);
    }

    if ($demoTtlRemainderMinutes > 0) {
        $demoTtlLabelParts[] = $demoTtlRemainderMinutes.' '.\Illuminate\Support\Str::plural('minute', $demoTtlRemainderMinutes);
    }

    $demoTtlLabel = $demoTtlLabelParts !== [] ? implode(' ', $demoTtlLabelParts) : '0 minutes';
    $homeSlides = collect($generalSettings['home_slides'] ?? [])
        ->filter(fn ($slide): bool => is_array($slide))
        ->map(function (array $slide): array {
            $imagePath = trim((string) ($slide['image_path'] ?? ''));

            return [
                'badge' => trim((string) ($slide['badge'] ?? 'Marketplace leader')),
                'title' => trim((string) ($slide['title'] ?? 'Give things a new story.')),
                'subtitle' => trim((string) ($slide['subtitle'] ?? 'The fastest way to sell and the smartest way to buy in your area.')),
                'primary_button_text' => trim((string) ($slide['primary_button_text'] ?? 'Browse listings')),
                'secondary_button_text' => trim((string) ($slide['secondary_button_text'] ?? 'Post listing')),
                'image_url' => \Modules\Site\App\Support\LocalMedia::url($imagePath),
            ];
        })
        ->filter(fn (array $slide): bool => $slide['title'] !== '')
        ->values();

    if ($homeSlides->isEmpty()) {
        $homeSlides = collect([
            [
                'badge' => 'Marketplace leader',
                'title' => 'Give things a new story.',
                'subtitle' => 'The fastest way to sell and the smartest way to buy in your area.',
                'primary_button_text' => 'Browse listings',
                'secondary_button_text' => 'Post listing',
                'image_url' => null,
            ],
        ]);
    }

    $activeSlide = $homeSlides->first();

    $categorySymbols = ['📱', '🚗', '🏠', '🛋️', '👕', '💼', '🎮', '🧰'];
    $categoryGradients = [
        'from-[#fff8f1] to-[#f3ede3]',
        'from-[#f2f7ff] to-[#e8eefc]',
        'from-[#f7fff7] to-[#ebf9f0]',
        'from-[#fff4f2] to-[#fee8e2]',
    ];

@endphp

@if($demoLandingMode && $prepareDemoRoute)
<div class="min-h-screen flex items-center justify-center px-5 py-10">
    <form method="POST" action="{{ $prepareDemoRoute }}" data-demo-prepare-form data-turnstile-required="{{ $prepareDemoTurnstileRequired ? '1' : '0' }}" class="w-full max-w-xl rounded-[32px] border border-slate-200 bg-white p-8 md:p-10 shadow-xl">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ $prepareDemoRedirect }}">
        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-slate-950">Prepare Demo</h1>
        <p class="mt-5 text-base md:text-lg leading-8 text-slate-600">
            Launch a private seeded marketplace for this browser. Listings, favorites, inbox data, and admin access are prepared automatically.
        </p>
        <p class="mt-4 text-base text-slate-500">
            This demo is deleted automatically after {{ $demoTtlLabel }}.
        </p>
        @if($prepareDemoTurnstileRenderable)
        <div class="mt-6 space-y-2">
            <div class="cf-turnstile" data-sitekey="{{ $demoTurnstileSiteKey }}"></div>
            <p class="text-xs text-slate-500">Complete the security check before starting your private demo.</p>
        </div>
        @elseif($prepareDemoTurnstileRequired)
        <p class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium leading-6 text-amber-700">
            Security check is enabled but the widget is not configured. Contact the administrator.
        </p>
        @endif
        <p data-demo-prepare-status data-turnstile-message="Please complete the security verification first." data-loading-message="Preparing your private demo. This can take longer because a dedicated seeded environment is being provisioned for your browser." aria-live="polite" class="mt-4 hidden rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium leading-6 text-blue-800">
            Preparing your private demo. This can take longer because a dedicated seeded environment is being provisioned for your browser.
        </p>
        <button type="submit" data-demo-prepare-button @if($prepareDemoTurnstileRequired) disabled @endif class="mt-8 inline-flex min-h-16 w-full items-center justify-center rounded-full bg-blue-600 px-8 py-4 text-lg font-semibold text-white shadow-lg transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-500">
            <span data-demo-prepare-idle>Prepare Demo</span>
            <span data-demo-prepare-loading class="hidden items-center gap-2">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"></path>
                </svg>
                Preparing Demo...
            </span>
        </button>
    </form>
</div>
@else
<div class="mx-auto max-w-7xl px-4 py-6 md:px-6 md:py-10 space-y-10 md:space-y-14 text-[#1A1714]" data-home-redesign>
    <section class="grid lg:grid-cols-12 gap-8 items-center" data-home-hero>
        <div class="lg:col-span-7">
            <div class="inline-flex items-center gap-2 text-[#D4440C] text-xs font-bold uppercase tracking-[0.2em] mb-5">
                <span class="w-8 h-[2px] bg-[#D4440C]"></span>
                {{ $activeSlide['badge'] }}
            </div>
            <h1 class="text-4xl md:text-6xl font-black leading-[1.08] tracking-tight max-w-2xl">
                {{ $activeSlide['title'] }}
            </h1>
            <p class="mt-5 text-[#71685b] text-base md:text-lg max-w-xl leading-7">
                {{ $activeSlide['subtitle'] }}
            </p>
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a href="{{ route('listings.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-[#1A1714] text-white px-6 py-3 text-sm md:text-base font-bold hover:bg-[#302a24] transition">
                    {{ $activeSlide['primary_button_text'] }}
                </a>
                @auth
                <a href="{{ route('panel.listings.create') }}" class="inline-flex items-center justify-center rounded-2xl border border-[#d9cfc0] bg-white px-6 py-3 text-sm md:text-base font-semibold hover:border-[#bfae95] transition">
                    {{ $activeSlide['secondary_button_text'] }}
                </a>
                @else
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-[#d9cfc0] bg-white px-6 py-3 text-sm md:text-base font-semibold hover:border-[#bfae95] transition">
                    {{ $activeSlide['secondary_button_text'] }}
                </a>
                @endauth
            </div>
            <div class="mt-7 flex items-center gap-4">
                <div class="flex -space-x-3">
                    @foreach([11, 22, 33, 44] as $avatar)
                    <img src="https://i.pravatar.cc/90?u={{ $avatar }}" alt="Community avatar" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                    @endforeach
                    <span class="w-10 h-10 rounded-full border-2 border-white bg-[#E8891A] text-white text-[11px] font-bold grid place-items-center">+2k</span>
                </div>
                <div class="text-sm">
                    <p class="font-bold">Active community</p>
                    <p class="text-[#71685b]">{{ number_format((int) $listingCount) }} active listings today</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-5 hidden lg:block">
            <div class="relative">
                <div class="absolute -inset-4 rounded-[2rem] bg-[#D4440C]/10 -rotate-2"></div>
                <div class="relative overflow-hidden rounded-3xl border border-white shadow-2xl h-[420px] bg-[#efe8dd]">
                    @if($activeSlide['image_url'])
                    <img src="{{ $activeSlide['image_url'] }}" alt="{{ $activeSlide['title'] }}" class="w-full h-full object-cover">
                    @elseif($heroImage)
                    @include('listing::partials.responsive-image', [
                        'image' => $heroImage,
                        'alt' => $heroListing?->title,
                        'class' => 'w-full h-full object-cover',
                        'loading' => 'eager',
                        'fetchpriority' => 'high',
                    ])
                    @else
                    <div class="w-full h-full grid place-items-center text-[#8c8274] text-sm font-semibold">Upload a hero image in settings</div>
                    @endif
                </div>
                <div class="absolute -bottom-6 -left-6 rounded-2xl border border-[#e4d9c8] bg-white px-4 py-3 shadow-xl flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-green-100 text-green-600 grid place-items-center text-lg">♥</div>
                    <div>
                        <p class="font-bold text-sm">High buyer interest</p>
                        <p class="text-xs text-[#7f7568]">Live in your region</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section data-home-section>
        <div class="flex items-end justify-between mb-5 md:mb-7">
            <h2 class="text-2xl md:text-3xl font-black tracking-tight">Categories</h2>
            <a href="{{ route('categories.index') }}" class="text-[#D4440C] font-bold text-sm hover:underline inline-flex items-center gap-1">
                All categories <span aria-hidden="true">›</span>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
            @foreach($menuCategories->take(6) as $index => $category)
            @php
                $categoryIconUrl = $category->iconUrl();
                $cardGradient = $categoryGradients[$index % count($categoryGradients)];
            @endphp
            <a href="{{ route('listings.index', ['category' => $category->id]) }}" class="group rounded-2xl border border-[#e4d9c8] bg-white p-5 text-center hover:border-[#D4440C] hover:-translate-y-1 hover:shadow-lg transition-all" data-home-category-card>
                <div class="mx-auto mb-3 h-14 w-14 rounded-full bg-gradient-to-b {{ $cardGradient }} grid place-items-center overflow-hidden text-2xl">
                    @if($categoryIconUrl)
                    <img src="{{ $categoryIconUrl }}" alt="{{ $category->name }}" class="h-9 w-9 object-contain">
                    @else
                    <span>{{ $categorySymbols[$index % count($categorySymbols)] }}</span>
                    @endif
                </div>
                <p class="font-bold text-sm leading-5 text-[#1A1714]">{{ $category->name }}</p>
                <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-[#8a7e6d]">{{ number_format((int) ($category->listings_count ?? 0)) }} listings</p>
            </a>
            @endforeach
        </div>
    </section>

    <section data-home-section>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
            <h2 class="text-2xl md:text-3xl font-black tracking-tight">Recommended for you</h2>
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <span class="rounded-full bg-[#1A1714] text-white px-4 py-1.5 text-sm font-bold">All</span>
                <a href="{{ route('listings.index', ['sort' => 'latest']) }}" class="rounded-full border border-[#e4d9c8] bg-white px-4 py-1.5 text-sm font-semibold hover:border-[#1A1714] transition">Latest</a>
                <a href="{{ route('listings.index', ['featured' => 1]) }}" class="rounded-full border border-[#e4d9c8] bg-white px-4 py-1.5 text-sm font-semibold hover:border-[#1A1714] transition">Featured</a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @forelse($listingCards as $listing)
            @php
                $listingImage = $listing->primaryImageData('card');
                $priceLabel = $listing->price ? number_format((float) $listing->price, 0, '.', ' ').' '.$listing->currency : __('messages.free');
                $locationLabel = trim(collect([$listing->city, $listing->country])->filter()->join(', '));
                $isFavorited = in_array($listing->id, $favoriteListingIds ?? [], true);
            @endphp
            <article class="group rounded-2xl overflow-hidden border border-[#e4d9c8] bg-white hover:shadow-xl transition" data-home-listing-card>
                <div class="relative h-56 overflow-hidden bg-[#eee4d4]">
                    <a href="{{ route('listings.show', $listing) }}" class="block h-full w-full" aria-label="{{ $listing->title }}">
                        @if($listingImage)
                        @include('listing::partials.responsive-image', [
                            'image' => $listingImage,
                            'alt' => $listing->title,
                            'class' => 'w-full h-full object-cover group-hover:scale-110 transition-transform duration-500',
                        ])
                        @else
                        <div class="h-full w-full grid place-items-center text-[#8a7e6d] text-sm font-semibold">No image</div>
                        @endif
                    </a>
                    <div class="absolute top-3 left-3 rounded-md bg-white/90 px-2 py-1 text-[10px] font-black uppercase tracking-widest">
                        {{ $listing->category?->name ?? 'Listing' }}
                    </div>
                    <div class="absolute top-3 right-3">
                        @auth
                        <form method="POST" action="{{ route('favorites.listings.toggle', $listing) }}">
                            @csrf
                            <button type="submit" class="w-9 h-9 rounded-full grid place-items-center transition {{ $isFavorited ? 'bg-[#D4440C] text-white' : 'bg-white/85 text-[#1A1714] hover:bg-white' }}">❤</button>
                        </form>
                        @else
                        <a href="{{ route('login') }}" class="w-9 h-9 rounded-full bg-white/85 text-[#1A1714] grid place-items-center">♡</a>
                        @endauth
                    </div>
                    <div class="absolute bottom-3 left-3 rounded-md bg-black/45 text-white px-2 py-1 text-[10px] font-medium">
                        {{ $listing->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-[15px] leading-5 min-h-[2.5rem] group-hover:text-[#D4440C] transition-colors">{{ $listing->title }}</h3>
                    <p class="mt-2 text-xs text-[#7f7568] truncate">{{ $locationLabel !== '' ? $locationLabel : 'Location not specified' }}</p>
                    <div class="mt-4 pt-4 border-t border-[#f0e7da] flex items-center justify-between">
                        <p class="text-xl font-black tracking-tight">{{ $priceLabel }}</p>
                        <a href="{{ route('listings.show', $listing) }}" class="w-8 h-8 rounded-lg bg-[#f2ede4] text-[#1A1714] grid place-items-center hover:bg-[#D4440C] hover:text-white transition">›</a>
                    </div>
                </div>
            </article>
            @empty
            <div class="col-span-full rounded-2xl border border-dashed border-[#d7ccbc] bg-white py-20 text-center text-[#7f7568]">
                No listings yet.
            </div>
            @endforelse
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('listings.index') }}" class="inline-flex items-center justify-center rounded-2xl border-2 border-[#1A1714] px-10 py-3 text-sm font-black hover:bg-[#1A1714] hover:text-white transition">
                Show more listings
            </a>
        </div>
    </section>

    <section class="relative overflow-hidden rounded-[2rem] bg-[#1A1714] text-white p-8 md:p-14" data-home-section>
        <div class="absolute right-[-8rem] top-[-8rem] w-72 h-72 rounded-full bg-gradient-to-br from-[#E8891A]/50 to-[#D4440C]/40 blur-3xl"></div>
        <div class="relative flex flex-col md:flex-row md:items-end md:justify-between gap-8">
            <div class="max-w-2xl">
                <h2 class="text-3xl md:text-5xl font-black leading-tight">Do you have items at home you no longer use?</h2>
                <p class="mt-4 text-white/70 text-base md:text-lg leading-7">The average user earns more every year by selling unused items. Start today.</p>
            </div>
            @auth
            <a href="{{ route('panel.listings.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-[#D4440C] px-8 py-4 text-base font-bold hover:bg-[#bf3a08] transition">
                Post listing for free
            </a>
            @else
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-[#D4440C] px-8 py-4 text-base font-bold hover:bg-[#bf3a08] transition">
                Post listing for free
            </a>
            @endauth
        </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4" data-home-section>
        <div class="rounded-2xl border border-[#e4d9c8] bg-white p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-[#8a7e6d] font-bold">Active listings</p>
            <p class="mt-2 text-3xl font-black">{{ number_format((int) $listingCount) }}</p>
        </div>
        <div class="rounded-2xl border border-[#e4d9c8] bg-white p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-[#8a7e6d] font-bold">Active categories</p>
            <p class="mt-2 text-3xl font-black">{{ number_format((int) $categoryCount) }}</p>
        </div>
        <div class="rounded-2xl border border-[#e4d9c8] bg-white p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-[#8a7e6d] font-bold">Community users</p>
            <p class="mt-2 text-3xl font-black">{{ number_format((int) $userCount) }}</p>
        </div>
    </section>
</div>
@endif
@if($prepareDemoTurnstileRenderable)
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endsection
