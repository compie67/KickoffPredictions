<?php
$tournament = $params['tournament'];
if (!KickoffTournament::exists($tournament)) {
    echo '<div class="kickoff-wrap"><div class="kickoff-card kickoff-warning">' . ossn_print('kickoff:not_imported') . '</div></div>';
    return;
}
$user = ossn_loggedin_user();
$teams = KickoffTournament::teams($tournament);
$bonus = KickoffPrediction::getBonus($tournament, $user->guid);
$groups = array();
foreach ($teams as $team) {
    $g = isset($team['group']) ? $team['group'] : '';
    if ($g !== '') { $groups[$g][] = $team; }
}
ksort($groups);
?>
<div class="kickoff-wrap">
  <div class="kickoff-page-head">
    <a class="kickoff-back" href="<?php echo kickoff_url('home', $tournament); ?>">&larr; <?php echo ossn_print('kickoff:back'); ?></a>
    <h2><i class="fa fa-star"></i> <?php echo ossn_print('kickoff:bonus'); ?></h2>
    <p class="kickoff-muted"><?php echo ossn_print('kickoff:bonus:help'); ?></p>
  </div>
  <div class="kickoff-card">
    <form action="<?php echo ossn_site_url('action/kickoff/bonus/save'); ?>" method="post">
      <?php echo ossn_plugin_view('input/security_token'); ?>
      <input type="hidden" name="tournament" value="<?php echo htmlspecialchars($tournament, ENT_QUOTES, 'UTF-8'); ?>" />
      <div class="kickoff-field">
        <label><?php echo ossn_print('kickoff:bonus:winner'); ?></label>
        <select name="winner" class="form-control">
          <option value=""><?php echo ossn_print('kickoff:select'); ?></option>
          <?php foreach ($teams as $team) { $selected = ($bonus['winner'] === $team['id']) ? 'selected' : ''; $flag = KickoffTournament::displayFlag(isset($team['flag']) ? $team['flag'] : '', isset($team['country']) ? $team['country'] : ''); $label = ($flag !== '' ? $flag . ' ' : '') . (isset($team['name']) ? $team['name'] : $team['id']); ?>
            <option value="<?php echo htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php } ?>
        </select>
      </div>
      <?php if ($groups) { ?>
      <h3><?php echo ossn_print('kickoff:bonus:group_winners'); ?></h3>
      <div class="kickoff-bonus-grid">
      <?php foreach ($groups as $group => $groupTeams) { ?>
        <div class="kickoff-field">
          <label><?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?></label>
          <select name="group_winners[<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>]" class="form-control">
            <option value=""><?php echo ossn_print('kickoff:select'); ?></option>
            <?php foreach ($groupTeams as $team) {
                $current = isset($bonus['group_winners'][$group]) ? $bonus['group_winners'][$group] : '';
                $selected = ($current === $team['id']) ? 'selected' : '';
            ?>
              <?php $flag = KickoffTournament::displayFlag(isset($team['flag']) ? $team['flag'] : '', isset($team['country']) ? $team['country'] : ''); $label = ($flag !== '' ? $flag . ' ' : '') . (isset($team['name']) ? $team['name'] : $team['id']); ?>
              <option value="<?php echo htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php } ?>
          </select>
        </div>
      <?php } ?>
      </div>
      <?php } ?>
      <p><button type="submit" class="btn btn-primary kickoff-save-button"><i class="fa fa-save"></i> <?php echo ossn_print('save'); ?></button></p>
    </form>
  </div>
</div>
