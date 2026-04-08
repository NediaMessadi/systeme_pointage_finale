<div class="sec-hd">
  <div>
    <div class="sec-title">📅 Planning Hebdomadaire</div>
    <div class="sec-sub">Suivi des tâches par métrologue — mise à jour en temps réel</div>
  </div>
  <div class="wk-nav">
    <button class="wk-btn" onclick="Admin.weekNav(-1)">‹</button>
    <div class="wk-lbl" id="week-lbl">Semaine courante</div>
    <button class="wk-btn" onclick="Admin.weekNav(+1)">›</button>
    <button class="tb-btn" style="margin-left:6px" onclick="Admin.weekNav(0)">Aujourd'hui</button>
  </div>
</div>

<div class="card">
  <div style="overflow-x:auto">
    <table class="tbl" id="planning-table">
      <thead>
        <tr>
          <th style="min-width:150px">Métrologue</th>
          <th class="tc" id="ph-0">Lundi</th>
          <th class="tc" id="ph-1">Mardi</th>
          <th class="tc" id="ph-2">Mercredi</th>
          <th class="tc" id="ph-3">Jeudi</th>
          <th class="tc" id="ph-4">Vendredi</th>
          <th>Score</th>
        </tr>
      </thead>
      <tbody id="planning-body">
        <tr><td colspan="7" class="tbl-empty">Chargement…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-hd"><div class="card-title">📌 Légende</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;padding:12px">
    <span class="chip chip-F">F — Finaliser</span>
    <span class="chip chip-V">V — Vérifier</span>
    <span class="chip chip-C">C — envoyer</span>
    <span class="chip chip-R">R — commander</span>
    <span class="chip chip-done">✓ Terminé</span>
    <span class="chip chip-wait">En attente</span>
    <span class="pres-status pres-abs">Absent</span>
  </div>
</div>
