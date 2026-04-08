<div class="sec-hd">
  <div>
    <div class="sec-title">👷 Métrologues</div>
    <div class="sec-sub" id="metro-count">—</div>
  </div>
  <button class="btn-prim" onclick="Admin.openUserModal()">+ Nouveau métrologue</button>
</div>

<!-- Filtres -->
<div class="toolbar">
  <div class="search-box">
    <span>🔍</span>
    <input type="text" id="metro-search" placeholder="Rechercher…" oninput="Admin.filterMetrologues()">
  </div>
  <select class="fi" id="metro-filter-niveau" onchange="Admin.filterMetrologues()">
    <option value="">Tous les niveaux</option>
    <option value="Junior">Junior</option>
    <option value="Intermédiaire">Intermédiaire</option>
    <option value="Senior">Senior</option>
    <option value="Expert">Expert</option>
  </select>
</div>

<!-- Grille métrologues -->
<div class="metro-grid" id="metro-grid">
  <div style="text-align:center;padding:40px;color:var(--txt3);grid-column:1/-1">Chargement…</div>
</div>

<!-- Modal Métrologue -->
<div class="overlay" id="userModal" onclick="if(event.target===this)Admin.closeUserModal()">
  <div class="modal modal-md">
    <div class="modal-hd">
      <div>
        <div class="modal-title" id="um-title">👷 Nouveau Métrologue</div>
        <div class="modal-sub">Compte + Badge RFID + Classeur</div>
      </div>
      <button class="modal-x" onclick="Admin.closeUserModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="um-id">
      <div class="fr2">
        <div class="fg"><label class="fl">Prénom *</label><input class="fi" id="um-prenom" placeholder="Prénom"></div>
        <div class="fg"><label class="fl">Nom *</label><input class="fi" id="um-nom" placeholder="Nom"></div>
      </div>
      <div class="fg"><label class="fl">Email *</label><input class="fi" type="email" id="um-email" placeholder="prenom.nom@cm2e.tn"></div>
      <div class="fr2">
        <div class="fg"><label class="fl">Téléphone</label><input class="fi" type="tel" id="um-tel" placeholder="+216 XX XXX XXX"></div>
        <div class="fg"><label class="fl">Badge UID (RFID)</label><input class="fi" id="um-badge" placeholder="Ex: A1B2C3D4" maxlength="20"></div>
      </div>
      <div class="fr3">
        <div class="fg"><label class="fl">Niveau</label>
          <select class="fi" id="um-niveau">
            <option>Junior</option><option>Intermédiaire</option><option>Senior</option><option>Expert</option>
          </select>
        </div>
        <div class="fg"><label class="fl">Poste</label><input class="fi" id="um-poste" placeholder="Ex: Métrologie industrielle"></div>
        <div class="fg"><label class="fl">N° Classeur *</label>
          <select class="fi" id="um-classeur">
            <option value="">— Sélectionner —</option>
            <?php for($i=1;$i<=10;$i++): ?><option value="<?=$i?>"><?=$i?></option><?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="fr2">
        <div class="fg"><label class="fl">Date d'embauche</label><input class="fi" type="date" id="um-hire"></div>
        <div class="fg"><label class="fl">Couleur du profil</label><input class="fi" type="color" id="um-color" value="#E31E24" style="height:40px;padding:4px"></div>
      </div>
      <div class="fg" id="um-pwd-wrap"><label class="fl">Mot de passe initial *</label><input class="fi" type="password" id="um-password" placeholder="••••••••"></div>
    </div>
    <div class="modal-foot">
      <button class="btn-danger" id="um-del-btn" style="display:none" onclick="Admin.deleteUser()">🗑 Supprimer</button>
      <button class="btn-sec" onclick="Admin.closeUserModal()">Annuler</button>
      <button class="btn-prim" onclick="Admin.saveUser()">✓ Enregistrer</button>
    </div>
  </div>
</div>
