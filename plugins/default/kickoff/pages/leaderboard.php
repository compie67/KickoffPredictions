<?php
$tournament = $params['tournament'];
$publicView = !empty($params['public_view']);
$board = KickoffLeaderboard::get($tournament);
$rows = isset($board['rows']) ? $board['rows'] : array();
?>
<div class="kickoff-wrap">
  <div class="kickoff-page-head">
    <?php if (!$publicView) { ?><a class="kickoff-back" href="<?php echo kickoff_url('home', $tournament); ?>">&larr; <?php echo ossn_print('kickoff:back'); ?></a><?php } ?>
    <h2><i class="fa fa-list-ol"></i> <?php echo ossn_print('kickoff:leaderboard'); ?></h2>
  </div>
  <div class="kickoff-card kickoff-share">
    <strong><i class="fa fa-share-alt"></i> <?php echo ossn_print('kickoff:share:public'); ?></strong>
    <input class="form-control kickoff-share-input" readonly value="<?php echo htmlspecialchars(kickoff_url('leaderboard', $tournament), ENT_QUOTES, 'UTF-8'); ?>" onclick="this.select();" />
  </div>
  <div class="kickoff-card">
    <div class="kickoff-table-wrap">
      <table class="kickoff-table">
        <thead><tr><th>#</th><th><?php echo ossn_print('kickoff:user'); ?></th><th><?php echo ossn_print('kickoff:points'); ?></th><th><?php echo ossn_print('kickoff:exact'); ?></th><th><?php echo ossn_print('kickoff:correct'); ?></th></tr></thead>
        <tbody>
        <?php if (!$rows) { ?>
          <tr><td colspan="5" class="kickoff-empty"><?php echo ossn_print('kickoff:leaderboard:empty'); ?></td></tr>
        <?php } foreach ($rows as $row) {
            $u = ossn_user_by_guid($row['user_guid']);
            $name = $u ? $u->fullname : '#' . $row['user_guid'];
        ?>
          <tr>
            <td><span class="kickoff-rank"><?php echo (int)$row['rank']; ?></span></td>
            <td><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
            <td><strong><?php echo (int)$row['points']; ?></strong></td>
            <td><?php echo (int)$row['exact']; ?></td>
            <td><?php echo (int)$row['correct_result']; ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($board['updated'])) { ?><p class="kickoff-muted"><i class="fa fa-clock-o"></i> <?php echo ossn_print('kickoff:updated'); ?>: <?php echo htmlspecialchars($board['updated'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
  </div>
</div>
