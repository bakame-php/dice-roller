<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Type;

use Bakame\DiceRoller\Test\Bakame;
use Bakame\DiceRoller\Type\PercentileDice;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\Type\PercentileDice
 */
final class PercentileDiceTest extends TestCase
{
    /**
     * @covers ::count
     * @covers ::__toString
     * @covers ::toString
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     */
    public function testFudgeDice(): void
    {
        $dice = new PercentileDice();
        self::assertCount(100, $dice);
        self::assertSame(100, $dice->getMaximum());
        self::assertSame(1, $dice->getMinimum());
        self::assertSame('D%', (string) $dice);
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->getMinimum(), $test);
            self::assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }
}
