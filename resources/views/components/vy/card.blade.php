@props([
  'title' => null,
  'subtitle' => null,
  'header' => true,
])

<div {{ $attributes->merge(['class' => 'vy-card overflow-hidden']) }}>
  @if($header && ($title || $subtitle))
    <div class="p-6 vy-card-h">
      @if($title) <div class="text-lg font-semibold text-white">{{ $title }}</div> @endif
      @if($subtitle) <div class="text-sm vy-muted mt-1">{{ $subtitle }}</div> @endif
    </div>
  @endif

  <div class="p-6">
    {{ $slot }}
  </div>
</div>
