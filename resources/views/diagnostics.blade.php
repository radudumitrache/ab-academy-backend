<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Connection diagnostics</title>
<style>
  :root {
    --ground:#e9edf1; --surface:#fff; --sunken:#dde4ea; --line:#c3ced8;
    --ink:#141d26; --muted:#5a6b7a; --accent:#2c6ca6;
    --pass:#197a54; --fail:#b4362a; --warn:#96631a;
    --mono: ui-monospace,"SF Mono","Roboto Mono",Menlo,Consolas,monospace;
    --sans: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,system-ui,sans-serif;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --ground:#0d141b; --surface:#151f28; --sunken:#0a1017; --line:#2a3945;
      --ink:#dce6ef; --muted:#8296a8; --accent:#64a8de;
      --pass:#46c391; --fail:#f0705d; --warn:#ddaa46;
    }
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--ground);color:var(--ink);font-family:var(--sans);
       font-size:16px;line-height:1.55;-webkit-text-size-adjust:100%}
  .wrap{max-width:600px;margin:0 auto;padding:22px 16px 56px}
  .eyebrow{font-family:var(--mono);font-size:11px;letter-spacing:.16em;
           text-transform:uppercase;color:var(--accent);margin:0 0 9px}
  h1{font-family:var(--mono);font-size:clamp(21px,5.5vw,27px);font-weight:600;
     letter-spacing:-.015em;line-height:1.2;margin:0 0 10px;text-wrap:balance}
  .lede{margin:0 0 22px;color:var(--muted);font-size:15px}
  h2{font-family:var(--mono);font-size:12px;letter-spacing:.13em;text-transform:uppercase;
     color:var(--muted);margin:26px 0 10px;font-weight:600}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:9px;
        padding:14px;margin-bottom:9px}
  .test{display:grid;grid-template-columns:20px 1fr auto;gap:11px;align-items:baseline}
  .g{font-family:var(--mono);font-weight:700;text-align:center}
  .g.idle{color:var(--muted)} .g.pass{color:var(--pass)}
  .g.fail{color:var(--fail)} .g.warn{color:var(--warn)}
  .t-name{font-weight:600;font-size:15px}
  .t-note{font-size:13px;color:var(--muted);margin-top:3px;grid-column:2/4}
  .t-meta{font-family:var(--mono);font-size:12.5px;color:var(--muted);
          font-variant-numeric:tabular-nums;white-space:nowrap}
  dl{margin:0;display:grid;grid-template-columns:auto 1fr;gap:7px 14px;font-size:14px}
  dt{font-family:var(--mono);font-size:11px;letter-spacing:.1em;text-transform:uppercase;
     color:var(--muted);padding-top:3px}
  dd{margin:0;font-family:var(--mono);word-break:break-word}
  .flag{border-left:3px solid var(--warn)}
  .ok{border-left:3px solid var(--pass)}
  button{font-family:var(--sans);font-size:15px;font-weight:600;padding:12px 18px;
         border-radius:7px;border:none;background:var(--accent);color:#fff;
         cursor:pointer;width:100%;min-height:48px}
  button:disabled{opacity:.5;cursor:progress}
  button:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
  .note{font-size:13px;color:var(--muted);margin:18px 0 0}
  code{font-family:var(--mono);font-size:.9em}
