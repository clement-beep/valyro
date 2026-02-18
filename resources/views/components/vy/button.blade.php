@props([
  'variant' => 'default', // default|primary|indigo|emerald|rose
  'type' => 'button',
])

@php
  $cls = 'vy-btn';
  if($variant === 'primary') $cls .= ' vy-btn-primary';
  if($variant === 'indigo') $cls .= ' vy-btn-indigo';
  if($variant === 'emerald') $cls .= ' vy-btn-emerald';
  if($variant === 'rose') $cls .= ' vy-btn-rose';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>
  {{ $slot }}
</button>
