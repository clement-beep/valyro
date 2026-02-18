@props([
  'label' => null,
  'name' => null,
  'type' => 'text',
  'value' => null,
  'placeholder' => null,
  'required' => false,
  'autocomplete' => null,
])

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
  @if($label)
    <label @if($name) for="{{ $name }}" @endif class="vy-label">{{ $label }}</label>
  @endif

  <input
    @if($name) name="{{ $name }}" id="{{ $name }}" @endif
    type="{{ $type }}"
    class="vy-field"
    value="{{ old($name ?? '', $value) }}"
    @if($placeholder) placeholder="{{ $placeholder }}" @endif
    @if($required) required @endif
    @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    {{ $attributes->except('class') }}
  />

  @if($name)
    @error($name)
      <div class="text-xs text-rose-300">{{ $message }}</div>
    @enderror
  @endif
</div>
