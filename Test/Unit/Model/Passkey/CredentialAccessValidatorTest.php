<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Passkey;

use FalconMedia\AdminPasskey\Model\Passkey\CredentialAccessValidator;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the self-service credential ownership check.
 */
class CredentialAccessValidatorTest extends TestCase
{
    private CredentialAccessValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CredentialAccessValidator();
    }

    /**
     * @dataProvider ownershipProvider
     */
    public function testIsOwnedByAdmin(?int $ownerId, int $currentId, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isOwnedByAdmin($ownerId, $currentId));
    }

    /**
     * @return array<string, array{0: int|null, 1: int, 2: bool}>
     */
    public static function ownershipProvider(): array
    {
        return [
            'same owner is allowed' => [7, 7, true],
            'different owner is denied' => [7, 8, false],
            'null owner is denied' => [null, 7, false],
            'zero current admin is denied' => [7, 0, false],
            'negative current admin is denied' => [7, -1, false],
            'null owner and zero current is denied' => [null, 0, false],
        ];
    }
}
