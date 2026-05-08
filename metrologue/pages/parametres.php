<?php require_once __DIR__ . '/../../lang_init.php'; ?>

<div class="page-header animate-in">
  <div>
    <h1><?= $lang['settings'] ?></h1>
    <div class="page-subtitle"><?= $lang['settings_subtitle'] ?></div>
  </div>
</div>

<div class="settings-card animate-in">

  <!-- Langue -->
  <div class="settings-section">
    <div class="settings-section-title"><span class="s-icon">🌐</span><?= $lang['language_section'] ?></div>
    <form method="POST" action="../lang_init.php">
      <input type="hidden" name="_redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
      <div class="settings-row">
        <label class="settings-label"><?= $lang['language'] ?></label>
        <div class="toggle-group">
          <button type="submit" name="lang" value="fr" class="toggle-btn <?= ($_SESSION['lang']??'fr')==='fr'?'active':'' ?>">🇫🇷 <?= $lang['french'] ?></button>
          <button type="submit" name="lang" value="en" class="toggle-btn <?= ($_SESSION['lang']??'fr')==='en'?'active':'' ?>">🇬🇧 <?= $lang['english'] ?></button>
        </div>
      </div>
    </form>
  </div>

  <!-- Thème -->
  <div class="settings-section">
    <div class="settings-section-title"><span class="s-icon">🎨</span><?= $lang['theme'] ?></div>
    <form method="POST" action="../lang_init.php">
      <input type="hidden" name="_redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
      <div class="settings-row">
        <label class="settings-label"><?= $lang['theme'] ?></label>
        <div class="toggle-group">
          <button type="submit" name="theme" value="light" class="toggle-btn <?= ($_SESSION['theme']??'light')==='light'?'active':'' ?>">☀️ <?= $lang['light'] ?></button>
          <button type="submit" name="theme" value="dark"  class="toggle-btn <?= ($_SESSION['theme']??'light')==='dark'?'active':'' ?>">🌙 <?= $lang['dark'] ?></button>
        </div>
      </div>
    </form>
  </div>

  <!-- Taille texte -->
  <div class="settings-section">
    <div class="settings-section-title"><span class="s-icon">🔤</span><?= $lang['text_size'] ?></div>
    <form method="POST" action="../lang_init.php">
      <input type="hidden" name="_redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
      <div class="settings-row">
        <label class="settings-label"><?= $lang['text_size'] ?></label>
        <div class="toggle-group">
          <button type="submit" name="taille" value="normal" class="toggle-btn <?= ($_SESSION['taille']??'normal')==='normal'?'active':'' ?>"><?= $lang['normal'] ?></button>
          <button type="submit" name="taille" value="grand"  class="toggle-btn <?= ($_SESSION['taille']??'normal')==='grand'?'active':'' ?>"><?= $lang['large'] ?></button>
        </div>
      </div>
    </form>
  </div>

</div>
