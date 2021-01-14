<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Tracer\MemoryTracer;
use Bakame\DiceRoller\Tracer\Tracer;
use PHPUnit\Framework\TestCase;
use function get_class;

final class TossContextTest extends TestCase
{
    private Tracer $tracer;

    public function setUp(): void
    {
        parent::setUp();
        $this->tracer = new MemoryTracer();
    }

    public function testItCanBeInstantiated(): void
    {
        $cup = Cup::of(new SidedDie(6), 3);
        $source = get_class($cup).'::roll';

        $context = new TossContext($cup, $source);
        self::assertSame($source, $context->source());
        self::assertSame($cup->notation(), $context->notation());
        self::assertEmpty($context->extensions());
        $expectedContext = ['source' => $source, 'notation' => $cup->notation()];

        self::assertSame($expectedContext, $context->asArray());
    }

    public function testTraceCanHaveOptionalsValue(): void
    {
        $cup = Cup::of(new SidedDie(6), 3);
        $source = get_class($cup).'::roll';

        $context = new TossContext($cup, $source, ['bar' => 'baz', 'result' => 23]);
        $arrExpected = ['source' => $source, 'notation' => $cup->notation(), 'bar' => 'baz', 'result' => 23];
        self::assertArrayHasKey('bar', $context->asArray());
        self::assertArrayHasKey('result', $context->asArray());
        self::assertSame($arrExpected, $context->asArray());
        self::assertSame($arrExpected, $context->jsonSerialize());
    }


    public function testContextWillFilterOutRollKeysFromOptionalValues(): void
    {
        $cup = Cup::of(new SidedDie(6), 3);
        $source = get_class($cup).'::roll';

        $context = new TossContext($cup, $source, ['bar' => 'baz', 'value' => 23, 'operation' => 'swordfish']);
        $arrExpected = ['source' => $source, 'notation' => $cup->notation(), 'bar' => 'baz'];
        self::assertArrayHasKey('bar', $context->asArray());
        self::assertArrayNotHasKey('value', $context->asArray());
        self::assertArrayNotHasKey('operation', $context->asArray());
        self::assertSame($arrExpected, $context->asArray());
        self::assertSame($arrExpected, $context->jsonSerialize());
    }
}
