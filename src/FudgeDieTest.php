<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use function json_encode;

/**
 * @coversDefaultClass \Bakame\DiceRoller\FudgeDie
 */
final class FudgeDieTest extends TestCase
{
    public function testFudgeDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 0;
            }
        };
        $dice = new FudgeDie($randomIntProvider);
        self::assertSame('DF', $dice->notation());
        self::assertSame(3, $dice->size());
        self::assertSame(1, $dice->maximum());
        self::assertSame(-1, $dice->minimum());
        self::assertSame(json_encode('DF'), json_encode($dice));

        $test = $dice->roll()->value();
        self::assertSame(0, $test);
        self::assertGreaterThanOrEqual($dice->minimum(), $test);
        self::assertLessThanOrEqual($dice->maximum(), $test);
    }

    /**
     * @covers ::setTracer
     * @covers ::getTracer
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $rollable = new FudgeDie();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);

        $rollable->roll();
        $rollable->setTracer($tracer);
        $rollable->roll();
        $rollable->maximum();
        $rollable->minimum();

        self::assertSame($tracer, $rollable->getTracer());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }
}
