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

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Tracer\MemoryTracer;
use PHPUnit\Framework\TestCase;
use function get_class;

class ContextTest extends TestCase
{
    /**
     * @var Tracer
     */
    private $tracer;

    public function setUp(): void
    {
        parent::setUp();
        $this->tracer = new MemoryTracer();
    }

    public function testItCanBeInstantiated(): void
    {
        $cup = Cup::fromRollable(new SidedDie(6), 3);
        $source = get_class($cup).'::roll';

        $context = new TossContext($cup, $source);
        self::assertSame($source, $context->source());
        self::assertSame($cup, $context->rollable());
        self::assertEmpty($context->extensions());
        $expectedContext = ['source' => $source, 'notation' => $cup->notation()];

        self::assertSame($expectedContext, $context->asArray());
    }

    public function testTraceCanHaveOptionalsValue(): void
    {
        $cup = Cup::fromRollable(new SidedDie(6), 3);
        $source = get_class($cup).'::roll';

        $context = new TossContext($cup, $source, ['bar' => 'baz', 'result' => 23]);
        $arrExpected = ['source' => $source, 'notation' => $cup->notation(), 'bar' => 'baz'];
        self::assertArrayHasKey('bar', $context->asArray());
        self::assertArrayNotHasKey('result', $context->asArray());
        self::assertSame($arrExpected, $context->asArray());
        self::assertSame($arrExpected, $context->jsonSerialize());
    }
}
