<div class="sec-hd">
  <div>
    <div class="sec-title">📁 Projets</div>
    <div class="sec-sub" id="proj-count">—</div>
  </div>
  <button class="btn-prim" onclick="Admin.openProjModal()">+ Nouveau projet</button>
</div>

<!-- Filtres -->
<div class="toolbar">
  <div class="search-box">
    <span>🔍</span>
    <input type="text" id="proj-search" placeholder="Code ou description…" oninput="Admin.filterProjects()">
  </div>
  <select class="fi" id="proj-filter-status" onchange="Admin.loadProjects()">
    <option value="">Tous les statuts</option>
    <option value="pending">En attente</option>
    <option value="progress">En cours</option>
    <option value="done">Terminé</option>
  </select>
  <select class="fi" id="proj-filter-user" onchange="Admin.loadProjects()">
    <option value="">Tous les métrologues</option>
  </select>
</div>

<!-- Tableau -->
<div class="card">
  <table class="tbl" id="proj-table">
    <thead>
      <tr>
        <th>Code</th>
        <th>Métrologue</th>
        <th>Statut</th>
        <th>Priorité</th>
        <th>Tâches</th>
        <th>Échéance</th>
        <th>Score</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="proj-body">
      <tr><td colspan="8" class="tbl-empty">Chargement…</td></tr>
    </tbody>
  </table>
</div>

<!-- Modal Projet -->
<div class="overlay" id="projModal" onclick="if(event.target===this)Admin.closeProjModal()">
  <div class="modal modal-md">
    <div class="modal-hd">
      <div>
        <div class="modal-title" id="pm-title">📁 Nouveau Projet</div>
        <div class="modal-sub" id="pm-code-display"></div>
      </div>
      <button class="modal-x" onclick="Admin.closeProjModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="pm-id">
      <div class="fg"><label class="fl">Nom / Code du projet *</label>
      <input class="fi" type="text" id="pm-name" placeholder="Ex: MICRO-875…" maxlength="60">
      </div>
      <div class="fg"><label class="fl">Métrologue assigné *</label>
        <select class="fi" id="pm-user">
          <option value="">— Sélectionner un métrologue —</option>
        </select>
      </div>
      <div class="fr2">
        <div class="fg"><label class="fl">Priorité</label>
          <select class="fi" id="pm-priority">
            <option>Normale</option><option>Haute</option><option>Urgente</option>
          </select>
        </div>
        <div class="fg"><label class="fl">Date de début</label><input class="fi" type="date" id="pm-start"></div>
      </div>
      <div class="fg"><label class="fl">Date d'échéance *</label><input class="fi" type="date" id="pm-due"></div>
      <div class="fg"><label class="fl">Description</label>
        <textarea class="fi" id="pm-desc" rows="3" placeholder="Description du projet…" style="resize:vertical"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-danger" id="pm-del-btn" style="display:none" onclick="Admin.deleteProject()">🗑 Supprimer</button>
      <button class="btn-sec" onclick="Admin.closeProjModal()">Annuler</button>
      <button class="btn-prim" onclick="Admin.saveProject()">✓ Enregistrer</button>
    </div>
  </div>
</div>

<!-- Modal Détail Projet (vue tâches) -->
<div class="overlay" id="projDetailModal" onclick="if(event.target===this)Admin.closeProjDetail()">
  <div class="modal modal-md">
    <div class="modal-hd">
      <div>
        <div class="modal-title" id="pd-title">Détail projet</div>
        <div class="modal-sub" id="pd-sub"></div>
      </div>
      <button class="modal-x" onclick="Admin.closeProjDetail()">✕</button>
    </div>
    <div class="modal-body" id="pd-body">Chargement…</div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="Admin.closeProjDetail()">Fermer</button>
    </div>
  </div>
</div>
