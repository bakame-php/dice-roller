<?php

/**
 * This file is part of the League.csv library
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/dice-roller/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\PercentileDice;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\PercentileDice
 */
final class PercentileDiceTest extends TestCase
{
    /**
     * @covers ::count
     * @covers ::__toString
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
