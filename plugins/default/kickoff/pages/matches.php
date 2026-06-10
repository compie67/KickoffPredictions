<?php
$tournament = $params['tournament'];
if (!KickoffTournament::exists($tournament)) {
    echo '<div class="kickoff-wrap"><div class="kickoff-card kickoff-warning">' . ossn_print('kickoff:not_imported') . '</div></div>';
    return;
}
$publicView = !empty($params['public_view']);
$user = ossn_isLoggedin() ? ossn_loggedin_user() : null;
$info = KickoffTournament::get($tournament);
$type = KickoffTournament::predictionType($tournament);
$teams = KickoffTournament::teams($tournament);
$matches = KickoffTournament::matches($tournament);
$settings = KickoffTournament::settings($tournament);
$userPredictions = $user ? KickoffPrediction::getUserPredictions($tournament, $user->guid) : array('predictions' => array());
$predMap = isset($userPredictions['predictions']) ? $userPredictions['predictions'] : array();
$results = KickoffTournament::results($tournament);
$resultMap = isset($results['results']) ? $results['results'] : array();
$currentStage = '';
$hasPred = function($matchId) use ($predMap, $type) { if (empty($predMap[$matchId])) return false; $p=$predMap[$matchId]; return ($type === 'pick_winner') ? !empty($p['winner_id']) : (isset($p['home_score']) && isset($p['away_score'])); };
?>
<div class="kickoff-wrap" data-kickoff-tournament="<?php echo htmlspecialchars($tournament, ENT_QUOTES, 'UTF-8'); ?>" data-kickoff-type="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="kickoff-page-head">
    <?php if (!$publicView) { ?><a class="kickoff-back" href="<?php echo kickoff_url('home', $tournament); ?>">&larr; <?php echo ossn_print('kickoff:back'); ?></a><?php } ?>
    <h2><?php echo htmlspecialchars(isset($info['title']) ? $info['title'] : ossn_print('kickoff:matches'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <p class="kickoff-muted"><?php echo $publicView ? ossn_print('kickoff:public:matches_hint') : (($type === 'pick_winner') ? ossn_print('kickoff:autosave:winner_hint') : ossn_print('kickoff:autosave:hint')); ?></p>
  </div>
  <div class="kickoff-card kickoff-share">
    <strong><i class="fa fa-share-alt"></i> <?php echo ossn_print('kickoff:share:public'); ?></strong>
    <input class="form-control kickoff-share-input" readonly value="<?php echo htmlspecialchars(kickoff_url('matches', $tournament), ENT_QUOTES, 'UTF-8'); ?>" onclick="this.select();" />
  </div>
  <div class="kickoff-filterbar kickoff-card">
    <button type="button" class="kickoff-filter active" data-filter="all"><?php echo ossn_print('kickoff:filter:all'); ?></button>
    <button type="button" class="kickoff-filter" data-filter="open"><?php echo ossn_print('kickoff:filter:open'); ?></button>
    <button type="button" class="kickoff-filter" data-filter="locked"><?php echo ossn_print('kickoff:filter:locked'); ?></button>
    <button type="button" class="kickoff-filter" data-filter="filled"><?php echo ossn_print('kickoff:filter:filled'); ?></button>
  </div>
  <?php if (!$publicView) { ?><div class="kickoff-token-source" style="display:none"><?php echo ossn_plugin_view('input/security_token'); ?></div><?php } ?>
  <?php foreach ($matches as $match) {
      $stage = isset($match['stage']) ? $match['stage'] : 'matches';
      if ($stage !== $currentStage) {
          $currentStage = $stage;
          $label = ossn_print('kickoff:stage:' . $stage);
          if ($label === 'kickoff:stage:' . $stage) { $label = $stage; }
          echo '<h3 class="kickoff-stage"><i class="fa fa-calendar"></i> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h3>';
      }
      $matchId = $match['id'];
      $prediction = isset($predMap[$matchId]) ? $predMap[$matchId] : array();
      $result = isset($resultMap[$matchId]) ? $resultMap[$matchId] : null;
      $resultEntered = KickoffTournament::hasResult($tournament, $matchId);
      $timeLocked = KickoffTournament::isLocked($match, $settings);
      $locked = $timeLocked || $resultEntered;
      $filled = $hasPred($matchId);
      $homeName = isset($match['home']) ? KickoffTournament::teamName($teams, $match['home']) : '';
      $awayName = isset($match['away']) ? KickoffTournament::teamName($teams, $match['away']) : '';
  ?>
    <div class="kickoff-card kickoff-match <?php echo $locked ? 'is-locked' : ''; ?> <?php echo $filled ? 'is-filled' : ''; ?>" data-locked="<?php echo $locked ? '1' : '0'; ?>" data-filled="<?php echo $filled ? '1' : '0'; ?>">
      <div class="kickoff-match-meta">
        <span><i class="fa fa-tag"></i> <?php echo htmlspecialchars(isset($match['group']) ? $match['group'] : (isset($match['location']) ? $match['location'] : ''), ENT_QUOTES, 'UTF-8'); ?></span>
        <span><i class="fa fa-clock-o"></i> <?php echo !empty($match['kickoff']) ? date('d-m-Y H:i', strtotime($match['kickoff'])) : ''; ?></span>
        <?php if ($resultEntered) { ?><span class="kickoff-pill"><?php echo ossn_print('kickoff:result_entered'); ?></span><?php } elseif ($locked) { ?><span class="kickoff-pill"><?php echo ossn_print('kickoff:locked'); ?></span><?php } ?>
      </div>
      <?php if ($type === 'pick_winner') { ?>
        <div class="kickoff-pick-row">
          <strong><?php echo htmlspecialchars(KickoffTournament::matchTitle($match, $teams), ENT_QUOTES, 'UTF-8'); ?></strong>
          <select class="form-control kickoff-winner-pick" data-match="<?php echo htmlspecialchars($matchId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($locked || $publicView) ? 'disabled' : ''; ?>>
            <option value=""><?php echo ossn_print('kickoff:select'); ?></option>
            <?php foreach ((isset($match['competitors']) && is_array($match['competitors']) ? $match['competitors'] : array()) as $competitor) {
              $selected = (isset($prediction['winner_id']) && $prediction['winner_id'] === $competitor['id']) ? 'selected' : '';
            ?>
              <option value="<?php echo htmlspecialchars($competitor['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($competitor['name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php } ?>
          </select>
        </div>
      <?php } else { ?>
        <div class="kickoff-match-row">
          <div class="kickoff-team kickoff-home"><span><?php echo htmlspecialchars($homeName, ENT_QUOTES, 'UTF-8'); ?></span></div>
          <input class="kickoff-score" type="number" min="0" max="99" inputmode="numeric" data-match="<?php echo htmlspecialchars($matchId, ENT_QUOTES, 'UTF-8'); ?>" data-side="home" value="<?php echo isset($prediction['home_score']) ? (int)$prediction['home_score'] : ''; ?>" <?php echo ($locked || $publicView) ? 'disabled' : ''; ?> />
          <span class="kickoff-sep">-</span>
          <input class="kickoff-score" type="number" min="0" max="99" inputmode="numeric" data-match="<?php echo htmlspecialchars($matchId, ENT_QUOTES, 'UTF-8'); ?>" data-side="away" value="<?php echo isset($prediction['away_score']) ? (int)$prediction['away_score'] : ''; ?>" <?php echo ($locked || $publicView) ? 'disabled' : ''; ?> />
          <div class="kickoff-team kickoff-away"><span><?php echo htmlspecialchars($awayName, ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
      <?php } ?>
      <div class="kickoff-status" data-status-for="<?php echo htmlspecialchars($matchId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php
          if ($result) {
              if (isset($result['winner_id'])) {
                  echo ossn_print('kickoff:result') . ': ' . htmlspecialchars(KickoffTournament::competitorName($match, $result['winner_id']), ENT_QUOTES, 'UTF-8');
              } else {
                  echo ossn_print('kickoff:result') . ': ' . (int)$result['home_score'] . '-' . (int)$result['away_score'];
              }
          }
        ?>
      </div>
    </div>
  <?php } ?>
</div>
