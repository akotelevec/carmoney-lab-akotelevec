<?php

declare(strict_types=1);

namespace CarMoneyLab\Domain;

/**
 * Решение по заявке на основании LTV и пробега, с кодом причины.
 *
 *   LTV <= approve_max              -> approve
 *   approve_max < LTV <= review_max -> review
 *   LTV > review_max                -> reject
 *
 * Высокий пробег (>= mileage.review_from_km, пороги — см. rules.php)
 * понижает только approve -> review с кодом причины high_mileage;
 * review и reject по LTV пробегом не смягчаются.
 */
final class DecisionEngine
{
    public const APPROVE = 'approve';
    public const REVIEW = 'review';
    public const REJECT = 'reject';

    public const REASON_LTV_APPROVE = 'ltv_approve';
    public const REASON_LTV_REVIEW = 'ltv_review';
    public const REASON_LTV_REJECT = 'ltv_reject';
    public const REASON_HIGH_MILEAGE = 'high_mileage';

    private float $approveMax;
    private float $reviewMax;
    private ?int $reviewFromKm;

    /**
     * @param array{approve_max:float,review_max:float} $thresholds
     * @param int|null $reviewFromKm порог высокого пробега (см. rules.php); null — правило не задано
     */
    public function __construct(array $thresholds, ?int $reviewFromKm = null)
    {
        $this->approveMax = $thresholds['approve_max'];
        $this->reviewMax = $thresholds['review_max'];
        $this->reviewFromKm = $reviewFromKm;
    }

    public function decide(float $ltv, int $mileage): Decision
    {
        if ($ltv < $this->approveMax) {
            if ($this->reviewFromKm !== null && $mileage >= $this->reviewFromKm) {
                return new Decision(self::REVIEW, self::REASON_HIGH_MILEAGE);
            }

            return new Decision(self::APPROVE, self::REASON_LTV_APPROVE);
        }

        if ($ltv <= $this->reviewMax) {
            return new Decision(self::REVIEW, self::REASON_LTV_REVIEW);
        }

        return new Decision(self::REJECT, self::REASON_LTV_REJECT);
    }
}
