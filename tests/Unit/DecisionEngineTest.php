<?php

declare(strict_types=1);

namespace CarMoneyLab\Tests\Unit;

use CarMoneyLab\Domain\DecisionEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecisionEngineTest extends TestCase
{
    private DecisionEngine $engine;

    /** @var array<string,mixed> */
    private array $rules;

    protected function setUp(): void
    {
        $this->rules = require __DIR__ . '/../../backend/config/rules.php';

        $this->engine = new DecisionEngine(
            $this->rules['ltv'],
            $this->reviewFromKm(),
        );
    }

    #[DataProvider('mileageBoundaries')]
    public function testDecidesByMileageAtLtvApprove(int $offsetFromThreshold, string $decision, string $reason): void
    {
        $result = $this->engine->decide($this->approveLtv(), $this->reviewFromKm() + $offsetFromThreshold);

        self::assertSame($decision, $result->decision);
        self::assertSame($reason, $result->reason);
    }

    /** @return array<string,array{int,string,string}> */
    public static function mileageBoundaries(): array
    {
        return [
            'порог − 1' => [-1, DecisionEngine::APPROVE, 'ltv_approve'],
            'ровно порог' => [0, DecisionEngine::REVIEW, 'high_mileage'],
            'порог + 1' => [1, DecisionEngine::REVIEW, 'high_mileage'],
        ];
    }

    #[DataProvider('thresholdAndAbove')]
    public function testKeepsLtvReviewAtHighMileage(int $offsetFromThreshold): void
    {
        $result = $this->engine->decide($this->reviewLtv(), $this->reviewFromKm() + $offsetFromThreshold);

        self::assertSame(DecisionEngine::REVIEW, $result->decision);
        self::assertSame('ltv_review', $result->reason);
    }

    #[DataProvider('thresholdAndAbove')]
    public function testKeepsLtvRejectAtHighMileage(int $offsetFromThreshold): void
    {
        $result = $this->engine->decide($this->rejectLtv(), $this->reviewFromKm() + $offsetFromThreshold);

        self::assertSame(DecisionEngine::REJECT, $result->decision);
        self::assertSame('ltv_reject', $result->reason);
    }

    /** @return array<string,array{int}> */
    public static function thresholdAndAbove(): array
    {
        return [
            'ровно порог' => [0],
            'выше порога' => [1],
        ];
    }

    #[DataProvider('ltvValues')]
    public function testKeepsLtvDecisionBelowMileageThreshold(float $ltv, string $decision, string $reason): void
    {
        $result = $this->engine->decide($ltv, $this->reviewFromKm() - 1);

        self::assertSame($decision, $result->decision);
        self::assertSame($reason, $result->reason);
    }

    /** @return array<string,array{float,string,string}> */
    public static function ltvValues(): array
    {
        return [
            'низкий LTV' => [28.5, DecisionEngine::APPROVE, 'ltv_approve'],
            'середина зелёной зоны' => [45.0, DecisionEngine::APPROVE, 'ltv_approve'],
            'серая зона' => [72.3, DecisionEngine::REVIEW, 'ltv_review'],
            'верхняя граница серой зоны' => [85.0, DecisionEngine::REVIEW, 'ltv_review'],
            'сразу за верхней границей' => [85.01, DecisionEngine::REJECT, 'ltv_reject'],
            'высокий LTV' => [120.0, DecisionEngine::REJECT, 'ltv_reject'],
        ];
    }

    public function testMileageThresholdDoesNotExceedValidationCeiling(): void
    {
        self::assertLessThanOrEqual(
            (int) $this->rules['vehicle']['max_mileage_km'],
            $this->reviewFromKm(),
            'mileage.review_from_km выше vehicle.max_mileage_km — правило понижения недостижимо (см. rules.php)',
        );
    }

    private function reviewFromKm(): int
    {
        if (!isset($this->rules['mileage']['review_from_km'])) {
            self::fail('В rules.php нет mileage.review_from_km — порог не согласован (spec_MILEAGE, ОО-5)');
        }

        return (int) $this->rules['mileage']['review_from_km'];
    }

    private function approveLtv(): float
    {
        return $this->rules['ltv']['approve_max'] / 2;
    }

    private function reviewLtv(): float
    {
        return ($this->rules['ltv']['approve_max'] + $this->rules['ltv']['review_max']) / 2;
    }

    private function rejectLtv(): float
    {
        return $this->rules['ltv']['review_max'] * 2;
    }
}
