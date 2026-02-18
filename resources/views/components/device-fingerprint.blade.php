{{-- resources/views/components/device-fingerprint.blade.php --}}
<div class="hidden">
  <input type="hidden" name="device_id" id="device_id" value="">
  <input type="hidden" name="device_tz" id="device_tz" value="">
  <input type="hidden" name="device_locale" id="device_locale" value="">
</div>

<script>
(function () {
  const KEY = 'valyro_device_id_v1';

  function safeSet(id, val){
    const el = document.getElementById(id);
    if(el) el.value = val || '';
  }

  try {
    let deviceId = '';
    try { deviceId = localStorage.getItem(KEY) || ''; } catch(e) {}

    if(!deviceId){
      deviceId = (crypto?.randomUUID ? crypto.randomUUID() : (Date.now() + '-' + Math.random().toString(16).slice(2)));
      try { localStorage.setItem(KEY, deviceId); } catch(e) {}
    }

    const tz = (Intl?.DateTimeFormat?.().resolvedOptions?.().timeZone) ? Intl.DateTimeFormat().resolvedOptions().timeZone : '';
    const locale = (navigator?.language || '');

    safeSet('device_id', deviceId);
    safeSet('device_tz', tz);
    safeSet('device_locale', locale);
  } catch(e) {}
})();
</script>
