<div class="sec-hd">
  <div>
    <div class="sec-title">⚙ Système</div>
    <div class="sec-sub">Configuration et paramètres de la plateforme</div>
  </div>
</div>

<div class="two-col">

  <!-- État base de données -->
  <div class="card">
    <div class="card-hd"><div class="card-title">🗄️ Base de données</div></div>
    <div class="card-body" id="sys-db">Chargement…</div>
  </div>

  <!-- Config RFID -->
  <div class="card">
    <div class="card-hd"><div class="card-title">📡 RFID — Pointage automatique</div></div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--txt2);margin-bottom:12px">
        Endpoint pour ESP32 : <code class="code-inline">POST api/rfid.php</code><br>
        Paramètre : <code class="code-inline">?uid=XXXX</code>
      </p>
      <div class="fg" style="margin-bottom:10px">
        <label class="fl">Heure limite — Retard (HH:MM)</label>
        <input class="fi" type="time" id="sys-late-limit" value="08:15">
      </div>
      <button class="btn-prim" onclick="Admin.saveSysSettings()">💾 Enregistrer</button>
    </div>
  </div>

  <!-- Scoring -->
  <div class="card">
    <div class="card-hd"><div class="card-title">🎯 Système de score</div></div>
    <div class="card-body">
      <div class="info-item"><span class="info-label">Avant l'échéance</span><span class="badge badge-g">+1 point</span></div>
      <div class="info-item"><span class="info-label">Le jour de l'échéance</span><span class="badge badge-b">0 point</span></div>
      <div class="info-item"><span class="info-label">Après l'échéance</span><span class="badge badge-r">−1 point</span></div>
      <p style="font-size:12px;color:var(--txt3);margin-top:12px">Le score est calculé automatiquement à la complétion du projet.</p>
    </div>
  </div>

  <!-- Informations système -->
  <div class="card">
    <div class="card-hd"><div class="card-title">ℹ️ Informations</div></div>
    <div class="card-body">
      <div class="info-item"><span class="info-label">Version</span><span class="info-val">3.0.0</span></div>
      <div class="info-item"><span class="info-label">PHP</span><span class="info-val"><?= PHP_VERSION ?></span></div>
      <div class="info-item"><span class="info-label">Serveur</span><span class="info-val"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP' ?></span></div>
      <div class="info-item"><span class="info-label">Base de données</span><span class="info-val" id="sys-db-version">—</span></div>
      <div class="info-item"><span class="info-label">Fuseau horaire</span><span class="info-val">Africa/Tunis</span></div>
    </div>
  </div>

</div>
