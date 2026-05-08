<div class="sec-hd">
  <div>
    <div class="sec-title">📋 Pointages</div>
    <div class="sec-sub" id="pt-sub">Gestion des présences · RFID activé</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <div class="rfid-badge" id="rfid-status">📡 RFID actif</div>
    <button class="btn-sec" onclick="Admin.openShiftModal()">🕒 Affecter horaires</button>
    <button class="btn-prim" onclick="Admin.openPtModal()">+ Pointage manuel</button>
  </div>
</div>

<!-- Filtres -->
<div class="toolbar">
  <input type="date" class="fi" id="pt-date" value="<?= date('Y-m-d') ?>" onchange="Admin.loadPointages()" style="width:160px">
  <select class="fi" id="pt-filter-user" onchange="Admin.loadPointages()">
    <option value="">Tous les métrologues</option>
  </select>
  <select class="fi" id="pt-filter-status" onchange="Admin.loadPointages()">
    <option value="">Tous les statuts</option>
    <option value="ok">Présent</option>
    <option value="late">Retard</option>
    <option value="abs">Absent</option>
  </select>
</div>

<!-- Résumé du jour -->
<div class="kpi-row" id="pt-kpi" style="margin-bottom:16px">
  <div class="kpi kpi-g"><div class="kpi-ico">✅</div><div class="kpi-val" id="pt-ok">—</div><div class="kpi-lbl">Présents</div></div>
  <div class="kpi kpi-o"><div class="kpi-ico">⚠️</div><div class="kpi-val" id="pt-late">—</div><div class="kpi-lbl">En retard</div></div>
  <div class="kpi kpi-r"><div class="kpi-ico">❌</div><div class="kpi-val" id="pt-abs">—</div><div class="kpi-lbl">Absents</div></div>
  <div class="kpi kpi-b"><div class="kpi-ico">📡</div><div class="kpi-val" id="pt-rfid">—</div><div class="kpi-lbl">Via RFID</div></div>
</div>

<!-- Horaires assignés -->
<div class="card" style="margin-bottom:16px">
  <div class="card-hd" style="margin-bottom:10px">
    <div>
      <div class="card-title">🕒 Horaires de travail assignés</div>
      <div class="card-sub" id="pt-shift-sub">Pour la date sélectionnée</div>
    </div>
  </div>
  <div id="pt-shift-body" class="tbl-empty" style="padding:8px 0">Chargement…</div>
</div>

<!-- Tableau -->
<div class="card">
  <table class="tbl">
    <thead>
      <tr>
        <th>Métrologue</th>
        <th>Date</th>
        <th>Arrivée</th>
        <th>Départ</th>
        <th>Durée</th>
        <th>Statut</th>
        <th>Source</th>
        <th>Note</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="pt-body">
      <tr><td colspan="9" class="tbl-empty">Chargement…</td></tr>
    </tbody>
  </table>
</div>

<!-- Modal Pointage -->
<div class="overlay" id="ptModal" onclick="if(event.target===this)Admin.closePtModal()">
  <div class="modal modal-sm">
    <div class="modal-hd">
      <div class="modal-title" id="ptm-title">📋 Pointage manuel</div>
      <button class="modal-x" onclick="Admin.closePtModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ptm-id">
      <div class="fg"><label class="fl">Métrologue *</label>
        <select class="fi" id="ptm-user"><option value="">— Sélectionner —</option></select>
      </div>
      <div class="fg"><label class="fl">Date *</label><input class="fi" type="date" id="ptm-date" value="<?= date('Y-m-d') ?>"></div>
      <div class="fg"><label class="fl">Statut *</label>
        <select class="fi" id="ptm-status">
          <option value="ok">Présent</option>
          <option value="late">Retard</option>
          <option value="abs">Absent</option>
        </select>
      </div>
      <div class="fr2" id="ptm-times">
        <div class="fg"><label class="fl">Arrivée</label><input class="fi" type="time" id="ptm-arrivee"></div>
        <div class="fg"><label class="fl">Départ</label><input class="fi" type="time" id="ptm-depart"></div>
      </div>
      <div class="fg"><label class="fl">Note</label><input class="fi" id="ptm-note" placeholder="Ex: Retard justifié, Maladie…"></div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="Admin.closePtModal()">Annuler</button>
      <button class="btn-prim" onclick="Admin.savePointage()">✓ Enregistrer</button>
    </div>
  </div>
</div>

<!-- Modal Affectation horaires -->
<div class="overlay" id="shiftModal" onclick="if(event.target===this)Admin.closeShiftModal()">
  <div class="modal modal-md">
    <div class="modal-hd">
      <div>
        <div class="modal-title" id="shift-title">🕒 Affecter un horaire de travail</div>
        <div class="modal-sub">Choisissez une date, un métrologue (ou tous), puis l'heure assignée</div>
      </div>
      <button class="modal-x" onclick="Admin.closeShiftModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="fr2">
        <div class="fg"><label class="fl">Date *</label><input class="fi" type="date" id="shift-date" onchange="Admin.refreshShiftModalList()"></div>
        <div class="fg"><label class="fl">Métrologue *</label>
          <select class="fi" id="shift-user">
            <option value="">Tous les métrologues actifs</option>
          </select>
        </div>
      </div>
      <div class="fg"><label class="fl">Heure assignée *</label><input class="fi" type="time" id="shift-time" value="08:00"></div>
      <div class="fg" style="margin-top:14px">
        <label class="fl">Affectations déjà définies pour cette date</label>
        <div id="shift-existing" class="shift-list">Chargement…</div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="Admin.closeShiftModal()">Annuler</button>
      <button class="btn-prim" onclick="Admin.saveShiftAssignment()">✓ Enregistrer</button>
    </div>
  </div>
</div>
