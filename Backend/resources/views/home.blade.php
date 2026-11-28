<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#030407">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="note-store-url" content="{{ route('notes.store') }}">
    <meta name="note-offline-url" content="{{ route('notes.offline') }}">
    <meta name="offline-user-id" content="{{ Auth::id() }}">
    <meta name="notes-index-url" content="{{ route('notes.index') }}">
    <meta name="note-show-url-template" content="{{ route('notes.show', '__NOTE_ID__') }}">
    <meta name="note-update-url-template" content="{{ route('notes.update', '__NOTE_ID__') }}">
    <meta name="note-lock-url-template" content="{{ route('notes.lock', '__NOTE_ID__') }}">
    <meta name="note-lock-change-url-template" content="{{ route('notes.lock.change', '__NOTE_ID__') }}">
    <meta name="note-unlock-check-url-template" content="{{ route('notes.unlock-check', '__NOTE_ID__') }}">
    <meta name="note-unlock-session-clear-url-template" content="{{ route('notes.unlock-session.clear', '__NOTE_ID__') }}">
    <meta name="note-lock-remove-url-template" content="{{ route('notes.lock.remove', '__NOTE_ID__') }}">
    <meta name="note-images-store-url-template" content="{{ route('notes.images.store', '__NOTE_ID__') }}">
    <meta name="note-image-delete-url-template" content="{{ route('notes.images.destroy', ['note' => '__NOTE_ID__', 'image' => '__IMAGE_ID__']) }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>My Notes — Your Space for Ideas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/sass/home.scss', 'resources/js/home.js'])
    
</head>
<body class="notes-app">
    @php
        $unlockedIds = array_map('strval', session('unlocked_note_passwords', []));
        $notes = collect($notes)->map(function ($note) use ($unlockedIds) {
            $hasPassword = $note->has_password;
            $unlocked = $hasPassword && in_array((string) $note->id, $unlockedIds, true);
            $accessLocked = $hasPassword && ! $unlocked;
            return [
                'id' => $note->id,
                'title' => $accessLocked ? 'Locked Note' : $note->title,
                'content' => $accessLocked ? '' : $note->content,
                'labels' => [],
                'updated' => $note->updated_at?->diffForHumans() ?? '',
                'pinned' => (bool) $note->is_pinned,
                'shared' => (bool) $note->is_shared,
                'locked' => $hasPassword,
                'unlocked' => $unlocked,
                'hasPassword' => $hasPassword,
                'image' => $note->images->isNotEmpty() && ! $hasPassword,
                'imageStyle' => 'blue',
                'imageAlt' => 'Attached image',
                'imagePath' => $hasPassword ? null : $note->images->first()?->path,
                'imageCount' => $hasPassword ? 0 : $note->images->count(),
            ];
        })->all();
        $pinnedNotes = array_values(array_filter($notes, fn ($note) => $note['pinned']));
        $otherNotes = array_values(array_filter($notes, fn ($note) => ! $note['pinned']));
        $labelCounts = [];
        foreach ($notes as $note) {
            foreach ($note['labels'] as $label) {
                $labelCounts[$label] = ($labelCounts[$label] ?? 0) + 1;
            }
        }
        ksort($labelCounts);

        $user = Auth::user();

        $lockIcon = 
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>';

