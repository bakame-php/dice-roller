<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\PercentileDie;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\PercentileDie
 */
final class PercentileDieTest extends TestCase
{
    /**
     * @covers ::size
     * @covers ::toString
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     */
    public function testFudgeDice(): void
    {
        $dice = new PercentileDie();
        self::assertSame(100, $dice->size());
        self::assertSame(100, $dice->maximum());
        self::assertSame(1, $dice->minimum());
        self::assertSame('D%', $dice->toString());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->minimum(), $test);
            self::assertLessThanOrEqual($dice->maximum(), $test);
        }
    }
}
