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
use Bakame\DiceRoller\SyntaxError;
use Bakame\DiceRoller\Tracer\Psr3Logger;
use Bakame\DiceRoller\Tracer\Psr3LogTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Dice\CustomDie
 */
final class CustomDieTest extends TestCase
{
    public function testDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 3;
            }
        };
        $dice = CustomDie::fromNotation('d[1,2,2,4,4]', $randomIntProvider);
        self::assertSame(5, $dice->size());
        self::assertSame(4, $dice->maximum());
        self::assertSame(1, $dice->minimum());
        self::assertSame('D[1,2,2,4,4]', $dice->notation());
        self::assertSame(json_encode('D[1,2,2,4,4]'), json_encode($dice));
        self::assertEquals($dice, CustomDie::fromNotation($dice->notation(), $randomIntProvider));

        $test = $dice->roll()->value();
        self::assertSame(4, $test);
        self::assertGreaterThanOrEqual($dice->minimum(), $test);
        self::assertLessThanOrEqual($dice->maximum(), $test);
    }

    /**
     * @covers ::__construct
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testConstructorWithWrongValue(): void
    {
        self::expectException(SyntaxError::class);
        CustomDie::fromNotation('d[1]');
    }

    /**
     * @dataProvider invalidNotation
     * @covers ::fromNotation
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testFromStringWithWrongValue(string $notation): void
    {
        self::expectException(SyntaxError::class);
        CustomDie::fromNotation($notation);
    }

    public function invalidNotation(): iterable
    {
        return [
            'invalid format' => ['1'],
            'contains non numeric' => ['d[1,0,foobar]'],
            'contains empty side' => ['d[1,,1]'],
        ];
    }

    /**
     * @covers ::setTracer
     * @covers ::getTracer
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $rollable = CustomDie::fromNotation('d[-1, -1, -2]');

        $rollable->setTracer($tracer);
        $rollable->roll();
        $rollable->maximum();
        $rollable->minimum();

        self::assertSame($tracer, $rollable->getTracer());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }
}