function icon($type, $w = 20, $h = 20, $color = 'currentColor', $fill = 'none') {
    if ($type === 'lock') {
        return <<<SVG
<svg xmlns="http://w3.org" fill="{$fill}" width="{$w}" height="{$h}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{$color}" class="size-6">
    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
</svg>
SVG;
    }
    return '';
}

    @endphp

    <nav class="topbar" aria-label="Main navigation">
        <div class="topbar-brand">
            
            {{-- Sidebar Button --}}
            <button class="icon-button mobile-menu-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open categories">
                
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="25" height="25" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>

            </button>

            {{-- Brand --}}
            <a class="d-inline-flex align-items-center gap-2"
               href="{{ url('/') }}"
               aria-label="NoteApp - Home">

                {{-- Brand Icon --}}
                <span class="gc-brand-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24"
                         fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 4.5h9.5L20 8v11.5H7V4.5Z"
                              stroke="currentColor" stroke-width="1.7"
                              stroke-linejoin="round"/>
                        <path d="M16 4.8V8h3.2M4 8h2M4 12h2M4 16h2M10 12h6M10 15.5h6"
                              stroke="currentColor" stroke-width="1.7"
                              stroke-linecap="round"/>
                    </svg>
                </span>

                {{-- Brand Name --}}
                <span class="gc-brand">Note<span>App</span></span>
            </a>
        </div>

        {{-- Search Box --}}
        <label class="search-box" for="noteSearch">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>

            <input id="noteSearch" type="search" placeholder="Search your notes here..." autocomplete="off" aria-label="Note Search">
        </label>

        <div class="topbar-actions">

            {{-- Change Layout --}}
            <div class="view-switch" role="group" aria-label="Note view options">
                <button class="icon-button view-option is-active" type="button" data-view="grid" aria-label="Show in grid view" aria-pressed="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>

                </button>

                <button class="icon-button view-option" type="button" data-view="list" aria-label="Show in list view" aria-pressed="false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </button>
            </div>

            {{-- Change Theme --}}
            <button id="themeToggle" class="icon-button theme-toggle" type="button" aria-label="Switch to light theme" title="Change theme">
                
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>

                <svg class="moon-icon d-none" xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>


            </button>

            {{-- Username and Avatar --}}
            <div class="dropdown">
                <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User Profile">
                    
                    {{-- Avatar --}}
                    <div class="profile-avatar-wrapper">
                        <span 
                            class="{{ $user->avatar ? 'd-none' : '' }} profile-avatar profile-avatar-fallback
                                    rounded-circle d-flex align-items-center justify-content-center fw-bold"
                            style="background: #c8ff19; color: #080a0e;">
                            
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>

                        <img
                            src="{{ $user->avatar
                                ? asset('storage/' . $user->avatar)
                                : '' }}"
                            alt="User avatar"
                            class="profile-avatar"
                            style="{{ $user->avatar ? '' : 'display:none' }}"
                        >
                    </div>

                    {{-- Username --}}
                    <span class="username">
                        {{ auth()->user()->name }}
                    </span>
                </button>

                {{-- Dropdown --}}
                <ul class="dropdown-menu dropdown-menu-end profile-menu">
                    
                    {{-- Email --}}
                    <li>
                        <span class="dropdown-item" style="color:#999999">
                            {{ auth()->user()->email }}
                        </span>
                    </li>
                    
                    {{-- User Profile --}}
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            Profile
                        </a>
                    </li>

                    {{-- Setting --}}
                    <li>
                        <a class="dropdown-item" href="#">
                            
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            Setting
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    {{-- Sign Out --}}
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
    
                                Sign out
                            </button>
                        </form>
                    </li>

                </ul>

            </div>
        </div>
    </nav>

    {{-- Email Verify Notification --}}
    <div class="verification-alert" role="status" id="verificationAlert">
        <span class="alert-icon">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>

        </span>
        
        <span>
            <strong>Your email is not verified yet</strong>
            
            <span class="alert-detail"> 
                — Verify email to protect your notes.
            </span>
        </span>
        
        <form class="verification-form" action="{{ route('verification.send') }}" method="POST">
            @csrf
            <button type="submit" class="alert-link">
                Verify now
                
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="12" height="15" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                </svg>
            </button>

        </form>
        
    </div>

    <div class="workspace">
        <aside class="sidebar sidebar-desktop" aria-label="Note categories">
            <div class="sidebar-inner">
                <p class="sidebar-caption">YOUR SPACE</p>
                <button class="sidebar-link is-selected" type="button" data-filter="all">
                    <i class="bi bi-journal-text"></i><span>All Notes</span><span class="sidebar-count">{{ count($notes) }}</span>
                </button>
                <button class="sidebar-link" type="button" data-filter="shared">
                    <i class="bi bi-people"></i><span>Shared with Me</span>
                </button>

                <div class="sidebar-section-heading">
                    <p class="sidebar-caption">YOUR LABELS</p>
                    <button class="tiny-icon-button" type="button" aria-label="Add label"><i class="bi bi-plus"></i></button>
                </div>
                <div class="label-list">
                    @foreach ($labelCounts as $label => $count)
                        <button class="sidebar-link label-filter" type="button" data-filter-label="{{ $label }}">
                            <span class="label-dot label-dot-{{ $loop->index % 5 }}"></span>
                            <span>{{ $label }}</span><span class="sidebar-count">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>
                <button class="sidebar-link manage-labels" type="button">
                    <i class="bi bi-sliders2"></i><span>Manage Labels</span>
                </button>
            </div>
        </aside>

        <div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarTitle">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title brand-mark" id="mobileSidebarTitle"><span class="brand-icon"><i class="bi bi-journal-bookmark-fill"></i></span> Note<span class="brand-accent">App</span></h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close categories"></button>
            </div>
            <div class="offcanvas-body">
                <p class="sidebar-caption">YOUR SPACE</p>
                <button class="sidebar-link is-selected mobile-filter" type="button" data-filter="all" data-bs-dismiss="offcanvas"><i class="bi bi-journal-text"></i><span>All Notes</span><span class="sidebar-count">{{ count($notes) }}</span></button>
                <button class="sidebar-link mobile-filter" type="button" data-filter="shared" data-bs-dismiss="offcanvas"><i class="bi bi-people"></i><span>Shared with Me</span></button>
                <div class="sidebar-section-heading"><p class="sidebar-caption">YOUR LABELS</p></div>
                @foreach ($labelCounts as $label => $count)
                    <button class="sidebar-link label-filter mobile-filter" type="button" data-filter-label="{{ $label }}" data-bs-dismiss="offcanvas">
                        <span class="label-dot label-dot-{{ $loop->index % 5 }}"></span><span>{{ $label }}</span><span class="sidebar-count">{{ $count }}</span>
                    </button>
                @endforeach
                <button class="sidebar-link manage-labels" type="button"><i class="bi bi-sliders2"></i><span>Manage Labels</span></button>
            </div>
        </div>

        <main class="main-content">
            <section class="notes-section" aria-labelledby="notesHeading">
                <div class="section-toolbar">
                    <div>
                        <p class="eyebrow section-eyebrow">YOUR IDEA MAP</p>
                        <h2 id="notesHeading">Notes <span id="activeFilterLabel">recent</span><span class="section-period">,</span></h2>
                        <p id="offlineStatus" class="autosave-status" role="status" aria-live="polite"></p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a class="button-quiet" href="{{ route('offline') }}">Offline Notes</a>
                        <button class="button-quiet sort-button" type="button" aria-label="Sort notes">
                            <i class="bi bi-arrow-down-up"></i><span>Recently Updated</span>
                        </button>
                    </div>
                </div>

                <div class="notes-results" id="notesResults" data-view="grid">
                    @foreach ([['name' => 'Pinned', 'notes' => $pinnedNotes, 'key' => 'pinned'], ['name' => 'Other', 'notes' => $otherNotes, 'key' => 'other']] as $group)
                        <section class="note-group" data-group="{{ $group['key'] }}" aria-label="{{ $group['name'] }}">
                            <h3 class="group-title"><span>{{ $group['name'] }}</span><span class="group-rule"></span></h3>
                            
                            <div class="note-grid">
                                @foreach ($group['notes'] as $note)
                                    <article class="note-card{{ $note['locked'] ? ' note-card-locked' : '' }}" data-note-id="{{ $note['id'] }}" data-search="{{ $note['title'] }} {{ $note['content'] }}" data-labels="{{ implode('|', $note['labels']) }}" data-shared="{{ $note['shared'] ? 'true' : 'false' }}" data-pinned="{{ $note['pinned'] ? 'true' : 'false' }}" data-locked="{{ $note['locked'] ? 'true' : 'false' }}" data-unlocked="{{ $note['unlocked'] ? 'true' : 'false' }}" data-has-password="{{ $note['hasPassword'] ? 'true' : 'false' }}" data-pin-url="{{ route('notes.pin', $note['id']) }}" data-update-url="{{ route('notes.update', $note['id']) }}" data-delete-url="{{ route('notes.destroy', $note['id']) }}" data-title="{{ $note['title'] }}" data-content="{{ $note['content'] }}">
                                        
                                        {{-- Lock Overlay --}}
                                        @if ($note['locked'])
                                            <div class="locked-note-overlay" aria-hidden="true">
                                                <span class="locked-note-symbol">{!! icon('lock', $w=18, $h=18) !!}</i></span>
                                                <span>{{ $note['unlocked'] ? 'Password-protected note' : 'Enter your password to unlock' }}</span>
                                            </div>
                                        @endif

                                        {{-- Thumbnail --}}
                                        @if ($note['image'])
                                            <div class="note-thumbnail {{ $note['locked'] ? 'blur' : '' }}" role="img" aria-label="{{ $note['imageAlt'] }}">
                                                <img src="{{ asset('storage/' . $note['imagePath']) }}" alt="{{ $note['imageAlt'] }}" class="note-thumbnail-image">
                                                @if ($note['imageCount'] > 1)
                                                    <span class="note-image-count">+{{ $note['imageCount'] - 1 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        {{-- Body --}}
                                        <div class="note-card-content {{ $note['locked'] ? 'blur' : '' }}">
                                            
                                            <div class="note-meta-icons" aria-label="Note status">
                                                @if ($note['pinned'])<span class="status-icon status-pinned" title="Pinned" aria-label="Pinned"><i class="bi bi-pin-angle-fill"></i></span>@endif
                                                @if ($note['shared'])<span class="status-icon" title="Shared" aria-label="Shared"><i class="bi bi-people-fill"></i></span>@endif
                                                @if ($note['locked'])<span class="status-icon lock-status" title="Password protected" aria-label="Password protected"><i class="bi bi-lock-fill"></i></span>@endif
                                            </div>

                                            <div class="note-copy">
                                                <h3 class="note-title">{{ $note['title'] }}</h3>
                                                <p class="note-excerpt">{{ $note['content'] }}</p>
                                            </div>

                                            <div class="note-footer">
                                                <div class="note-labels">
                                                    @foreach ($note['labels'] as $label)
                                                        <span class="note-label">{{ $label }}</span>
                                                    @endforeach
                                                </div>
                                                <span class="note-updated">{{ $note['updated'] }}</span>
                                            </div>
                                            <div class="note-hover-actions">
                                                <button class="quick-action pin-note" type="button" aria-label="{{ $note['pinned'] ? 'Unpin note' : 'Pin note' }}" title="{{ $note['pinned'] ? 'Unpin' : 'Pin' }}"><i class="bi {{ $note['pinned'] ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i></button>
                                                <button class="quick-action lock-note" type="button" aria-label="{{ $note['hasPassword'] ? 'Manage note password' : 'Lock note' }}" title="{{ $note['hasPassword'] ? 'Change or remove password' : 'Lock note' }}">{!! icon('lock', $w=15, $h=15) !!}</button>
                                                <button class="quick-action edit-note" type="button" aria-label="Edit note" title="Edit"><i class="bi bi-pencil"></i></button>
                                                <button class="quick-action delete-note" type="button" aria-label="Delete note" title="Delete"><i class="bi bi-trash3"></i></button>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
               
                {{-- Empty Notification --}}
                <div class="empty-state d-none" id="emptyState">
                    <div class="empty-illustration" aria-hidden="true">
                        <div class="empty-sheet"><i class="bi bi-journal-plus"></i><span></span><span></span><span></span></div>
                        <span class="empty-spark">✦</span>
                    </div>
                    
                    <h3 id="emptyTitle"></h3>
                    <p id="emptyDescription"></p>
                    <button id="emptyAdd" class="button-primary create-note-trigger" type="button">
                        <i class="bi bi-plus-lg"></i> 
                        ADD A NOTE
                    </button>
                </div>
            </section>
        </main>
    </div>

    {{-- New Note Button --}}
    <button class="fab create-note-trigger rounded-circle" type="button" aria-label="Create new note" title="Create new note">
        
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.5" stroke="#20270f" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>

    </button>

    {{-- Create Note Dialog --}}
    <div class="modal fade" id="editorModal" tabindex="-1" aria-labelledby="editorTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered editor-dialog">
            
            <div class="modal-content editor-modal">
                
                {{-- Header --}}
                <div class="modal-header">
                    <div class="dropdown editor-actions-menu d-none" id="editorActionsMenu">
                        <button class="icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Note options">
                            <i class="bi bi-list"></i>
                        </button>
                        <ul class="dropdown-menu editor-dropdown-menu">
                            <li>
                                <button class="dropdown-item" type="button" id="editorPinAction">
                                    <i class="bi bi-pin-angle"></i>
                                    <span id="editorPinActionText">Pin note</span>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" type="button" id="editorLockAction">
                                    <i class="bi bi-lock" id="editorLockActionIcon"></i>
                                    <span id="editorLockActionText">Unlock note</span>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item editor-delete-action" type="button" id="editorDeleteAction">
                                    <i class="bi bi-trash3"></i>
                                    <span>Delete note</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <span class="editor-kicker" id="editorModalTitle">
                        Add New Note
                    </span>

                    <button type="button" class="icon-button ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Title and Content --}}
                <div class="modal-body">
                    <div id="noteImagesFrame" class="note-images-frame d-none">
                        <div id="existingNoteImages" class="note-image-gallery existing-note-images"></div>
                        <div id="noteImagePreview" class="note-image-gallery note-image-preview"></div>
                    </div>
                    <div class="note-text-frame">
                        <div class="note-editor-fields">
                            <label class="visually-hidden" for="editorNoteTitle">Title</label>
                            <input class="editor-title-input" id="editorNoteTitle" type="text" placeholder="Title">

                            <label class="visually-hidden" for="editorNoteContent">Content</label>
                            <textarea class="editor-content-input" id="editorNoteContent" placeholder="Start writing your note here..."></textarea>
                        </div>
                    </div>
                    <div class="note-image-picker">
                        <label for="noteImages" class="note-image-picker-label">
                            <i class="bi bi-image"></i>
                            Add images
                        </label>
                        <input id="noteImages" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer editor-footer">
                    <span class="autosave-status"><i class="bi bi-cloud-check"></i> Autosave</span>
                    
                    <button class="button-primary ms-auto" type="button" id="saveNote">Add</button>
                </div>
            </div>

        </div>
    </div>

    <div id="imageViewer" class="image-viewer d-none" role="dialog" aria-modal="true" aria-label="Image viewer">
        <button id="closeImageViewer" class="image-viewer-close" type="button" aria-label="Close image">
            <i class="bi bi-x-lg"></i>
        </button>
        <img id="imageViewerContent" src="" alt="Enlarged image">
    </div>

    {{-- Lock Note Dialog --}}
    <div class="modal fade" id="noteLockModal" tabindex="-1" aria-labelledby="noteLockTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered lock-dialog">
            <form class="modal-content lock-modal" id="noteLockForm">
                
                {{-- Header --}}
                <div class="modal-header">
                    <h2 class="editor-kicker ms-auto" id="noteLockTitle"></h2>
                    <button type="button" class="icon-button ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Content --}}
                <div class="modal-body">
                    <p id="noteLockDescription">Set a password to protect this note.</p>

                    <div id="oldPasswordField" class="lock-field d-none">
                        <label for="oldNotePassword">Current password</label>
                        <input id="oldNotePassword" type="password" autocomplete="current-password">
                    </div>

                    <div id="currentPasswordField" class="lock-field d-none">
                        <label for="notePassword">Note password</label>
                        <input id="notePassword" type="password" autocomplete="current-password">
                    </div>

                    <div id="newPasswordFields">
                        <div class="lock-field">
                            <label for="newNotePassword">New password</label>
                            <input id="newNotePassword" type="password" autocomplete="new-password">
                        </div>

                        <div class="lock-field">
                            <label for="confirmNotePassword">Confirm new password</label>
                            <input id="confirmNotePassword" type="password" autocomplete="new-password">
                        </div>
                    </div>

                    <div id="noteLockError" class="lock-error d-none" role="alert"></div>
                </div>

                <div class="modal-footer lock-actions">
                    <button id="disableNoteLock" class="button-secondary d-none" type="button">Remove password</button>
                    <button id="changeNotePassword" class="button-secondary d-none" type="button">Change password</button>
                    <button id="submitNoteLock" class="button-primary" type="submit">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered delete-dialog">
            <div class="modal-content delete-modal">
                <div class="modal-body text-center">
                    <span class="delete-symbol"><i class="bi bi-trash3"></i></span>
                    <h2 id="deleteTitle">Delete this note?</h2>
                    <p>This note will be removed from this list.</p>
                    <div class="delete-actions">
                        <button class="button-secondary" type="button" data-bs-dismiss="modal">Keep note</button>
                        <button class="button-danger" type="button" id="confirmDelete">Delete note</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
</body>
</html>
