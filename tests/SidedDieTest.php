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

use Bakame\DiceRoller\Exception\CanNotBeRolled;
use Bakame\DiceRoller\SidedDie;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\SidedDie
 */
final class SidedDieTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::fromString
     * @covers ::toString
     * @covers ::getSize
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     */
    public function testSixSidedValues(): void
    {
        $expected = 6;
        $dice = new SidedDie($expected);
        self::assertSame($expected, $dice->getSize());
        self::assertSame('D6', $dice->toString());
        self::assertEquals($dice, SidedDie::fromString($dice->toString()));
        self::assertSame($expected, $dice->getMaximum());
        self::assertSame(1, $dice->getMinimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->getMinimum(), $test);
            self::assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWithWrongValue(): void
    {
        self::expectException(CanNotBeRolled::class);
        new SidedDie(1);
    }

    /**
     * @covers ::fromString
     */
    public function testfromStringWithWrongValue(): void
    {
        self::expectException(CanNotBeRolled::class);
        SidedDie::fromString('1');
    }
}