@props([
  'label' => null,
  'name' => null,
  'value' => null,
  'required' => false,
])

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
  @if($label)
    <label @if($name) for="{{ $name }}" @endif class="vy-label">{{ $label }}</label>
  @endif

  <select
    @if($name) name="{{ $name }}" id="{{ $name }}" @endif
    class="vy-select"
    @if($required) required @endif
    {{ $attributes->except('class') }}
  >
    {{ $slot }}
  </select>

  @if($name)
    @error($name)
      <div class="text-xs text-rose-300">{{ $message }}</div>
    @enderror
  @endif
</div>
