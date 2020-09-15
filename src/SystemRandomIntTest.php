<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Exception\IntGeneratorError;
use PHPUnit\Framework\TestCase;

final class SystemRandomIntTest extends TestCase
{
    private SystemRandomInt  $generator;

    public function setUp(): void
    {
        $this->generator = new SystemRandomInt();
    }

    public function testItReturnsANumberBetweenMinimumAndMaxValue(): void
    {
        $minimum = 4;
        $maximum = 10;

        $result = $this->generator->generateInt($minimum, $maximum);

        self::assertTrue($result >= $minimum);
        self::assertTrue($result <= $maximum);
    }

    public function testItThrowsIfMaximumIsLesserThanMinimum(): void
    {
        self::expectException(IntGeneratorError::class);

        $this->generator->generateInt(5, 3);
    }
}
