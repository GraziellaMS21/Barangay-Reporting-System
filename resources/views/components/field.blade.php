@props(['label', 'hint' => null, 'name' => null, 'error' => null])
<div class="review-field">
  <label for="{{ $name }}">
    {{ $label }}
    @if($hint)
      <span class="field-hint">{{ $hint }}</span>
    @endif
  </label>
  {{ $slot }}
  @if($error)
    <span class="field-error">{{ $error }}</span>
  @endif
</div>