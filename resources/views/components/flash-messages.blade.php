@php
  $success = session('success');
  $error = session('error');
@endphp

@if ($success)
  <div class="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-emerald-100">
    {{ $success }}
  </div>
@endif

@if ($error)
  <div class="mb-6 rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-rose-100">
    {{ $error }}
  </div>
@endif

@if ($errors->any())
  <div class="mb-6 rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-amber-100">
    <div class="font-semibold mb-1">Erreur</div>
    <ul class="list-disc ml-5">
      @foreach ($errors->all() as $e)
        <li class="text-sm">{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif
