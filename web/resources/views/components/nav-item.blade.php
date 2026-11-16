@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @class(['nav-item', 'is-active' => $active]) @if ($active) aria-current="page" @endif>
    <x-icon :name="$icon"/>
    <span>{{ $slot }}</span>
</a>
