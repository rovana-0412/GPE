@props(['value' => 0])

<span {{ $attributes->class(['num']) }}>{{ number_format((float) $value, 0, ',', ' ') }}&nbsp;{{ config('gpe.currency_symbol') }}</span>
