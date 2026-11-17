<?php

namespace App\Services;

use App\Models\Employee;

class FaceMatcher
{
    private const THRESHOLD = 0.5;  // ajústalo con pruebas reales
    private const MARGIN = 0.05;    // diferencia mínima con el segundo mejor

    /** @return array{employee: Employee, distance: float}|null */
    public function match(array $input, int $tenantId): ?array
    {
        $best = null;
        $bestDist = INF;
        $second = INF;

        Employee::where('tenant_id', $tenantId)
            ->whereNotNull('face_descriptor')
            ->each(function (Employee $e) use ($input, &$best, &$bestDist, &$second) {
                $d = $this->distance($e->face_descriptor, $input);
                if ($d < $bestDist) {
                    $second = $bestDist;
                    $bestDist = $d;
                    $best = $e;
                } elseif ($d < $second) {
                    $second = $d;
                }
            }
        );

        if (!$best || $bestDist > self::THRESHOLD || ($second - $bestDist) < self::MARGIN) {
            return null;
        }

        return ['employee' => $best, 'distance' => $bestDist];
    }

    private function distance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $v) {
            $sum += ($v - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }
}