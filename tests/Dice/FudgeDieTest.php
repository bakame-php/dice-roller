<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Dice;

use Bakame\DiceRoller\Dice\FudgeDie;
use PHPUnit\Framework\TestCase;
use function json_encode;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Dice\FudgeDie
 */
final class FudgeDieTest extends TestCase
{
    public function testFudgeDice(): void
    {
        $dice = new FudgeDie();
        self::assertSame('DF', $dice->notation());
        self::assertSame(3, $dice->size());
        self::assertSame(1, $dice->maximum());
        self::assertSame(-1, $dice->minimum());
        self::assertSame(json_encode('DF'), json_encode($dice));
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll()->value();
            self::assertGreaterThanOrEqual($dice->minimum(), $test);
            self::assertLessThanOrEqual($dice->maximum(), $test);
        }
    }
}
