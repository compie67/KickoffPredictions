<?php
if (!ossn_isLoggedin()) {
    kickoff_action_json(false, ossn_print('kickoff:error:login'));
}
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$matchId = kickoff_input('match_id');
if (!$matchId) {
    kickoff_action_json(false, ossn_print('kickoff:error:invalid'));
}
if (!KickoffTournament::exists($tournament)) {
    kickoff_action_json(false, ossn_print('kickoff:error:not_imported'));
}
$match = KickoffTournament::matchById($tournament, $matchId);
if (!$match) {
    kickoff_action_json(false, ossn_print('kickoff:error:match_missing'));
}
if (KickoffTournament::hasResult($tournament, $matchId)) {
    kickoff_action_json(false, ossn_print('kickoff:error:result_entered'));
}
if (KickoffTournament::isLocked($match, KickoffTournament::settings($tournament))) {
    kickoff_action_json(false, ossn_print('kickoff:error:locked'));
}
$user = ossn_loggedin_user();
$type = KickoffTournament::predictionType($tournament);
if ($type === 'pick_winner') {
    $winnerId = trim((string)kickoff_input('winner_id'));
    if ($winnerId === '') {
        kickoff_action_json(false, ossn_print('kickoff:error:invalid'));
    }
    $ok = KickoffPrediction::saveWinnerPrediction($tournament, $user->guid, $matchId, $winnerId);
} else {
    $home = kickoff_safe_int(kickoff_input('home_score'), 0, 99);
    $away = kickoff_safe_int(kickoff_input('away_score'), 0, 99);
    if ($home === null || $away === null) {
        kickoff_action_json(false, ossn_print('kickoff:error:invalid'));
    }
    $ok = KickoffPrediction::savePrediction($tournament, $user->guid, $matchId, $home, $away);
}
kickoff_action_json($ok, $ok ? ossn_print('kickoff:saved') : ossn_print('kickoff:error:save'));
