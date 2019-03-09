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

use Bakame\DiceRoller\FudgeDice;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\FudgeDice
 */
final class FudgeDiceTest extends TestCase
{
    /**
     * @covers ::count
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::toString
     * @covers ::__toString
     * @covers ::roll
     */
    public function testFudgeDice(): void
    {
        $dice = new FudgeDice();
        self::assertSame('DF', (string) $dice);
        self::assertCount(3, $dice);
        self::assertSame(1, $dice->getMaximum());
        self::assertSame(-1, $dice->getMinimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->getMinimum(), $test);
            self::assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }
}
