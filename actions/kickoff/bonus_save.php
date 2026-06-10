<?php
if (!ossn_isLoggedin()) {
    redirect('');
}
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$winner = trim((string)kickoff_input('winner'));
$groupWinners = kickoff_input('group_winners');
if (!is_array($groupWinners)) {
    $groupWinners = array();
}
$user = ossn_loggedin_user();
if (KickoffPrediction::saveBonus($tournament, $user->guid, $winner, $groupWinners)) {
    ossn_trigger_message(ossn_print('kickoff:bonus:saved'));
} else {
    ossn_trigger_message(ossn_print('kickoff:error:save'), 'error');
}
redirect('kickoff/bonus?tournament=' . urlencode($tournament));
