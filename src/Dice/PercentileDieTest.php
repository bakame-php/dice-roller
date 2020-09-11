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
use Bakame\DiceRoller\Tracer\MemoryTracer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Dice\PercentileDie
 */
final class PercentileDieTest extends TestCase
{
    public function testFudgeDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 42;
            }
        };

        $dice = new PercentileDie($randomIntProvider);
        $dice->setTracer(new MemoryTracer());
        self::assertSame(100, $dice->size());
        self::assertSame(100, $dice->maximum());
        self::assertSame(1, $dice->minimum());
        self::assertSame('D%', $dice->notation());
        self::assertSame(json_encode('D%'), json_encode($dice));

        $test = $dice->roll()->value();
        self::assertSame(42, $test);
        self::assertGreaterThanOrEqual($dice->minimum(), $test);
        self::assertLessThanOrEqual($dice->maximum(), $test);
    }
}
