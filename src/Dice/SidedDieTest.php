<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Dice;

use Bakame\DiceRoller\Contract\RandomIntGenerator;
use Bakame\DiceRoller\Exception\SyntaxError;
use PHPUnit\Framework\TestCase;
use function json_encode;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Dice\SidedDie
 */
final class SidedDieTest extends TestCase
{
    public function testSixSidedValues(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 3;
            }
        };

        $expected = 6;
        $dice = new SidedDie($expected, $randomIntProvider);
        self::assertSame($expected, $dice->size());
        self::assertSame('D6', $dice->notation());
        self::assertEquals($dice, SidedDie::fromNotation($dice->notation(), $randomIntProvider));
        self::assertSame($expected, $dice->maximum());
        self::assertSame(1, $dice->minimum());
        self::assertSame(json_encode('D6'), json_encode($dice));

        $test = $dice->roll()->value();
        self::assertSame(3, $test);
        self::assertGreaterThanOrEqual($dice->minimum(), $test);
        self::assertLessThanOrEqual($dice->maximum(), $test);
    }

    /**
     * @covers ::__construct
     * @covers \Bakame\DiceRoller\Exception\SyntaxError
     */
    public function testConstructorWithWrongValue(): void
    {
        self::expectException(SyntaxError::class);
        new SidedDie(1);
    }

    /**
     * @covers ::fromNotation
     * @covers \Bakame\DiceRoller\Exception\SyntaxError
     */
    public function testFromStringWithWrongValue(): void
    {
        self::expectException(SyntaxError::class);
        SidedDie::fromNotation('1');
    }
}
