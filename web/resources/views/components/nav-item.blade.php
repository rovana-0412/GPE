@props(['href' => null, 'icon', 'active' => false])

@if ($href)
    <a href="{{ $href }}" @class(['nav-item', 'is-active' => $active]) @if ($active) aria-current="page" @endif>
        <x-icon :name="$icon"/>
        <span>{{ $slot }}</span>
    </a>
@else
    <span class="nav-item is-soon" aria-disabled="true">
        <x-icon :name="$icon"/>
        <span>{{ $slot }}</span>
        <small>Bientôt</small>
    </span>
@endif
