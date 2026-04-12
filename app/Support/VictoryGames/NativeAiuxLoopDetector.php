<?php

namespace App\Support\VictoryGames;

class NativeAiuxLoopDetector
{
    public function fingerprint(string $actionType, array $actionParams): string
    {
        return substr(hash('sha256', $actionType.':'.json_encode($actionParams)), 0, 12);
    }

    public function isLooping(array $fingerprints, array $rules, array $actionHistory = []): bool
    {
        if (empty($fingerprints)) {
            return false;
        }

        if ($this->repeatedExecuteJsInspection($actionHistory)) {
            return true;
        }

        $repeatSingle = (int) ($rules['repeat_single'] ?? 4);
        $repeatAlternating = (int) ($rules['repeat_alternating'] ?? 3);
        $minActions = (int) ($rules['min_actions_before_loop'] ?? 6);
        $staleUrlActions = (int) ($rules['stale_url_actions'] ?? 10);

        if (!empty($actionHistory) && count($actionHistory) < $minActions) {
            return false;
        }

        if (count($fingerprints) >= $repeatSingle) {
            $tail = array_slice($fingerprints, -$repeatSingle);

            if (count(array_unique($tail)) === 1 && $this->historyStayedOnSameUrl(array_slice($actionHistory, -$repeatSingle))) {
                return true;
            }
        }

        if (count($fingerprints) >= $repeatAlternating * 2) {
            $tail = array_slice($fingerprints, -($repeatAlternating * 2));
            $even = array_unique(array_values(array_filter($tail, fn ($value, $index) => $index % 2 === 0, ARRAY_FILTER_USE_BOTH)));
            $odd = array_unique(array_values(array_filter($tail, fn ($value, $index) => $index % 2 === 1, ARRAY_FILTER_USE_BOTH)));

            if (count($even) === 1 && count($odd) === 1 && $even !== $odd && $this->historyStayedOnSameUrl(array_slice($actionHistory, -($repeatAlternating * 2)))) {
                return true;
            }
        }

        if (!empty($actionHistory) && count($actionHistory) >= $staleUrlActions) {
            $tail = array_slice($actionHistory, -$staleUrlActions);

            if ($this->historyStayedOnSameUrl($tail) && count($this->uniqueActionSignatures($tail)) <= 2) {
                return true;
            }
        }

        return false;
    }

    private function repeatedExecuteJsInspection(array $actionHistory): bool
    {
        if (count($actionHistory) < 3) {
            return false;
        }

        $tail = array_slice($actionHistory, -3);

        foreach ($tail as $item) {
            if (($item['action_type'] ?? null) !== 'execute_js' || !($item['success'] ?? false)) {
                return false;
            }
        }

        $urls = array_unique(array_map(fn ($item) => $item['url'] ?? null, $tail));
        $results = array_unique(array_map(fn ($item) => (string) ($item['execution_result'] ?? ''), $tail));

        return count($urls) === 1 && count($results) <= 1;
    }

    private function historyStayedOnSameUrl(array $actionHistory): bool
    {
        $urls = array_filter(array_map(fn ($item) => $item['url'] ?? null, $actionHistory));

        return !empty($urls) && count(array_unique($urls)) === 1;
    }

    private function uniqueActionSignatures(array $actionHistory): array
    {
        return array_unique(array_map(function (array $item) {
            return $this->fingerprint(
                (string) ($item['action_type'] ?? ''),
                (array) ($item['action_params'] ?? [])
            );
        }, $actionHistory));
    }
}
