@props(['signature' => true])

<div {{ $attributes->class(['logo']) }}>
    <svg class="logo__mark" viewBox="0 0 40 40" fill="none" stroke="currentColor"
         stroke-width="1.6" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
        <path d="M5 16 20 6l15 10Z"/>
        <path d="M8 20h24"/>
        <path d="M12 23v9M20 23v9M28 23v9"/>
        <path d="M6 35h28"/>
    </svg>
    <div>
        <span class="logo__word">GPE</span>
        @if ($signature)
            <span class="logo__sig">Gestion du Patrimoine de l'État</span>
        @endif
    </div>
</div>
