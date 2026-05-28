{{--
  Local Environment Badge
  Visible only when APP_ENV=local.
  Fades to 25% opacity after 5 seconds. Never blocks UI (pointer-events:none).
  Confirms: correct server, correct build, local vs prod.
--}}
@if(app()->environment('local'))
<div id="local-env-badge"
     style="
         position:fixed;bottom:0;left:0;right:0;z-index:99999;
         background:#003594;border-top:2px solid rgba(255,209,0,0.4);
         padding:4px 0;pointer-events:none;
         transition:opacity 0.8s ease;
     ">
    <p style="
        color:#FFD100;text-align:center;margin:0;
        font-family:ui-monospace,'Cascadia Code',monospace;
        font-size:10px;font-weight:700;letter-spacing:.12em;
    ">
        LOCAL BUILD
        &nbsp;·&nbsp; {{ config('app.url') }}
        &nbsp;·&nbsp; {{ config('database.connections.mysql.database') }}
        &nbsp;·&nbsp; {{ now()->format('H:i:s') }}
    </p>
</div>
<script>
    setTimeout(function() {
        var b = document.getElementById('local-env-badge');
        if (b) b.style.opacity = '0.25';
    }, 5000);
</script>
@endif
