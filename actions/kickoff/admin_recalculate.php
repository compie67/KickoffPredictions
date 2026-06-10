<?php
if (!ossn_isAdminLoggedin()) {
    redirect('');
}
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
if (KickoffLeaderboard::recalculate($tournament)) {
    ossn_trigger_message(ossn_print('kickoff:leaderboard:recalculated'));
} else {
    ossn_trigger_message(ossn_print('kickoff:error:save'), 'error');
}
redirect('kickoff/admin?tournament=' . urlencode($tournament));
