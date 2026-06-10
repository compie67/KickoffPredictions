<?php
$tournament = $params['tournament'];
$exists = KickoffTournament::exists($tournament);
$imported = KickoffTournament::importedTournaments();
?>
<div class="kickoff-wrap kickoff-page-home">
  <div class="kickoff-hero kickoff-card">
    <div>
      <div class="kickoff-kicker"><?php echo ossn_print('kickoff:menu'); ?></div>
      <h2><?php echo ossn_print('kickoff:title'); ?></h2>
      <p><?php echo ossn_print('kickoff:intro'); ?></p>
    </div>
    <div class="kickoff-hero-icon"><i class="fa fa-trophy"></i></div>
  </div>

  <?php if ($imported) { ?>
    <div class="kickoff-card kickoff-select-card">
      <label><?php echo ossn_print('kickoff:tournament:choose'); ?></label>
      <select class="form-control kickoff-tournament-switch">
        <?php foreach ($imported as $item) { $selected = ($item['id'] === $tournament) ? 'selected' : ''; ?>
          <option value="<?php echo htmlspecialchars(kickoff_url('home', $item['id']), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php } ?>
      </select>
    </div>
  <?php } ?>

  <?php if (!$exists) { ?>
    <div class="kickoff-card kickoff-warning">
      <?php echo ossn_print('kickoff:not_imported'); ?>
      <?php if (ossn_isAdminLoggedin()) { ?>
        <p><a class="btn btn-primary" href="<?php echo ossn_site_url('administrator/component/KickoffPredictions'); ?>"><?php echo ossn_print('kickoff:admin:open'); ?></a></p>
      <?php } ?>
    </div>
  <?php } else { $info = KickoffTournament::get($tournament); ?>
    <div class="kickoff-grid kickoff-nav-grid">
      <a class="kickoff-card kickoff-tile kickoff-tile-primary" href="<?php echo kickoff_url('matches', $tournament); ?>">
        <i class="fa fa-futbol-o"></i>
        <strong><?php echo ossn_print('kickoff:matches'); ?></strong>
        <span><?php echo ossn_print('kickoff:matches:desc'); ?></span>
        <em><?php echo ossn_print('kickoff:open'); ?> →</em>
      </a>
      <a class="kickoff-card kickoff-tile" href="<?php echo kickoff_url('leaderboard', $tournament); ?>">
        <i class="fa fa-list-ol"></i>
        <strong><?php echo ossn_print('kickoff:leaderboard'); ?></strong>
        <span><?php echo ossn_print('kickoff:leaderboard:desc'); ?></span>
        <em><?php echo ossn_print('kickoff:open'); ?> →</em>
      </a>
      <a class="kickoff-card kickoff-tile" href="<?php echo kickoff_url('bonus', $tournament); ?>">
        <i class="fa fa-star"></i>
        <strong><?php echo ossn_print('kickoff:bonus'); ?></strong>
        <span><?php echo ossn_print('kickoff:bonus:desc'); ?></span>
        <em><?php echo ossn_print('kickoff:open'); ?> →</em>
      </a>
    </div>
    <div class="kickoff-card kickoff-info-card">
      <h3><?php echo htmlspecialchars(isset($info['title']) ? $info['title'] : $tournament, ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo !empty($info['description']) ? htmlspecialchars($info['description'], ENT_QUOTES, 'UTF-8') : ossn_print('kickoff:intro'); ?></p>
      <p class="kickoff-muted"><i class="fa fa-info-circle"></i> <?php echo ossn_print('kickoff:tournament:type'); ?>: <?php echo htmlspecialchars(isset($info['prediction_type']) ? $info['prediction_type'] : 'score', ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  <?php } ?>
</div>
