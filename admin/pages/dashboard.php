<div class="sec-hd">
  <div>
    <div class="sec-title">📊 Tableau de bord</div>
    <div class="sec-sub">Vue d'ensemble — <span id="dash-date"></span></div>
  </div>
  <button class="tb-btn" onclick="Admin.loadDashboard()">🔄 Actualiser</button>
</div>

<!-- KPI CARDS -->
<div class="kpi-row" id="dash-kpi">
  <div class="kpi kpi-r"><div class="kpi-ico">📁</div><div class="kpi-val" id="d-proj">—</div><div class="kpi-lbl">Projets actifs</div></div>
  <div class="kpi kpi-g"><div class="kpi-ico">👷</div><div class="kpi-val" id="d-users">—</div><div class="kpi-lbl">Métrologues actifs</div></div>
  <div class="kpi kpi-o"><div class="kpi-ico">✅</div><div class="kpi-val" id="d-tasks">—</div><div class="kpi-lbl">Tâches terminées</div></div>
  <div class="kpi kpi-b"><div class="kpi-ico">⚠️</div><div class="kpi-val" id="d-abs">—</div><div class="kpi-lbl">Absences aujourd'hui</div></div>
</div>

<!-- PLANNING + CLASSEMENT -->
<div class="dash-grid">

  <!-- Planning semaine -->
  <div class="card card-wide">
    <div class="card-hd">
      <div class="card-title">📅 Planning de la semaine</div>
      <div class="card-sub" id="d-week-range"></div>
    </div>
    <div style="overflow-x:auto">
      <table class="tbl" id="dash-plan-table">
        <thead>
          <tr>
            <th style="min-width:140px">Métrologue</th>
            <th class="tc">Lundi</th>
            <th class="tc">Mardi</th>
            <th class="tc">Mercredi</th>
            <th class="tc">Jeudi</th>
            <th class="tc">Vendredi</th>
            <th>Score</th>
          </tr>
        </thead>
        <tbody id="dash-plan-body">
          <tr><td colspan="7" class="tbl-empty">Chargement…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top 3 + Countdown -->
  <div class="dash-side">

    <div class="card">
      <div class="card-hd"><div class="card-title">🏆 Top 3 — Trimestre</div></div>
      <div class="card-body">
        <div class="podium" id="dash-podium"></div>
        <div id="dash-leaderboard"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-hd"><div class="card-title">⏱ Fin de trimestre</div></div>
      <div class="card-body">
        <div class="timer-cd">
          <div class="timer-lbl" id="timer-qlbl">—</div>
          <div class="timer-digs">
            <div class="t-unit"><div class="t-num" id="t-days">—</div><div class="t-ulbl">Jours</div></div>
            <div class="t-sep">:</div>
            <div class="t-unit"><div class="t-num" id="t-hours">—</div><div class="t-ulbl">H</div></div>
            <div class="t-sep">:</div>
            <div class="t-unit"><div class="t-num" id="t-mins">—</div><div class="t-ulbl">Min</div></div>
          </div>
        </div>
        <div style="margin-top:10px">
          <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--txt3);margin-bottom:4px">
            <span id="timer-qlbl2"></span>
            <span style="color:var(--red);font-weight:700" id="timer-qpct"></span>
          </div>
          <div class="pbar-bg"><div class="pbar-fill" id="q-pbar" style="width:0%"></div></div>
        </div>
        <button class="btn-full btn-prim" style="margin-top:14px" onclick="Admin.openChampModal()">🎉 Résultat trimestriel</button>
      </div>
    </div>

  </div>
</div>

<!-- Modal résultat trimestriel -->
<div class="overlay" id="champModal" onclick="if(event.target===this)Admin.closeChampModal()">
  <div class="modal modal-sm">
    <div class="modal-hd">
      <div class="modal-title">🏆 Résultat Trimestriel</div>
      <button class="modal-x" onclick="Admin.closeChampModal()">✕</button>
    </div>
    <div class="modal-body" id="champ-body" style="text-align:center;padding:24px 20px">
      <div class="champ-trophy">🏅</div>
      <div id="champ-content">Chargement…</div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="Admin.closeChampModal()">Fermer</button>
      <button class="btn-prim" onclick="Admin.saveChampion()">✓ Publier le résultat</button>
    </div>
  </div>
</div>
