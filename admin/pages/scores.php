<div class="sec-hd">
  <div>
    <div class="sec-title">🏆 Scores & Classement</div>
    <div class="sec-sub" id="scores-quarter-lbl">—</div>
  </div>
  <div style="display:flex;gap:8px">
    <button class="tb-btn" onclick="Admin.loadScores()">🔄 Actualiser</button>
    <button class="btn-danger-outline" onclick="Admin.confirmResetScores()">🔄 Réinitialiser scores</button>
  </div>
</div>

<!-- Podium Top 3 -->
<div class="card" style="margin-bottom:16px">
  <div class="card-hd"><div class="card-title">🥇 Podium du trimestre</div></div>
  <div class="card-body">
    <div class="podium-large" id="scores-podium">
      <div style="text-align:center;color:var(--txt3)">Chargement…</div>
    </div>
  </div>
</div>

<!-- Classement complet -->
<div class="card">
  <div class="card-hd">
    <div class="card-title">📊 Classement complet</div>
    <div class="card-sub">Score = projets terminés avant (+1) ou après (-1) l'échéance</div>
  </div>
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:50px">#</th>
        <th>Métrologue</th>
        <th>Niveau</th>
        <th class="tc">Projets terminés</th>
        <th class="tc">Absences</th>
        <th class="tc">Score</th>
        <th class="tc">Tendance</th>
      </tr>
    </thead>
    <tbody id="scores-body">
      <tr><td colspan="7" class="tbl-empty">Chargement…</td></tr>
    </tbody>
  </table>
</div>

<!-- Historique trimestres -->
<div class="card" style="margin-top:16px">
  <div class="card-hd"><div class="card-title">📜 Historique des champions</div></div>
  <table class="tbl">
    <thead><tr><th>Trimestre</th><th>Champion</th><th>Score</th><th>Date</th></tr></thead>
    <tbody id="scores-history">
      <tr><td colspan="4" class="tbl-empty">Aucun résultat publié</td></tr>
    </tbody>
  </table>
</div>
