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
     * @covers ::getSize
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::toString
     * @covers ::roll
     */
    public function testFudgeDice(): void
    {
        $dice = new FudgeDie();
        self::assertSame('DF', $dice->toString());
        self::assertSame(3, $dice->getSize());
        self::assertSame(1, $dice->getMaximum());
        self::assertSame(-1, $dice->getMinimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->getMinimum(), $test);
            self::assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }
}
