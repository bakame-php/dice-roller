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
     * @covers ::getSize
     * @covers ::toString
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     */
    public function testFudgeDice(): void
    {
        $dice = new PercentileDie();
        self::assertSame(100, $dice->getSize());
        self::assertSame(100, $dice->getMaximum());
        self::assertSame(1, $dice->getMinimum());
        self::assertSame('D%', $dice->toString());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->getMinimum(), $test);
            self::assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }
}
