<div class="sec-hd">
  <div>
    <div class="sec-title">🔒 Sécurité</div>
    <div class="sec-sub">Journaux de connexion et activité système</div>
  </div>
  <button class="tb-btn" onclick="Admin.loadLogs()">🔄 Actualiser</button>
</div>

<div class="two-col">

  <!-- Journaux d'activité -->
  <div class="card" style="grid-column:1/-1">
    <div class="card-hd">
      <div class="card-title">📋 Journaux système</div>
      <div style="display:flex;gap:8px">
        <select class="fi" id="log-filter-type" onchange="Admin.loadLogs()" style="width:130px">
          <option value="">Tous les types</option>
          <option value="ok">✅ Succès</option>
          <option value="warn">⚠️ Avertissement</option>
          <option value="err">❌ Erreur</option>
        </select>
      </div>
    </div>
    <table class="tbl">
      <thead><tr><th style="width:40px"></th><th>Message</th><th>Type</th><th>Date</th></tr></thead>
      <tbody id="logs-body">
        <tr><td colspan="4" class="tbl-empty">Chargement…</td></tr>
      </tbody>
    </table>
  </div>

</div>
