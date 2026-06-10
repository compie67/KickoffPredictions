<?php
if (!ossn_isAdminLoggedin()) {
    redirect('');
}
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$matchId = kickoff_input('match_id');
if (!$matchId) {
    ossn_trigger_message(ossn_print('kickoff:error:invalid'), 'error');
    redirect('kickoff/admin?tournament=' . urlencode($tournament));
}
$type = KickoffTournament::predictionType($tournament);
if ($type === 'pick_winner') {
    $winnerId = trim((string)kickoff_input('winner_id'));
    if ($winnerId === '') {
        ossn_trigger_message(ossn_print('kickoff:error:invalid'), 'error');
        redirect('kickoff/admin?tournament=' . urlencode($tournament));
    }
    $ok = KickoffTournament::saveResult($tournament, $matchId, null, null, $winnerId);
} else {
    $home = kickoff_safe_int(kickoff_input('home_score'), 0, 99);
    $away = kickoff_safe_int(kickoff_input('away_score'), 0, 99);
    if ($home === null || $away === null) {
        ossn_trigger_message(ossn_print('kickoff:error:invalid'), 'error');
        redirect('kickoff/admin?tournament=' . urlencode($tournament));
    }
    $ok = KickoffTournament::saveResult($tournament, $matchId, $home, $away);
}
if ($ok) {
    ossn_trigger_message(ossn_print('kickoff:result:saved'));
} else {
    ossn_trigger_message(ossn_print('kickoff:error:save'), 'error');
}
redirect('kickoff/admin?tournament=' . urlencode($tournament));
