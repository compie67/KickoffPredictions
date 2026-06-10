<?php
class KickoffLeaderboard {
    public static function file($tournament) {
        return KickoffStorage::path('leaderboard', $tournament, 'leaderboard.json');
    }

    public static function get($tournament) {
        return KickoffStorage::readJson(self::file($tournament), array('tournament' => $tournament, 'rows' => array(), 'updated' => null));
    }

    public static function recalculate($tournament) {
        $settings = KickoffTournament::settings($tournament);
        $results = KickoffTournament::results($tournament);
        $resultMap = isset($results['results']) && is_array($results['results']) ? $results['results'] : array();
        $rows = array();

        foreach (KickoffPrediction::allPredictionFiles($tournament) as $file) {
            $data = KickoffStorage::readJson($file, array());
            if (empty($data['user_guid'])) {
                continue;
            }
            $points = 0;
            $exact = 0;
            $correct = 0;
            $played = 0;
            $predictions = isset($data['predictions']) && is_array($data['predictions']) ? $data['predictions'] : array();
            foreach ($predictions as $matchId => $prediction) {
                if (!isset($resultMap[$matchId])) {
                    continue;
                }
                $played++;
                $p = KickoffScoring::matchPoints($prediction, $resultMap[$matchId], $settings);
                $points += $p;
                if (KickoffScoring::isExact($prediction, $resultMap[$matchId], $settings)) {
                    $exact++;
                }
                if (self::hasCorrectOutcome($prediction, $resultMap[$matchId])) {
                    $correct++;
                }
            }
            $rows[] = array(
                'user_guid' => (int)$data['user_guid'],
                'points' => $points,
                'exact' => $exact,
                'correct_result' => $correct,
                'played_scored' => $played,
            );
        }

        usort($rows, function($a, $b) {
            if ($a['points'] === $b['points']) {
                if ($a['exact'] === $b['exact']) {
                    return $a['user_guid'] <=> $b['user_guid'];
                }
                return $b['exact'] <=> $a['exact'];
            }
            return $b['points'] <=> $a['points'];
        });
        $rank = 1;
        foreach ($rows as &$row) {
            $row['rank'] = $rank++;
        }
        unset($row);

        return KickoffStorage::writeJson(self::file($tournament), array(
            'tournament' => $tournament,
            'rows' => $rows,
            'updated' => KickoffStorage::now(),
        ));
    }

    private static function hasCorrectOutcome(array $prediction, array $result) {
        if (isset($prediction['winner_id'], $result['winner_id'])) {
            return $prediction['winner_id'] !== '' && $prediction['winner_id'] === $result['winner_id'];
        }
        if (!isset($prediction['home_score'], $prediction['away_score'], $result['home_score'], $result['away_score'])) {
            return false;
        }
        return KickoffScoring::outcome((int)$prediction['home_score'], (int)$prediction['away_score']) === KickoffScoring::outcome((int)$result['home_score'], (int)$result['away_score']);
    }
}
