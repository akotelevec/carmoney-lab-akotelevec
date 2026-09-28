<?php

declare(strict_types=1);

namespace CarMoneyLab\Tests\Unit;

use CarMoneyLab\Domain\ApplicationValidator;
use CarMoneyLab\Domain\ValidationException;
use CarMoneyLab\Domain\VehicleAge;
use CarMoneyLab\Domain\VinValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApplicationValidatorTest extends TestCase
{
    private ApplicationValidator $validator;

    /** @var array<string,mixed> */
    private array $rules;

    protected function setUp(): void
    {
        $this->rules = require __DIR__ . '/../../backend/config/rules.php';

        $this->validator = new ApplicationValidator(
            $this->rules,
            new VinValidator($this->rules['vin']),
            new VehicleAge((int) date('Y')),
        );
    }

    /** @return array<string,mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'vin' => 'XTA21099998765432',
            'year' => (int) date('Y') - 5,
            'mileage' => 84000,
            'market_value' => 900000,
            'requested_amount' => 450000,
            'term_months' => 24,
        ], $overrides);
    }

    public function testAcceptsValidApplicationAndNormalisesVin(): void
    {
        $result = $this->validator->validate($this->validPayload(['vin' => 'xta21099998765432']));

        self::assertSame('XTA21099998765432', $result['vin']);
        self::assertSame(450000, $result['requested_amount']);
    }

    public function testRejectsYearInTheFuture(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate($this->validPayload(['year' => (int) date('Y') + 1]));
    }

    public function testRejectsAmountBelowMinimum(): void
    {
        try {
            $this->validator->validate($this->validPayload(['requested_amount' => 1000]));
            self::fail('Ожидали ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('requested_amount', $exception->errors());
        }
    }

    public function testCollectsAllErrorsAtOnce(): void
    {
        try {
            $this->validator->validate($this->validPayload([
                'vin' => 'BAD',
                'term_months' => 120,
                'market_value' => 0,
            ]));
            self::fail('Ожидали ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(
                ['vin', 'market_value', 'term_months'],
                array_keys($exception->errors()),
            );
        }
    }

    public function testRejectsApplicationWithoutMileage(): void
    {
        $payload = $this->validPayload();
        unset($payload['mileage']);

        try {
            $this->validator->validate($payload);
            self::fail('Ожидали ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('mileage', $exception->errors());
        }
    }

    #[DataProvider('emptyMileageValues')]
    public function testRejectsEmptyMileage(mixed $mileage): void
    {
        try {
            $this->validator->validate($this->validPayload(['mileage' => $mileage]));
            self::fail('Ожидали ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('mileage', $exception->errors());
        }
    }

    /** @return array<string,array{mixed}> */
    public static function emptyMileageValues(): array
    {
        return [
            'null' => [null],
            'пустая строка' => [''],
        ];
    }

    public function testAcceptsZeroMileage(): void
    {
        $result = $this->validator->validate($this->validPayload(['mileage' => 0]));

        self::assertSame(0, $result['mileage']);
    }

    public function testRejectsMileageAboveValidationCeiling(): void
    {
        $mileage = (int) $this->rules['vehicle']['max_mileage_km'] + 1;

        try {
            $this->validator->validate($this->validPayload(['mileage' => $mileage]));
            self::fail('Ожидали ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('mileage', $exception->errors());
        }
    }
}
