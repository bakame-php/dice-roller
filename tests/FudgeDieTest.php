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

use Bakame\DiceRoller\FudgeDie;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\FudgeDie
 */
final class FudgeDieTest extends TestCase
{
    /**
     * @covers ::size
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::toString
     * @covers ::roll
     */
    public function testFudgeDice(): void
    {
        $dice = new FudgeDie();
        self::assertSame('DF', $dice->toString());
        self::assertSame(3, $dice->size());
        self::assertSame(1, $dice->maximum());
        self::assertSame(-1, $dice->minimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->minimum(), $test);
            self::assertLessThanOrEqual($dice->maximum(), $test);
        }
    }
}
