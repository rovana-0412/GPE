@props(['name'])

<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('grid')
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            @break
        @case('building')
            <path d="M4 21V5l8-2 8 2v16M9 9h2M13 9h2M9 13h2M13 13h2M10 21v-4h4v4"/>
            @break
        @case('tag')
            <path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1"/>
            @break
        @case('link')
            <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>
            @break
        @case('swap')
            <path d="M4 8h14l-3-3M20 16H6l3 3"/>
            @break
        @case('wrench')
            <path d="M14.5 6.5a4 4 0 0 0 5 5L11 20a2.1 2.1 0 0 1-3-3z"/>
            @break
        @case('landmark')
            <path d="M3 9l9-5 9 5zM5 9v9M10 9v9M14 9v9M19 9v9M3 21h18"/>
            @break
        @case('sitemap')
            <rect x="9" y="3" width="6" height="5"/><rect x="3" y="16" width="6" height="5"/>
            <rect x="15" y="16" width="6" height="5"/><path d="M12 8v4M6 16v-4h12v4"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('chart')
            <path d="M4 20V4M4 20h16M8 16v-5M12 16V8M16 16v-3"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('logout')
            <path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>
            @break
    @endswitch
</svg>
