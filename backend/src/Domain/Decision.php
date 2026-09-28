<?php

declare(strict_types=1);

namespace CarMoneyLab\Domain;

/**
 * Результат DecisionEngine: решение и код причины
 * (значения — константы DecisionEngine).
 */
final class Decision
{
    public function __construct(
        public readonly string $decision,
        public readonly string $reason,
    ) {
    }
}
