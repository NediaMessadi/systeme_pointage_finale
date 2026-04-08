/**
 * CM2E Admin — JavaScript unifié
 */
const Admin = (() => {
  'use strict';

  const BASE = (() => {
    const s = location.pathname;
    const idx = s.indexOf('/cm2e-php');
    return idx >= 0 ? s.slice(0, idx + '/cm2e-php'.length) : '';
  })();
  const apiUrl = path => `${BASE}/api/${path}`;

  /* ── API Helpers ──────────────────────────── */
  const api = async (url, opts = {}) => {
    try {
      const r = await fetch(url, { headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, ...opts });
      return await r.json();
    } catch (e) { return { success: false, error: e.message }; }
  };
  const get   = url          => api(url);
  const post  = (url, body)  => api(url, { method: 'POST',   body: JSON.stringify(body) });
  const put   = (url, body)  => api(url, { method: 'PUT',    body: JSON.stringify(body) });
  const patch = (url, body)  => api(url, { method: 'PATCH',  body: JSON.stringify(body) });
  const del   = url          => api(url, { method: 'DELETE' });

  /* ── Toast ────────────────────────────────── */
  let toastT;
  const toast = (msg, type = 'ok') => {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.className = `toast show ${type}`;
    clearTimeout(toastT);
    toastT = setTimeout(() => el.classList.remove('show'), 3400);
  };

  /* ── Chips ────────────────────────────────── */
  const TASK_LABELS = { finaliser: 'Finaliser', verifier: 'Vérifier', envoyer: 'Envoyer', commander: 'Commander' };
  const TASK_CLASS  = { finaliser: 'F', verifier: 'V', envoyer: 'E', commander: 'Cm' };
  const TASK_LETTER = { finaliser: 'F', verifier: 'V', envoyer: 'E', commander: 'Cm' };
  const chipTask = t => {
    if (!t) return '<span class="chip chip-empty">—</span>';
    return `<span class="chip chip-${TASK_CLASS[t] || 'F'}">${TASK_CLASS[t] || '?'}</span>`;
  };
  const statusChip = s => {
    if (s === 'done')     return '<span class="chip chip-done">Terminé</span>';
    if (s === 'progress') return '<span class="chip chip-prog">En cours</span>';
    return '<span class="chip chip-wait">En attente</span>';
  };
  const presChip = s => {
    if (s === 'ok')   return '<span class="pres-status pres-ok">● Présent</span>';
    if (s === 'late') return '<span class="pres-status pres-late">⚠ Retard</span>';
    if (s === 'abs')  return '<span class="pres-status pres-abs">✕ Absent</span>';
    return '<span class="pres-status" style="color:var(--txt3)">—</span>';
  };
  const ini = (p, n) => (p[0] + n[0]).toUpperCase();
  const fmtDate = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—';

  /* ════════════════════════════════════════════
     DASHBOARD
  ════════════════════════════════════════════ */
  let timerInterval;
  const loadDashboard = async () => {
    const el = id => document.getElementById(id);
    const r = await get(apiUrl('dashboard.php'));
    if (!r.success) return;

    /* Date */
    const dd = el('dash-date');
    if (dd) dd.textContent = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    /* KPI */
    if (el('d-proj'))  el('d-proj').textContent  = r.kpi.active_proj;
    if (el('d-users')) el('d-users').textContent = r.kpi.active_users;
    if (el('d-tasks')) el('d-tasks').textContent = r.kpi.done_tasks;
    if (el('d-abs'))   el('d-abs').textContent   = r.kpi.abs_today;

    /* Quarter timer */
    const q = r.quarter;
    if (el('timer-qlbl'))  el('timer-qlbl').textContent  = q.label;
    if (el('timer-qlbl2')) el('timer-qlbl2').textContent = q.label;
    if (el('timer-qpct'))  el('timer-qpct').textContent  = q.progress + '%';
    const pbar = el('q-pbar');
    if (pbar) setTimeout(() => pbar.style.width = q.progress + '%', 200);

    if (timerInterval) clearInterval(timerInterval);
    const updateTimer = () => {
      const end = new Date(q.end + 'T23:59:59');
      const diff = Math.max(0, end - new Date());
      const fmt = v => String(v).padStart(2, '0');
      if (el('t-days'))  el('t-days').textContent  = fmt(Math.floor(diff / 86400000));
      if (el('t-hours')) el('t-hours').textContent = fmt(Math.floor((diff % 86400000) / 3600000));
      if (el('t-mins'))  el('t-mins').textContent  = fmt(Math.floor((diff % 3600000) / 60000));
    };
    updateTimer();
    timerInterval = setInterval(updateTimer, 60000);

    /* Week range */
    const wr = el('d-week-range');
    if (wr && r.week_start) {
      const ws = fmtDate(r.week_start), we = fmtDate(r.week_end);
      wr.textContent = `Semaine du ${ws} au ${we}`;
    }

    /* Leaderboard + Podium */
    renderLeaderboard('dash-leaderboard', 'dash-podium', r.leaderboard);

    /* Planning */
    renderPlanningTable('dash-plan-body', r.planning, null);
  };

  const renderLeaderboard = (lbId, podId, data) => {
    const lbEl = document.getElementById(lbId);
    const pdEl = document.getElementById(podId);

    if (lbEl) {
      lbEl.innerHTML = data.slice(3).map((u, i) => `
        <div class="lb-row">
          <div class="lb-rank">${i + 4}</div>
          <div class="lb-av" style="background:${u.color}">${ini(u.prenom, u.nom)}</div>
          <div class="lb-info"><div class="lb-name">${u.prenom} ${u.nom}</div></div>
          <div class="lb-score ${u.score > 0 ? 'score-pos' : u.score < 0 ? 'score-neg' : ''}">${u.score >= 0 ? '+' + u.score : u.score}</div>
        </div>
      `).join('') || '<div style="text-align:center;padding:20px;color:var(--txt3)">Aucune donnée</div>';
    }

    if (pdEl) {
      const top3 = data.slice(0, 3);
      const medals = ['🥇','🥈','🥉'];
      const classes = ['base-1','base-2','base-3'];
      const heights = ['1','2','3'];
      /* Reorder: 2nd, 1st, 3rd */
      const order = top3.length >= 2 ? [1, 0, 2] : [0, 1, 2];
      pdEl.innerHTML = order.filter(i => top3[i]).map(i => {
        const u = top3[i];
        if (!u) return '';
        return `
          <div class="podium-item">
            <div class="podium-medal">${medals[i]}</div>
            <div class="podium-avatar" style="background:${u.color}">${ini(u.prenom, u.nom)}</div>
            <div class="podium-name">${u.prenom}<br>${u.nom}</div>
            <div class="podium-score">${u.score >= 0 ? '+' + u.score : u.score}</div>
            <div class="podium-base ${classes[i]}">${i + 1}</div>
          </div>
        `;
      }).join('');
    }
  };

  /* ════════════════════════════════════════════
     PLANNING
  ════════════════════════════════════════════ */
  let planOffset = 0;

  const renderPlanningTable = (tbodyId, planning, headerIds) => {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.innerHTML = planning.map(row => {
      const u = row.user;

      /* Detect if user has all tasks done on current project (from any day) */
      const allDone = row.days.some(d => d.all_tasks_done);

      const cells = row.days.map(d => {
        if (d.pointage === 'abs') return `<td class="tc">${presChip('abs')}</td>`;
        if (d.all_tasks_done) {
          /* All 4 tasks complete → show "Complet" badge */
          return `<td class="tc"><span class="chip chip-done" style="font-size:11px;padding:3px 8px;font-weight:700">✓ Complet</span></td>`;
        }
        let cell = chipTask(d.task_type);
        if (d.task_done) cell = `<span class="chip chip-done">✓</span>`;
        return `<td class="tc">${cell}</td>`;
      }).join('');

      /* Score : +1 only when positive (before deadline), never show negative in planning */
      let scoreHtml;
      if (u.score > 0) {
        scoreHtml = `<span style="font-weight:800;color:var(--green)">+${u.score}</span>`;
      } else if (u.score < 0) {
        scoreHtml = `<span style="font-weight:800;color:var(--red)">${u.score}</span>`;
      } else {
        scoreHtml = `<span style="font-weight:600;color:var(--txt3)">+0</span>`;
      }

      const nameBadge = allDone
        ? `<span style="font-size:10px;background:var(--green);color:#fff;border-radius:20px;padding:2px 7px;font-weight:700;margin-left:4px">✓ Complet</span>`
        : '';

      return `<tr>
        <td>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <div class="lb-av" style="background:${u.color}">${ini(u.prenom, u.nom)}</div>
            <span style="font-weight:600;font-size:13px">${u.prenom} ${u.nom}</span>
            ${nameBadge}
          </div>
        </td>
        ${cells}
        <td>${scoreHtml}</td>
      </tr>`;
    }).join('') || `<tr><td colspan="7" class="tbl-empty">Aucun métrologue</td></tr>`;
  };

  const loadPlanning = async () => {
    const r = await get(apiUrl(`planning.php?offset=${planOffset}`));
    if (!r.success) return;

    const wlbl = document.getElementById('week-lbl');
    if (wlbl && r.week_start) {
      wlbl.textContent = `${fmtDate(r.week_start)} → ${fmtDate(r.week_end)}`;
    }

    /* Update column headers with dates */
    const days = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi'];
    for (let i = 0; i < 5; i++) {
      const th = document.getElementById(`ph-${i}`);
      if (th && r.planning[0]) {
        const d = r.planning[0].days[i];
        const dt = d ? new Date(d.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) : '';
        th.textContent = `${days[i]} ${dt}`;
      }
    }

    renderPlanningTable('planning-body', r.planning, ['ph-0','ph-1','ph-2','ph-3','ph-4']);
  };

  const weekNav = async (dir) => {
    if (dir === 0) planOffset = 0;
    else planOffset += dir;
    await loadPlanning();
  };

  /* ════════════════════════════════════════════
     MÉTROLOGUES
  ════════════════════════════════════════════ */
  let allUsers = [];

  const loadMetrologues = async () => {
    const r = await get(apiUrl('users.php?role=metrologue'));
    if (!r.success) return;
    allUsers = r.data || [];
    renderMetrologues(allUsers);
    const cnt = document.getElementById('metro-count');
    if (cnt) cnt.textContent = `${allUsers.length} métrologue(s)`;

    /* Populate dropdowns */
    populateUserDropdowns(allUsers);
  };

  const filterMetrologues = () => {
    const search = document.getElementById('metro-search')?.value.toLowerCase() || '';
    const niveau = document.getElementById('metro-filter-niveau')?.value || '';
    const filtered = allUsers.filter(u =>
      (!search || `${u.prenom} ${u.nom}`.toLowerCase().includes(search) || (u.poste || '').toLowerCase().includes(search)) &&
      (!niveau || u.niveau === niveau)
    );
    renderMetrologues(filtered);
  };

  const renderMetrologues = (users) => {
    const grid = document.getElementById('metro-grid');
    if (!grid) return;
    if (!users.length) { grid.innerHTML = '<div style="text-align:center;padding:40px;color:var(--txt3);grid-column:1/-1">Aucun métrologue</div>'; return; }
    grid.innerHTML = users.map(u => {
      const scoreColor = u.score > 0 ? 'var(--green)' : u.score < 0 ? 'var(--red)' : 'var(--txt3)';
      const scoreStr   = u.score >= 0 ? `+${u.score}` : u.score;
      return `
        <div class="metro-card" onclick="Admin.openUserModal(${u.id})">
          <div class="metro-card-top">
            <div class="metro-avatar" style="background:${u.color}">${u.photo ? `<img src="../uploads/${u.photo}">` : ini(u.prenom, u.nom)}</div>
            <div>
              <div class="metro-name">${u.prenom} ${u.nom}</div>
              <div class="metro-pos">${u.poste || u.niveau}</div>
              ${u.badge_uid ? `<div style="font-size:10px;color:var(--txt3);margin-top:2px">📡 ${u.badge_uid}</div>` : ''}
            </div>
          </div>
          <div class="metro-stats">
            <div class="metro-stat">
              <div class="metro-stat-val" style="color:${scoreColor}">${scoreStr}</div>
              <div class="metro-stat-lbl">Score</div>
            </div>
            <div class="metro-stat">
              <div class="metro-stat-val">${u.projets_termines || 0}</div>
              <div class="metro-stat-lbl">Projets</div>
            </div>
            <div class="metro-stat">
              <div class="metro-stat-val">${u.absences_count || 0}</div>
              <div class="metro-stat-lbl">Absences</div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  };

  const populateUserDropdowns = (users) => {
    ['proj-filter-user','pt-filter-user','ptm-user','pm-user'].forEach(selId => {
      const sel = document.getElementById(selId);
      if (!sel) return;
      const cur = sel.value;
      const opts = users.map(u => `<option value="${u.id}">${u.prenom} ${u.nom}</option>`).join('');
      if (selId === 'proj-filter-user' || selId === 'pt-filter-user') {
        sel.innerHTML = `<option value="">Tous les métrologues</option>` + opts;
      } else {
        sel.innerHTML = `<option value="">— Sélectionner un métrologue —</option>` + opts;
      }
      if (cur) sel.value = cur;
    });
  };

  /* Modal Métrologue */
  const openUserModal = async (id = null) => {
    const modal = document.getElementById('userModal');
    if (!modal) return;
    document.getElementById('um-id').value = '';
    document.getElementById('um-prenom').value = '';
    document.getElementById('um-nom').value = '';
    document.getElementById('um-email').value = '';
    document.getElementById('um-tel').value = '';
    document.getElementById('um-badge').value = '';
    document.getElementById('um-niveau').value = 'Senior';
    document.getElementById('um-poste').value = '';
    document.getElementById('um-classeur').value = '';
    document.getElementById('um-hire').value = '';
    document.getElementById('um-color').value = '#E31E24';
    document.getElementById('um-password').value = '';
    document.getElementById('um-pwd-wrap').style.display = 'block';
    document.getElementById('um-del-btn').style.display = 'none';
    document.getElementById('um-title').textContent = '👷 Nouveau Métrologue';

    if (id) {
      const r = await get(apiUrl(`users.php?id=${id}`));
      if (r.success && r.data) {
        const u = r.data;
        document.getElementById('um-id').value       = u.id;
        document.getElementById('um-prenom').value   = u.prenom;
        document.getElementById('um-nom').value      = u.nom;
        document.getElementById('um-email').value    = u.email;
        document.getElementById('um-tel').value      = u.telephone || '';
        document.getElementById('um-badge').value    = u.badge_uid || '';
        document.getElementById('um-niveau').value   = u.niveau;
        document.getElementById('um-poste').value    = u.poste || '';
        document.getElementById('um-classeur').value = u.classeur;
        document.getElementById('um-hire').value     = u.hire_date || '';
        document.getElementById('um-color').value    = u.color;
        document.getElementById('um-pwd-wrap').style.display = 'none';
        document.getElementById('um-del-btn').style.display  = 'inline-flex';
        document.getElementById('um-title').textContent = `✏️ Modifier — ${u.prenom} ${u.nom}`;
      }
    }

    modal.classList.add('open');
  };
  const closeUserModal = () => document.getElementById('userModal')?.classList.remove('open');

  const saveUser = async () => {
    const id = document.getElementById('um-id').value;
    const data = {
      prenom: document.getElementById('um-prenom').value,
      nom:    document.getElementById('um-nom').value,
      email:  document.getElementById('um-email').value,
      telephone: document.getElementById('um-tel').value,
      badge_uid: document.getElementById('um-badge').value,
      niveau:    document.getElementById('um-niveau').value,
      poste:     document.getElementById('um-poste').value,
      classeur:  document.getElementById('um-classeur').value,
      hire_date: document.getElementById('um-hire').value,
      color:     document.getElementById('um-color').value,
    };
    const pwd = document.getElementById('um-password').value;
    if (pwd) data.password = pwd;

    const r = id
      ? await patch(apiUrl(`users.php?id=${id}`), data)
      : await post(apiUrl('users.php'), { ...data, role: 'metrologue' });

    if (r.success) {
      toast(id ? 'Métrologue mis à jour ✓' : 'Métrologue créé ✓');
      closeUserModal();
      loadMetrologues();
    } else {
      toast(r.error || 'Erreur', 'err');
    }
  };

  const deleteUser = async () => {
    const id = document.getElementById('um-id').value;
    if (!id || !confirm('Supprimer ce métrologue ? Cette action est irréversible.')) return;
    const r = await del(apiUrl(`users.php?id=${id}`));
    if (r.success) { toast('Supprimé ✓', 'warn'); closeUserModal(); loadMetrologues(); }
    else toast(r.error || 'Erreur', 'err');
  };

  /* ════════════════════════════════════════════
     PROJETS
  ════════════════════════════════════════════ */
  let allProjects = [];

  const loadProjects = async () => {
    const status = document.getElementById('proj-filter-status')?.value || '';
    const userId = document.getElementById('proj-filter-user')?.value || '';
    let url = apiUrl('projects.php');
    const params = [];
    if (status) params.push('status=' + status);
    if (userId) params.push('user_id=' + userId);
    if (params.length) url += '?' + params.join('&');

    const r = await get(url);
    if (!r.success) return;
    allProjects = r.data || [];
    renderProjects(allProjects);
    const cnt = document.getElementById('proj-count');
    if (cnt) cnt.textContent = `${allProjects.length} projet(s)`;
  };

  const filterProjects = () => {
    const s = document.getElementById('proj-search')?.value.toLowerCase() || '';
    const filtered = allProjects.filter(p =>
      !s || p.code.toLowerCase().includes(s) || (p.description || '').toLowerCase().includes(s)
    );
    renderProjects(filtered);
  };

  const renderProjects = (projects) => {
    const tbody = document.getElementById('proj-body');
    if (!tbody) return;
    tbody.innerHTML = projects.map(p => {
      const total = p.total_tasks || 0;
      const done  = p.done_tasks  || 0;
      const pct   = total > 0 ? Math.round(done / total * 100) : 0;
      const scoreStr = p.score_awarded !== null && p.score_awarded !== undefined
        ? `<span style="font-weight:800;color:${p.score_awarded > 0 ? 'var(--green)' : p.score_awarded < 0 ? 'var(--red)' : 'var(--txt3)'}">${p.score_awarded > 0 ? '+1' : p.score_awarded < 0 ? '-1' : '0'}</span>`
        : '<span style="color:var(--txt3)">—</span>';
      const userName = p.prenom ? `${p.prenom} ${p.nom}` : '<span style="color:var(--txt3)">Non assigné</span>';
      return `<tr>
        <td><span style="font-weight:800;color:var(--red)">${p.code}</span></td>
        <td>${userName}</td>
        <td>${statusChip(p.status)}</td>
        <td><span class="chip" style="background:${p.priority==='Urgente'?'#fee2e2':p.priority==='Haute'?'#fff7ed':'#f1f5f9'};color:${p.priority==='Urgente'?'#991b1b':p.priority==='Haute'?'#9a3412':'#64748b'}">${p.priority}</span></td>
        <td><div style="display:flex;align-items:center;gap:6px">
          <div class="pbar-bg" style="width:80px"><div class="pbar-fill" style="width:${pct}%"></div></div>
          <span style="font-size:11px;color:var(--txt3)">${done}/${total}</span>
        </div></td>
        <td>${p.due_date ? fmtDate(p.due_date) : '—'}</td>
        <td>${scoreStr}</td>
        <td style="text-align:right">
          <button class="tb-btn" onclick="Admin.openProjDetail(${p.id})" style="margin-right:4px">👁</button>
          <button class="tb-btn" onclick="Admin.openProjModal(${p.id})">✏️</button>
        </td>
      </tr>`;
    }).join('') || `<tr><td colspan="8" class="tbl-empty">Aucun projet</td></tr>`;
  };

  /* Modal Projet */
  const openProjModal = async (id = null) => {
    const modal = document.getElementById('projModal');
    if (!modal) return;

    /* Load metrologues for dropdown */
    const ur = await get(apiUrl('users.php?role=metrologue'));
    if (ur.success) {
      const sel = document.getElementById('pm-user');
      if (sel) {
        sel.innerHTML = '<option value="">— Sélectionner —</option>' +
          (ur.data || []).map(u => `<option value="${u.id}">${u.prenom} ${u.nom}</option>`).join('');
      }
    }

    /* Reset */
    document.getElementById('pm-id').value = '';
    document.getElementById('pm-user').value = '';
    document.getElementById('pm-priority').value = 'Normale';
    document.getElementById('pm-start').value = new Date().toISOString().slice(0,10);
    document.getElementById('pm-due').value = '';
    document.getElementById('pm-desc').value = '';
    document.getElementById('pm-del-btn').style.display = 'none';
    document.getElementById('pm-code-display').textContent = 'Code généré automatiquement';
    document.getElementById('pm-name').value = '';
    document.getElementById('pm-title').textContent = '📁 Nouveau Projet';

    if (id) {
      const r = await get(apiUrl(`projects.php?id=${id}`));
      if (r.success && r.data) {
        const p = r.data;
        document.getElementById('pm-id').value        = p.id;
        document.getElementById('pm-user').value      = p.user_id || '';
        document.getElementById('pm-priority').value  = p.priority;
        document.getElementById('pm-start').value     = p.start_date || '';
        document.getElementById('pm-due').value       = p.due_date  || '';
        document.getElementById('pm-desc').value      = p.description || '';
        document.getElementById('pm-del-btn').style.display = 'inline-flex';
        document.getElementById('pm-code-display').textContent = p.code;
        document.getElementById('pm-title').textContent = `✏️ Modifier — ${p.code}`;
      }
    }
    modal.classList.add('open');
  };
  const closeProjModal = () => document.getElementById('projModal')?.classList.remove('open');

  const saveProject = async () => {
    const id = document.getElementById('pm-id').value;
    const name = document.getElementById('pm-name').value.trim();
if (!name) { toast('Nom du projet requis', 'err'); return; }
const data = {
  name:        name,
  user_id:     document.getElementById('pm-user').value,
      priority:    document.getElementById('pm-priority').value,
      start_date:  document.getElementById('pm-start').value,
      due_date:    document.getElementById('pm-due').value,
      description: document.getElementById('pm-desc').value,
    };
    if (!data.user_id || !data.due_date) { toast('Métrologue et échéance requis', 'err'); return; }

    const r = id
      ? await patch(apiUrl(`projects.php?id=${id}`), data)
      : await post(apiUrl('projects.php'), data);

    if (r.success) {
      toast(id ? 'Projet mis à jour ✓' : `Projet créé : ${r.code} ✓`);
      closeProjModal();
      loadProjects();
    } else toast(r.error || 'Erreur', 'err');
  };

  const deleteProject = async () => {
    const id = document.getElementById('pm-id').value;
    if (!id || !confirm('Supprimer ce projet ?')) return;
    const r = await del(apiUrl(`projects.php?id=${id}`));
    if (r.success) { toast('Supprimé ✓', 'warn'); closeProjModal(); loadProjects(); }
    else toast(r.error || 'Erreur', 'err');
  };

  /* Modal Détail Projet */
  const openProjDetail = async (id) => {
    const modal = document.getElementById('projDetailModal');
    if (!modal) return;
    const r = await get(apiUrl(`projects.php?id=${id}`));
    if (!r.success || !r.data) return;
    const p = r.data;
    document.getElementById('pd-title').textContent = p.code;
    document.getElementById('pd-sub').textContent   = p.description || '';
    const body = document.getElementById('pd-body');
    const tasks = p.tasks || [];
    body.innerHTML = `
      <div style="margin-bottom:14px">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px">
          ${statusChip(p.status)}
          <span style="font-size:12px;color:var(--txt2)">Assigné à : <strong>${p.prenom ? p.prenom+' '+p.nom : '—'}</strong></span>
          <span style="font-size:12px;color:var(--txt2)">Échéance : <strong>${fmtDate(p.due_date)}</strong></span>
        </div>
        ${p.score_awarded !== null && p.score_awarded !== undefined ? `
        <div style="margin-bottom:12px;font-size:13px">
          Score: <span style="font-weight:800;font-size:16px;color:${p.score_awarded>0?'var(--green)':p.score_awarded<0?'var(--red)':'var(--txt3)'}">${p.score_awarded>0?'+1':p.score_awarded<0?'-1':'0'}</span>
          <span style="color:var(--txt3);font-size:11px;margin-left:6px">(terminé le ${fmtDate(p.completed_at)})</span>
        </div>` : ''}
      </div>
      <div style="font-size:13px;font-weight:700;margin-bottom:8px">Tâches (${tasks.filter(t=>t.completed).length}/${tasks.length})</div>
      <div style="display:flex;flex-direction:column;gap:6px">
        ${tasks.map(t => `
          <div style="display:flex;align-items:center;gap:8px;padding:8px;border:1.5px solid var(--border);border-radius:8px;${t.completed?'background:#f0fdf4':''}">
            <div style="width:22px;height:22px;border-radius:50%;background:${t.completed?'var(--green)':'var(--bg)'};border:2px solid ${t.completed?'var(--green)':'var(--border)'};display:flex;align-items:center;justify-content:center;font-size:11px;color:#fff">${t.completed?'✓':''}</div>
            <span class="chip chip-${TASK_CLASS[t.type]||'F'}">${TASK_CLASS[t.type]||'?'}</span>
            <span style="flex:1;font-size:13px;font-weight:${t.completed?700:400}">${t.label}</span>
            ${t.completed_at ? `<span style="font-size:11px;color:var(--txt3)">${fmtDate(t.completed_at)}</span>` : ''}
          </div>
        `).join('')}
      </div>
    `;
    modal.classList.add('open');
  };
  const closeProjDetail = () => document.getElementById('projDetailModal')?.classList.remove('open');

  /* ════════════════════════════════════════════
     POINTAGES
  ════════════════════════════════════════════ */
  const loadPointages = async () => {
    const date   = document.getElementById('pt-date')?.value || new Date().toISOString().slice(0,10);
    const userId = document.getElementById('pt-filter-user')?.value || '';
    const status = document.getElementById('pt-filter-status')?.value || '';

    let url = apiUrl(`pointages.php?date=${date}`);
    if (userId) url += `&user_id=${userId}`;
    if (status) url += `&status=${status}`;

    const r = await get(url);
    if (!r.success) return;

    /* KPI */
    if (r.kpi) {
      const si = id => document.getElementById(id);
      if (si('pt-ok'))   si('pt-ok').textContent   = r.kpi.ok_count   || 0;
      if (si('pt-late')) si('pt-late').textContent = r.kpi.late_count || 0;
      if (si('pt-abs'))  si('pt-abs').textContent  = r.kpi.abs_count  || 0;
      if (si('pt-rfid')) si('pt-rfid').textContent = r.kpi.rfid_count || 0;
    }

    const tbody = document.getElementById('pt-body');
    if (!tbody) return;
    tbody.innerHTML = (r.data || []).map(p => `<tr>
      <td><div style="display:flex;align-items:center;gap:8px">
        <div class="lb-av" style="background:${p.color||'#E31E24'}">${ini(p.prenom, p.nom)}</div>
        <span style="font-weight:600">${p.prenom} ${p.nom}</span>
      </div></td>
      <td>${p.date}</td>
      <td>${p.arrivee || '—'}</td>
      <td>${p.depart || '—'}</td>
      <td>${p.duree || '—'}</td>
      <td>${presChip(p.status)}</td>
      <td>${p.rfid_scan ? '<span style="color:var(--green);font-size:11px;font-weight:700">📡 RFID</span>' : '<span style="color:var(--txt3);font-size:11px">Manuel</span>'}</td>
      <td style="color:var(--txt3);font-size:12px">${p.note || '—'}</td>
      <td><button class="tb-btn" onclick="Admin.openPtEdit(${p.id})">✏️</button></td>
    </tr>`).join('') || `<tr><td colspan="9" class="tbl-empty">Aucun pointage pour cette date</td></tr>`;
  };

  const openPtModal = (id = null) => {
    const modal = document.getElementById('ptModal');
    if (!modal) return;
    document.getElementById('ptm-id').value = id || '';
    if (!id) {
      document.getElementById('ptm-user').value    = '';
      document.getElementById('ptm-date').value    = new Date().toISOString().slice(0,10);
      document.getElementById('ptm-status').value  = 'ok';
      document.getElementById('ptm-arrivee').value = '';
      document.getElementById('ptm-depart').value  = '';
      document.getElementById('ptm-note').value    = '';
    }
    document.getElementById('ptm-title').textContent = id ? '✏️ Modifier pointage' : '📋 Nouveau pointage';
    modal.classList.add('open');
  };

  const openPtEdit = async (id) => {
    const r = await get(apiUrl(`pointages.php?id=${id}`));
    if (!r.success || !r.data) return;
    const p = r.data;
    openPtModal(id);
    document.getElementById('ptm-user').value    = p.user_id;
    document.getElementById('ptm-date').value    = p.date;
    document.getElementById('ptm-status').value  = p.status;
    document.getElementById('ptm-arrivee').value = p.arrivee || '';
    document.getElementById('ptm-depart').value  = p.depart  || '';
    document.getElementById('ptm-note').value    = p.note    || '';
  };

  const closePtModal = () => document.getElementById('ptModal')?.classList.remove('open');

  const savePointage = async () => {
    const id = document.getElementById('ptm-id').value;
    const data = {
      user_id: document.getElementById('ptm-user').value,
      date:    document.getElementById('ptm-date').value,
      status:  document.getElementById('ptm-status').value,
      arrivee: document.getElementById('ptm-arrivee').value || null,
      depart:  document.getElementById('ptm-depart').value  || null,
      note:    document.getElementById('ptm-note').value,
    };
    const r = id
      ? await patch(apiUrl(`pointages.php?id=${id}`), data)
      : await post(apiUrl('pointages.php'), data);

    if (r.success) { toast('Pointage enregistré ✓'); closePtModal(); loadPointages(); }
    else toast(r.error || 'Erreur', 'err');
  };

  /* ════════════════════════════════════════════
     SCORES
  ════════════════════════════════════════════ */
  const loadScores = async () => {
    const r = await get(apiUrl('scores.php'));
    if (!r.success) return;

    /* Label trimestre */
    const lbl = document.getElementById('scores-quarter-lbl');
    if (lbl) lbl.textContent = r.quarter.label;

    /* Podium */
    renderLeaderboard('scores-leaderboard-dummy', 'scores-podium', r.leaderboard);

    /* Tableau */
    const tbody = document.getElementById('scores-body');
    if (tbody) {
      tbody.innerHTML = r.leaderboard.map((u, i) => {
        const scoreStr = u.score >= 0 ? `+${u.score}` : u.score;
        const scoreColor = u.score > 0 ? 'var(--green)' : u.score < 0 ? 'var(--red)' : 'var(--txt3)';
        return `<tr>
          <td style="font-weight:700;color:var(--txt3)">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : i + 1}</td>
          <td><div style="display:flex;align-items:center;gap:8px">
            <div class="lb-av" style="background:${u.color}">${ini(u.prenom, u.nom)}</div>
            <div><div style="font-weight:600">${u.prenom} ${u.nom}</div><div style="font-size:11px;color:var(--txt3)">${u.niveau}</div></div>
          </div></td>
          <td>${u.niveau}</td>
          <td class="tc" style="font-weight:600">${u.projets_termines || 0}</td>
          <td class="tc" style="font-weight:600;color:${u.absences>0?'var(--orange)':'var(--txt3)'}">${u.absences || 0}</td>
          <td class="tc" style="font-size:18px;font-weight:800;color:${scoreColor}">${scoreStr}</td>
          <td class="tc">${u.score > 0 ? '📈' : u.score < 0 ? '📉' : '➡️'}</td>
        </tr>`;
      }).join('') || `<tr><td colspan="7" class="tbl-empty">Aucune donnée</td></tr>`;
    }

    /* Historique */
    const hist = document.getElementById('scores-history');
    if (hist) {
      hist.innerHTML = r.history.map(h => `<tr>
        <td><span style="font-weight:700">${h.quarter} ${h.year}</span></td>
        <td>${h.champion_nom || '—'}</td>
        <td style="font-weight:700;color:var(--green)">${h.champion_score >= 0 ? '+' + h.champion_score : h.champion_score}</td>
        <td style="font-size:11px;color:var(--txt3)">${h.created_at ? fmtDate(h.created_at) : '—'}</td>
      </tr>`).join('') || `<tr><td colspan="4" class="tbl-empty">Aucun résultat publié</td></tr>`;
    }
  };

  const openChampModal = async () => {
    const modal = document.getElementById('champModal');
    if (!modal) return;
    const r = await get(apiUrl('scores.php'));
    const top = r.success && r.leaderboard[0] ? r.leaderboard[0] : null;
    const content = document.getElementById('champ-content');
    if (content) {
      content.innerHTML = top ? `
        <div style="font-size:18px;font-weight:800;margin-bottom:4px">${top.prenom} ${top.nom}</div>
        <div style="font-size:14px;color:var(--txt2);margin-bottom:12px">${r.quarter?.label || ''}</div>
        <div style="font-size:42px;font-weight:800;color:var(--green)">${top.score >= 0 ? '+' + top.score : top.score}</div>
        <div style="font-size:12px;color:var(--txt3);margin-top:4px">points</div>
      ` : '<p style="color:var(--txt3)">Aucune donnée disponible.</p>';
    }
    modal.classList.add('open');
  };
  const closeChampModal = () => document.getElementById('champModal')?.classList.remove('open');

  const saveChampion = async () => {
    const r = await post(apiUrl('scores.php'), { action: 'publish_champion' });
    if (r.success) { toast(`🏆 Champion publié : ${r.champion?.prenom} ${r.champion?.nom}`); closeChampModal(); loadScores(); }
    else toast(r.error || 'Erreur', 'err');
  };

  const confirmResetScores = async () => {
    if (!confirm('Réinitialiser tous les scores ? Cette action est irréversible.')) return;
    const r = await post(apiUrl('scores.php'), { action: 'reset' });
    if (r.success) { toast('Scores réinitialisés ✓', 'warn'); loadScores(); }
    else toast(r.error || 'Erreur', 'err');
  };

  /* ════════════════════════════════════════════
     SÉCURITÉ / LOGS
  ════════════════════════════════════════════ */
  const loadLogs = async () => {
    const type = document.getElementById('log-filter-type')?.value || '';
    const r = await get(apiUrl(`logs.php${type ? '?type=' + type : ''}`));
    const tbody = document.getElementById('logs-body');
    if (!tbody || !r.success) return;
    tbody.innerHTML = (r.data || []).map(l => {
      const typeClass = l.log_type === 'ok' ? 'chip-done' : l.log_type === 'warn' ? 'chip-C' : 'chip-F';
      return `<tr>
        <td style="font-size:16px">${l.icon}</td>
        <td>${l.message}${l.prenom ? ` <span style="color:var(--txt3);font-size:11px">— ${l.prenom} ${l.nom}</span>` : ''}</td>
        <td><span class="chip ${typeClass}">${l.log_type}</span></td>
        <td style="font-size:11px;color:var(--txt3)">${l.created_at || ''}</td>
      </tr>`;
    }).join('') || `<tr><td colspan="4" class="tbl-empty">Aucun journal</td></tr>`;
  };

  /* ════════════════════════════════════════════
     SYSTÈME
  ════════════════════════════════════════════ */
  const loadSysInfo = async () => {
    const r = await get(apiUrl('dashboard.php'));
    if (!r.success) return;
    const ver = document.getElementById('sys-db-version');
    if (ver) ver.textContent = r.db_version || '—';
    const sysDb = document.getElementById('sys-db');
    if (sysDb) {
      sysDb.innerHTML = `
        <div class="info-item"><span class="info-label">Connexion</span><span class="info-val" style="color:var(--green)">✅ Connecté</span></div>
        <div class="info-item"><span class="info-label">Projets actifs</span><span class="info-val">${r.kpi.active_proj}</span></div>
        <div class="info-item"><span class="info-label">Métrologues actifs</span><span class="info-val">${r.kpi.active_users}</span></div>
      `;
    }
  };

  const saveSysSettings = async () => {
    toast('Paramètres enregistrés ✓');
  };

  /* ════════════════════════════════════════════
     INIT — Router de page
  ════════════════════════════════════════════ */
  const init = () => {
    const page = new URLSearchParams(location.search).get('page') || 'dashboard';

    /* Sidebar connection status */
    const sb = document.getElementById('sb-conn');
    get(apiUrl('dashboard.php')).then(r => {
      if (sb) {
        sb.textContent = r.success ? '● Connecté — MySQL OK' : '✕ Erreur de connexion';
        sb.className = r.success ? 'sb-conn' : 'sb-conn err';
      }
    });

    switch (page) {
      case 'dashboard':   loadDashboard(); break;
      case 'metrologues': loadMetrologues(); break;
      case 'projets':     loadProjects(); loadMetrologues(); break;
      case 'pointages':   loadMetrologues(); loadPointages(); break;
      case 'planning':    loadPlanning(); break;
      case 'scores':      loadScores(); break;
      case 'securite':    loadLogs(); break;
      case 'systeme':     loadSysInfo(); break;
    }
  };

  document.addEventListener('DOMContentLoaded', init);

  return {
    loadDashboard, loadMetrologues, filterMetrologues,
    openUserModal, closeUserModal, saveUser, deleteUser,
    loadProjects, filterProjects,
    openProjModal, closeProjModal, saveProject, deleteProject,
    openProjDetail, closeProjDetail,
    loadPointages, openPtModal, openPtEdit, closePtModal, savePointage,
    loadPlanning, weekNav,
    loadScores, openChampModal, closeChampModal, saveChampion, confirmResetScores,
    loadLogs, loadSysInfo, saveSysSettings,
  };
})();
