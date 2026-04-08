/**
 * CM2E Métrologue — JavaScript unifié
 */
const Metro = (() => {
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
  const patch = (url, body)  => api(url, { method: 'PATCH',  body: JSON.stringify(body) });

  /* ── Toast ────────────────────────────────── */
  let toastT;
  const toast = (msg, type = 'ok') => {
    let el = document.getElementById('me-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'me-toast';
      el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none;max-width:300px;';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.style.background = type === 'ok' ? '#065f46' : type === 'warn' ? '#92400e' : '#991b1b';
    el.style.color = '#fff';
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
    clearTimeout(toastT);
    toastT = setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(10px)'; }, 3200);
  };

  const ini    = (p, n) => (p[0] + n[0]).toUpperCase();
  const fmtDate = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—';
  const fmtDateShort = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) : '—';

  /* ── Chips ────────────────────────────────── */
  const TASK_CLASS = { finaliser: 'F', verifier: 'V', commande: 'C', reception: 'R' };
  const statusChip = s => {
    if (s === 'done')     return '<span class="chip chip-done">Terminé</span>';
    if (s === 'progress') return '<span class="chip chip-prog">En cours</span>';
    return '<span class="chip chip-wait">En attente</span>';
  };

  /* ════════════════════════════════════════════
     TABLEAU DE BORD
  ════════════════════════════════════════════ */
  const loadDashboard = async () => {
    const elId = id => document.getElementById(id);

    /* Date */
    const dd = elId('me-date');
    if (dd) dd.textContent = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    /* Données */
    const [dashR, projR, ptR] = await Promise.all([
      get(apiUrl('dashboard.php')),
      get(apiUrl('projects.php')),
      get(apiUrl('pointages.php?date=' + new Date().toISOString().slice(0,10))),
    ]);

    /* Score */
    if (dashR.success && dashR.user) {
      const s = dashR.user.score;
      const sc = elId('me-score'); if (sc) sc.textContent = s >= 0 ? `+${s}` : s;
    }

    /* Projets KPI */
    if (projR.success) {
      const projs = projR.data || [];
      const active = projs.filter(p => p.status !== 'done').length;
      const done   = projs.filter(p => p.status === 'done').length;
      if (elId('me-kpi-proj'))   elId('me-kpi-proj').textContent   = projs.length;
      if (elId('me-kpi-done'))   elId('me-kpi-done').textContent   = done;
      if (elId('me-kpi-active')) elId('me-kpi-active').textContent = active;

      /* Projet en cours */
      const current = projs.find(p => p.status === 'progress') || projs.find(p => p.status === 'pending');
      if (current) {
        if (elId('me-cp-code')) elId('me-cp-code').textContent = current.code;
        if (elId('me-cp-desc')) elId('me-cp-desc').textContent = current.description || '';
        const total = current.total_tasks || 0;
        const dnT   = current.done_tasks  || 0;
        const pct   = total > 0 ? Math.round(dnT / total * 100) : 0;
        const pbar  = elId('me-cp-pbar');
        const plbl  = elId('me-cp-plbl');
        if (pbar) setTimeout(() => pbar.style.width = pct + '%', 300);
        if (plbl) plbl.textContent = `${dnT}/${total} tâches • ${pct}%`;
        if (elId('me-cp-due')) elId('me-cp-due').textContent = `Échéance : ${fmtDate(current.due_date)}`;
        if (elId('me-cp-status')) elId('me-cp-status').innerHTML = statusChip(current.status);
      } else {
        const cpCard = elId('me-cp-card');
        if (cpCard) cpCard.innerHTML = `<div class="empty-state"><div class="empty-ico">🎯</div><h3>Aucun projet actif</h3><p>Vous n'avez pas de projet en cours.</p></div>`;
      }
    }

    /* Pointage du jour */
    if (ptR.success && ptR.data && ptR.data.length > 0) {
      const pt = ptR.data[0];
      if (elId('me-kpi-pt')) {
        const lbl = pt.status === 'ok' ? '✅ Présent' : pt.status === 'late' ? '⚠ Retard' : '✕ Absent';
        elId('me-kpi-pt').textContent = lbl;
      }
    }

    /* Classement trimestre */
    if (dashR.success && dashR.leaderboard) {
      renderLeaderboard('me-leaderboard', dashR.leaderboard);
    }
  };

  const renderLeaderboard = (id, data) => {
    const el = document.getElementById(id);
    if (!el) return;
    const userId = parseInt(document.getElementById('me-user-id')?.value || 0);
    el.innerHTML = data.slice(0, 8).map((u, i) => {
      const scoreStr = u.score >= 0 ? `+${u.score}` : u.score;
      const isMe = u.id == userId;
      return `
        <div class="lb-row${isMe ? ' lb-me' : ''}">
          <div class="lb-rank">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : i + 1}</div>
          <div class="lb-avatar" style="background:${u.color}">${ini(u.prenom, u.nom)}</div>
          <div class="lb-info"><div class="lb-name">${u.prenom} ${u.nom}${isMe ? ' <strong>(Moi)</strong>' : ''}</div></div>
          <div class="lb-score ${u.score > 0 ? 'score-pos' : u.score < 0 ? 'score-neg' : 'score-zero'}">${scoreStr}</div>
        </div>
      `;
    }).join('') || '<div style="text-align:center;padding:20px;color:var(--txt3)">Aucune donnée</div>';
  };

  /* ════════════════════════════════════════════
     PROJET COURANT
  ════════════════════════════════════════════ */
  let currentProjId = null;

  const loadCurrentProject = async () => {
    const r = await get(apiUrl('projects.php'));
    if (!r.success) return;
    const projs = r.data || [];
    const current = projs.find(p => p.status === 'progress') || projs.find(p => p.status === 'pending');

    const container = document.getElementById('pc-container');
    if (!container) return;

    if (!current) {
      container.innerHTML = `
        <div class="empty-state" style="padding:60px 20px">
          <div class="empty-ico">🎯</div>
          <h3>Aucun projet actif</h3>
          <p>Vous n'avez pas de projet assigné. L'administrateur vous en attribuera un prochainement.</p>
        </div>`;
      return;
    }

    currentProjId = current.id;
    const tasks   = current.tasks || [];
    const total   = tasks.length;
    const done    = tasks.filter(t => t.completed).length;
    const pct     = total > 0 ? Math.round(done / total * 100) : 0;

    const dueDate = current.due_date ? new Date(current.due_date + 'T23:59:59') : null;
    const now     = new Date();
    const isLate  = dueDate && dueDate < now && current.status !== 'done';
    const daysLeft = dueDate ? Math.ceil((dueDate - now) / 86400000) : null;

    container.innerHTML = `
      <div class="card col-full">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:10px">
          <div>
            <div class="cp-code">${current.code}</div>
            <div class="cp-desc">${current.description || 'Aucune description'}</div>
            <div class="cp-meta">
              ${statusChip(current.status)}
              <span style="font-size:12px;color:var(--txt3)">Priorité : <strong>${current.priority}</strong></span>
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-size:12px;color:${isLate ? 'var(--red)' : 'var(--txt3)'}">
              📅 Échéance : <strong>${fmtDate(current.due_date)}</strong>
              ${daysLeft !== null ? `<br><span style="font-size:11px">${isLate ? `⚠ Dépassée de ${Math.abs(daysLeft)} j.` : daysLeft === 0 ? '⚠ Aujourd\'hui !' : `${daysLeft} jours restants`}</span>` : ''}
            </div>
            ${current.status !== 'done' ? `
            <button onclick="Metro.completeProject(${current.id})"
              style="margin-top:8px;padding:8px 14px;background:var(--green);color:#fff;border-radius:8px;font-size:12px;font-weight:700">
              ✅ Marquer comme terminé
            </button>` : ''}
          </div>
        </div>

        <!-- Barre de progression -->
        <div class="cp-progress" style="margin-bottom:18px">
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--txt2);margin-bottom:5px">
            <span>Progression globale</span>
            <strong>${done}/${total} tâches — ${pct}%</strong>
          </div>
          <div class="pbar-bg"><div class="pbar-fill" id="pc-pbar" style="width:0%"></div></div>
        </div>

        <!-- Tâches -->
        <div class="card-title">Tâches à réaliser</div>
        <div class="tasks-list" id="pc-tasks">
          ${tasks.map(t => `
            <div class="task-item${t.completed ? ' task-done' : ''}" id="task-${t.id}" onclick="Metro.toggleTask(${current.id},${t.id},${t.completed ? 0 : 1})">
              <button class="task-toggle${t.completed ? ' task-toggle-done' : ''}">${t.completed ? '✓' : ''}</button>
              <div class="task-body">
                <div class="task-chip chip-${TASK_CLASS[t.type] || 'F'}" style="background:${getTaskColor(t.type)}">
                  ${TASK_CLASS[t.type] || '?'}
                </div>
                <span class="task-label${t.completed ? '" style="text-decoration:line-through' : ''}">${t.label}</span>
              </div>
              ${t.completed_at ? `<span class="task-date">${fmtDate(t.completed_at)}</span>` : ''}
            </div>
          `).join('')}
        </div>
      </div>
    `;

    setTimeout(() => {
      const pb = document.getElementById('pc-pbar');
      if (pb) pb.style.width = pct + '%';
    }, 200);
  };

  const getTaskColor = type => {
    const colors = { finaliser: '#fee2e2', verifier: '#dbeafe', commande: '#fef9c3', reception: '#d1fae5' };
    return colors[type] || '#f1f5f9';
  };

  const toggleTask = async (projId, taskId, done) => {
    const el = document.getElementById(`task-${taskId}`);
    if (el) { el.style.opacity = '.5'; el.style.pointerEvents = 'none'; }
    const r = await patch(apiUrl(`projects.php?id=${projId}`), { task_id: taskId, done: done === 1 });
    if (r.success) {
      toast(done === 1 ? '✓ Tâche accomplie !' : 'Tâche réouverte');
      loadCurrentProject();
    } else {
      toast(r.error || 'Erreur', 'err');
      if (el) { el.style.opacity = '1'; el.style.pointerEvents = ''; }
    }
  };

  const completeProject = async (id) => {
    if (!confirm('Marquer ce projet comme terminé ? Le score sera calculé automatiquement.')) return;
    const r = await patch(apiUrl(`projects.php?id=${id}`), { complete: true });
    if (r.success) {
      const s = r.score;
      toast(`Projet terminé ! Score : ${s > 0 ? '+' + s : s}`);
      loadCurrentProject();
    } else toast(r.error || 'Erreur', 'err');
  };

  /* ════════════════════════════════════════════
     MES PROJETS
  ════════════════════════════════════════════ */
  let allMyProjects = [];

  const loadMyProjects = async () => {
    const r = await get(apiUrl('projects.php'));
    if (!r.success) return;
    allMyProjects = r.data || [];
    renderMyProjects(allMyProjects);
    const cnt = document.getElementById('mep-count');
    if (cnt) cnt.textContent = `${allMyProjects.length} projet(s)`;
  };

  const filterMyProjects = () => {
    const s      = document.getElementById('mep-search')?.value.toLowerCase() || '';
    const status = document.getElementById('mep-filter-status')?.value || '';
    const filtered = allMyProjects.filter(p =>
      (!s      || p.code.toLowerCase().includes(s) || (p.description || '').toLowerCase().includes(s)) &&
      (!status || p.status === status)
    );
    renderMyProjects(filtered);
  };

  const renderMyProjects = (projects) => {
    const grid = document.getElementById('mep-grid');
    if (!grid) return;
    if (!projects.length) {
      grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="empty-ico">📂</div><h3>Aucun projet</h3><p>Aucun projet ne correspond à vos critères.</p></div>`;
      return;
    }
    grid.innerHTML = projects.map(p => {
      const total  = p.total_tasks || 0;
      const done   = p.done_tasks  || 0;
      const pct    = total > 0 ? Math.round(done / total * 100) : 0;
      const isLate = p.due_date && new Date(p.due_date) < new Date() && p.status !== 'done';
      const scoreStr = p.score_awarded !== null && p.score_awarded !== undefined
        ? `<span class="score-chip ${p.score_awarded > 0 ? 'score-pos' : p.score_awarded < 0 ? 'score-neg' : 'score-zero'}">${p.score_awarded > 0 ? '+1' : p.score_awarded < 0 ? '-1' : '0'}</span>`
        : '';
      return `
        <div class="proj-card">
          <div class="proj-card-top">
            <span class="proj-code">${p.code}</span>
            ${statusChip(p.status)}
          </div>
          <div class="proj-card-desc">${p.description || 'Aucune description'}</div>
          <div class="proj-card-meta">
            <span class="chip" style="background:${p.priority==='Urgente'?'#fee2e2':p.priority==='Haute'?'#fff7ed':'#f1f5f9'};color:${p.priority==='Urgente'?'#991b1b':p.priority==='Haute'?'#9a3412':'#64748b'}">${p.priority}</span>
            <span class="${isLate ? 'proj-due-late' : 'proj-due'}">📅 ${fmtDate(p.due_date)}</span>
            ${scoreStr}
          </div>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--txt3);margin-bottom:4px">
              <span>Progression</span><span>${done}/${total} — ${pct}%</span>
            </div>
            <div class="pbar-bg"><div class="pbar-fill" style="width:${pct}%"></div></div>
          </div>
          ${p.status !== 'done' ? `<a href="?page=projet_courant" class="proj-card-btn">Ouvrir le projet →</a>` : ''}
        </div>
      `;
    }).join('');
  };

  /* ════════════════════════════════════════════
     CALENDRIER PERFORMANCE
  ════════════════════════════════════════════ */
  let calYear = new Date().getFullYear();

  const loadCalendrier = async () => {
    const r = await get(apiUrl('pointages.php'));
    if (!r.success) return;
    const pointages = r.data || [];

    /* Regrouper par date */
    const ptMap = {};
    pointages.forEach(p => { ptMap[p.date] = p; });

    renderCalendar(ptMap);
  };

  const renderCalendar = (ptMap) => {
    const grid = document.getElementById('cal-grid');
    if (!grid) return;

    /* Mise à jour label année */
    const lbl = document.getElementById('cal-year-lbl');
    if (lbl) lbl.textContent = calYear;

    const today = new Date().toISOString().slice(0,10);
    const months = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

    grid.innerHTML = months.map((m, mi) => {
      const firstDay = new Date(calYear, mi, 1);
      const daysInMonth = new Date(calYear, mi + 1, 0).getDate();
      let startDow = firstDay.getDay() - 1; // Lundi = 0
      if (startDow < 0) startDow = 6;

      let cells = '';
      for (let b = 0; b < startDow; b++) cells += `<div class="cal-day cal-day-blank"></div>`;

      for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${calYear}-${String(mi+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dow = new Date(calYear, mi, d).getDay();
        const isWe   = dow === 0 || dow === 6;
        const isToday= dateStr === today;
        const pt     = ptMap[dateStr];

        let cls = 'cal-day';
        let scoreHtml = '';
        if (isWe) cls += ' cal-we';
        else if (pt) {
          if      (pt.status === 'abs')  { cls += ' cal-abs'; }
          else if (pt.status === 'late') { cls += ' cal-late'; scoreHtml = '<span class="cal-score">⚠</span>'; }
          else { cls += ' cal-pos'; scoreHtml = '<span class="cal-score">+</span>'; }
        }
        if (isToday) cls += ' cal-today';

        cells += `<div class="${cls}" title="${dateStr}"><span class="cal-day-num">${d}</span>${scoreHtml}</div>`;
      }

      return `
        <div class="cal-month-card">
          <div class="cal-month-title">${m}</div>
          <div class="cal-day-headers">
            ${['L','M','M','J','V','S','D'].map(d=>`<div class="cal-dh">${d}</div>`).join('')}
          </div>
          <div class="cal-days">${cells}</div>
        </div>
      `;
    }).join('');
  };

  const calPrev = () => { calYear--; loadCalendrier(); };
  const calNext = () => { calYear++; loadCalendrier(); };

  /* ════════════════════════════════════════════
     PARAMÈTRES / PROFIL
  ════════════════════════════════════════════ */
  const loadProfil = async () => {
    const r = await get(apiUrl('users.php?me=1'));
    if (!r.success || !r.data) return;
    const u = r.data;
    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };
    setText('prof-prenom', u.prenom);
    setText('prof-nom',    u.nom);
    setText('prof-email',  u.email);
    setText('prof-tel',    u.telephone);
    setText('prof-niveau', u.niveau);
    setText('prof-poste',  u.poste);
    setText('prof-badge',  u.badge_uid || 'Non assigné');
    setText('prof-hire',   u.hire_date ? fmtDate(u.hire_date) : '—');
    setText('prof-score',  u.score >= 0 ? `+${u.score}` : u.score);

    const av = document.getElementById('prof-avatar');
    if (av) {
      av.style.background = u.color;
      av.textContent = ini(u.prenom, u.nom);
    }
  };

  const saveTheme = (theme) => {
    document.body.classList.toggle('dark-mode', theme === 'dark');
    localStorage.setItem('cm2e-theme', theme);
    toast('Thème appliqué ✓');
  };

  const saveFont = (size) => {
    document.body.classList.toggle('text-large', size === 'large');
    localStorage.setItem('cm2e-font', size);
    toast('Taille de police mise à jour ✓');
  };

  /* Appliquer thème au chargement */
  const applyStoredPrefs = () => {
    const t = localStorage.getItem('cm2e-theme');
    const f = localStorage.getItem('cm2e-font');
    if (t === 'dark') document.body.classList.add('dark-mode');
    if (f === 'large') document.body.classList.add('text-large');
  };

  /* ── Changement de mot de passe ─────────────────── */
  const changePassword = async () => {
    const old = document.getElementById('cp-old')?.value;
    const nw  = document.getElementById('cp-new')?.value;
    const cfm = document.getElementById('cp-confirm')?.value;

    if (!old || !nw || !cfm) { toast('Tous les champs sont requis', 'err'); return; }
    if (nw !== cfm) { toast('Les mots de passe ne correspondent pas', 'err'); return; }
    if (nw.length < 8) { toast('Mot de passe trop court (8 car. min.)', 'err'); return; }

    const r = await patch(apiUrl('users.php?me=1'), { change_password: true, old_password: old, new_password: nw });
    if (r.success) { toast('Mot de passe mis à jour ✓'); document.getElementById('cp-old').value = document.getElementById('cp-new').value = document.getElementById('cp-confirm').value = ''; }
    else toast(r.error || 'Erreur', 'err');
  };

  /* ════════════════════════════════════════════
     INIT
  ════════════════════════════════════════════ */
  const init = () => {
    applyStoredPrefs();
    const page = new URLSearchParams(location.search).get('page') || 'tableau_bord';
    switch (page) {
      case 'tableau_bord':         loadDashboard();        break;
      case 'projet_courant':       loadCurrentProject();   break;
      case 'projets':              loadMyProjects();        break;
      case 'calendrier_performance': loadCalendrier();     break;
      case 'profil_utilisateur':   loadProfil();           break;
    }
  };

  document.addEventListener('DOMContentLoaded', init);

  return {
    loadDashboard, loadCurrentProject, toggleTask, completeProject,
    loadMyProjects, filterMyProjects,
    loadCalendrier, calPrev, calNext,
    loadProfil, saveTheme, saveFont, changePassword,
  };
})();