</style>
</head>
<body>
<div class="wrap">
  <p class="eyebrow">Connection diagnostics</p>
  <h1>What this server received from you</h1>
  <p class="lede">Everything below describes this device's connection to the API. Screenshot the whole page and send it over — no tokens or cookies appear anywhere on it.</p>

  <h2>Live tests</h2>
  <div class="card">
    <div class="test">
      <span class="g idle" id="g1">·</span>
      <span><span class="t-name">Reach the API</span></span>
      <span class="t-meta" id="m1">—</span>
      <span class="t-note" id="n1">Waiting to run.</span>
    </div>
  </div>
  <div class="card">
    <div class="test">
      <span class="g idle" id="g2">·</span>
      <span><span class="t-name">Token survives the trip</span></span>
      <span class="t-meta" id="m2">—</span>
      <span class="t-note" id="n2">Waiting to run.</span>
    </div>
  </div>
  <div class="card">
    <div class="test">
      <span class="g idle" id="g3">·</span>
      <span><span class="t-name">OPTIONS verb allowed</span></span>
      <span class="t-meta" id="m3">—</span>
      <span class="t-note" id="n3">Waiting to run.</span>
    </div>
  </div>
  <button id="run">Run tests</button>

  <h2>Network path</h2>
  @php $signals = $report['proxy_signals'] ?? []; @endphp
  <div class="card {{ count($signals) ? 'flag' : 'ok' }}">
    @if (count($signals))
      <p style="margin:0 0 11px;font-size:14.5px">A proxy or content filter sits between this device and the server. That is the most likely reason the app cannot sign in while pages load normally.</p>
      <dl>
        @foreach ($signals as $name => $value)
          <dt>{{ $name }}</dt><dd>{{ $value }}</dd>
        @endforeach
      </dl>
    @else
      <p style="margin:0;font-size:14.5px">No proxy detected. The connection reaches the server directly.</p>
    @endif
  </div>

  <h2>Connection facts</h2>
  <div class="card">
    <dl>
      <dt>Your IP</dt><dd>{{ $report['client']['ip'] }}</dd>
      <dt>Over HTTPS</dt><dd>{{ $report['request']['over_https'] ? 'yes' : 'no' }}</dd>
      <dt>Host</dt><dd>{{ $report['request']['http_host'] }}</dd>
      <dt>Server time</dt><dd>{{ $report['server_time'] }}</dd>
    </dl>
  </div>

  <p class="note">This page runs on the API's own domain, so it cannot test cross-origin preflights. It does test the two faults that actually strand a device: a stripped <code>Authorization</code> header and a blocked <code>OPTIONS</code> request.</p>
</div>

<script>
(function () {
  "use strict";
  var TOKEN = "Bearer probe-" + Math.random().toString(36).slice(2, 12);

  function set(i, state, meta, note) {
    var g = document.getElementById("g" + i);
    g.className = "g " + state;
    g.textContent = { idle: "·", run: "●", pass: "✓", fail: "✗", warn: "!" }[state];
    document.getElementById("m" + i).textContent = meta;
    document.getElementById("n" + i).textContent = note;
  }

  function call(path, opts) {
    var t0 = Date.now();
    var ctl = new AbortController();
    var timer = setTimeout(function () { ctl.abort(); }, 15000);
    opts = opts || {}; opts.signal = ctl.signal; opts.cache = "no-store";
    return fetch(path, opts).then(function (res) {
      clearTimeout(timer);
      return res.text().then(function (body) {
        var j = null; try { j = JSON.parse(body); } catch (e) {}
        return { status: res.status, json: j, ms: Date.now() - t0, err: null };
      });
    }).catch(function (e) {
      clearTimeout(timer);
      return { status: 0, json: null, ms: Date.now() - t0,
               err: e && e.name === "AbortError" ? "timed out" : "blocked" };
    });
  }

  document.getElementById("run").addEventListener("click", function () {
    var btn = this;
    btn.disabled = true; btn.textContent = "Running…";
    [1, 2, 3].forEach(function (i) { set(i, "run", "…", "Running."); });

    call("/api/hello").then(function (r) {
      if (r.err) set(1, "fail", "ERR", "Cannot reach the API from this device (" + r.err + ").");
      else if (r.status === 200) set(1, "pass", r.status + " · " + r.ms + "ms", "API reachable.");
      else set(1, "warn", r.status + " · " + r.ms + "ms", "Reached the server but got HTTP " + r.status + ".");

      return call("/api/diagnostics", { headers: { "Authorization": TOKEN } });
    }).then(function (r) {
      if (r.err) set(2, "fail", "ERR", "Request blocked before reaching the server.");
      else if (!r.json || !r.json.authorization) set(2, "warn", r.status + " · " + r.ms + "ms", "Unexpected response from the server.");
      else if (r.json.authorization.received) set(2, "pass", r.status + " · " + r.ms + "ms", "The token arrived intact. Nothing is stripping it.");
      else set(2, "fail", r.status + " · " + r.ms + "ms", "The token was removed in transit. This breaks every signed-in request while leaving normal pages working.");

      return call("/api/cors-test", { method: "OPTIONS" });
    }).then(function (r) {
      if (r.err) set(3, "fail", "ERR", "The OPTIONS verb is being dropped. The app can never authorise a request on this connection.");
      else if (r.status >= 200 && r.status < 300) set(3, "pass", r.status + " · " + r.ms + "ms", "OPTIONS passes through normally.");
      else set(3, "warn", r.status + " · " + r.ms + "ms", "OPTIONS returned HTTP " + r.status + ".");

      btn.disabled = false; btn.textContent = "Run again";
    });
  });
})();
</script>
</body>
</html>
