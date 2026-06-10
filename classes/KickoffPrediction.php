<?php
class KickoffPrediction {
    public static function userFile($tournament, $userGuid) {
        return KickoffStorage::path('predictions', $tournament, 'users', (int)$userGuid . '.json');
    }
    public static function bonusFile($tournament, $userGuid) {
        return KickoffStorage::path('bonus', $tournament, 'users', (int)$userGuid . '.json');
    }

    public static function getUserPredictions($tournament, $userGuid) {
        return KickoffStorage::readJson(self::userFile($tournament, $userGuid), array(
            'user_guid' => (int)$userGuid,
            'tournament' => $tournament,
            'predictions' => array(),
        ));
    }

    public static function savePrediction($tournament, $userGuid, $matchId, $home, $away) {
        $data = self::getUserPredictions($tournament, $userGuid);
        if (!isset($data['predictions']) || !is_array($data['predictions'])) {
            $data['predictions'] = array();
        }
        $data['predictions'][$matchId] = array(
            'home_score' => $home,
            'away_score' => $away,
            'updated' => KickoffStorage::now(),
        );
        $data['updated'] = KickoffStorage::now();
        return KickoffStorage::writeJson(self::userFile($tournament, $userGuid), $data);
    }



    public static function saveWinnerPrediction($tournament, $userGuid, $matchId, $winnerId) {
        $data = self::getUserPredictions($tournament, $userGuid);
        if (!isset($data['predictions']) || !is_array($data['predictions'])) {
            $data['predictions'] = array();
        }
        $data['predictions'][$matchId] = array(
            'winner_id' => $winnerId,
            'updated' => KickoffStorage::now(),
        );
        $data['updated'] = KickoffStorage::now();
        return KickoffStorage::writeJson(self::userFile($tournament, $userGuid), $data);
    }

    public static function getPrediction($tournament, $userGuid, $matchId) {
        $data = self::getUserPredictions($tournament, $userGuid);
        return isset($data['predictions'][$matchId]) ? $data['predictions'][$matchId] : null;
    }

    public static function allPredictionFiles($tournament) {
        return KickoffStorage::listJsonFiles(KickoffStorage::path('predictions', $tournament, 'users'));
    }


    public static function userDisplayName($userGuid) {
        $userGuid = (int)$userGuid;
        if (function_exists('ossn_user_by_guid')) {
            $user = ossn_user_by_guid($userGuid);
            if ($user) {
                if (!empty($user->fullname)) { return $user->fullname; }
                if (!empty($user->username)) { return $user->username; }
            }
        }
        return 'User #' . $userGuid;
    }

    public static function predictionStats($tournament) {
        $files = self::allPredictionFiles($tournament);
        $players = 0;
        $predictions = 0;
        foreach ($files as $file) {
            $data = KickoffStorage::readJson($file, array());
            $items = isset($data['predictions']) && is_array($data['predictions']) ? $data['predictions'] : array();
            if (!empty($items)) {
                $players++;
                $predictions += count($items);
            }
        }
        $bonusPlayers = 0;
        foreach (self::allBonusFiles($tournament) as $file) {
            $data = KickoffStorage::readJson($file, array());
            $hasWinner = !empty($data['winner']);
            $hasGroups = !empty($data['group_winners']) && is_array($data['group_winners']) && count(array_filter($data['group_winners'])) > 0;
            if ($hasWinner || $hasGroups) { $bonusPlayers++; }
        }
        return array('players' => $players, 'predictions' => $predictions, 'bonus_players' => $bonusPlayers);
    }

    public static function overviewRows($tournament, array $matches = array(), array $teams = array(), $limit = 300) {
        $matchMap = array();
        foreach ($matches as $match) {
            if (!empty($match['id'])) { $matchMap[$match['id']] = $match; }
        }
        $rows = array();
        foreach (self::allPredictionFiles($tournament) as $file) {
            $data = KickoffStorage::readJson($file, array());
            if (empty($data['user_guid'])) { continue; }
            $userGuid = (int)$data['user_guid'];
            $items = isset($data['predictions']) && is_array($data['predictions']) ? $data['predictions'] : array();
            foreach ($items as $matchId => $prediction) {
                $match = isset($matchMap[$matchId]) ? $matchMap[$matchId] : array('id' => $matchId);
                if (isset($prediction['winner_id'])) {
                    $value = KickoffTournament::competitorName($match, $prediction['winner_id']);
                } else {
                    $home = isset($prediction['home_score']) ? $prediction['home_score'] : '';
                    $away = isset($prediction['away_score']) ? $prediction['away_score'] : '';
                    $value = $home . ' - ' . $away;
                }
                $rows[] = array(
                    'user_guid' => $userGuid,
                    'user_name' => self::userDisplayName($userGuid),
                    'match_id' => $matchId,
                    'match_title' => KickoffTournament::matchTitle($match, $teams),
                    'kickoff' => isset($match['kickoff']) ? $match['kickoff'] : '',
                    'prediction' => $value,
                    'updated' => isset($prediction['updated']) ? $prediction['updated'] : (isset($data['updated']) ? $data['updated'] : ''),
                );
                if (count($rows) >= $limit) {
                    return $rows;
                }
            }
        }
        usort($rows, function($a, $b){ return strcmp($b['updated'], $a['updated']); });
        return $rows;
    }


    public static function getBonus($tournament, $userGuid) {
        return KickoffStorage::readJson(self::bonusFile($tournament, $userGuid), array(
            'user_guid' => (int)$userGuid,
            'tournament' => $tournament,
            'winner' => '',
            'group_winners' => array(),
        ));
    }

    public static function saveBonus($tournament, $userGuid, $winner, array $groupWinners = array()) {
        $data = array(
            'user_guid' => (int)$userGuid,
            'tournament' => $tournament,
            'winner' => $winner,
            'group_winners' => $groupWinners,
            'updated' => KickoffStorage::now(),
        );
        return KickoffStorage::writeJson(self::bonusFile($tournament, $userGuid), $data);
    }

    public static function allBonusFiles($tournament) {
        return KickoffStorage::listJsonFiles(KickoffStorage::path('bonus', $tournament, 'users'));
    }
}
