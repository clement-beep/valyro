@props([
  'variant' => 'default', // default|ok|warn|admin
])

@php
  $cls = 'vy-pill';
  if($variant === 'ok') $cls .= ' vy-pill-ok';
  if($variant === 'warn') $cls .= ' vy-pill-warn';
  if($variant === 'admin') $cls .= ' vy-pill-admin';
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}>
  {{ $slot }}
</span>
