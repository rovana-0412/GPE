@props(['icon' => 'clock', 'title', 'text' => null])

<div class="empty-state">
    <x-icon :name="$icon"/>
    <p class="empty-state__title">{{ $title }}</p>
    @if ($text)
        <p class="empty-state__text">{{ $text }}</p>
    @endif
</div>
