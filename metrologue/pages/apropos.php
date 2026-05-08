<div class="page-header animate-in">
  <div><h1><?= $lang['about'] ?></h1><div class="page-subtitle"><?= $lang['about_subtitle'] ?></div></div>
</div>

<div class="settings-card animate-in">
  <div class="settings-section" style="text-align:center;padding:24px">
    <div style="margin-bottom:16px">
      <img src="../../public/assets/img/logocm2e.png" alt="CM2E" style="height:60px" onerror="this.src='../../public/assets/img/logocm2e.jpg'">
    </div>
    <h2 style="margin:0 0 8px;font-size:20px"><?= $lang['app_name'] ?></h2>
    <p style="color:var(--txt3);font-size:13px"><?= $lang['company_full'] ?></p>
    <div style="margin-top:24px;display:flex;flex-direction:column;gap:10px">
      <div class="info-row"><span class="info-label">Version</span><span class="info-value">3.0.0</span></div>
      <div class="info-row"><span class="info-label"><?= $lang['platform'] ?></span><span class="info-value">PHP + MySQL (XAMPP)</span></div>
      <div class="info-row"><span class="info-label"><?= $lang['timezone'] ?></span><span class="info-value">Africa/Tunis (UTC+1)</span></div>
    </div>
    <p style="color:var(--txt3);font-size:12px;margin-top:24px">
      <?= $lang['attendance_project_system'] ?><br>
      <?= $lang['rfid_badge'] ?>
    </p>
  </div>
</div>
