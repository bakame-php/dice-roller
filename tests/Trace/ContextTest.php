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

namespace Bakame\DiceRoller\Test\Trace;

use Bakame\DiceRoller\Trace\Context;
use Bakame\DiceRoller\Trace\LogTracer;
use Bakame\DiceRoller\Trace\MemoryLogger;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    /**
     * @var LogTracer
     */
    private $tracer;

    public function setUp(): void
    {
        parent::setUp();
        $this->tracer = new LogTracer(new MemoryLogger());
    }

    public function testItCanBeInstantiated(): void
    {
        $context = new Context('foo');
        self::assertSame('foo', $context->source());
        self::assertEmpty($context->extensions());
        $expectedContext = ['source' => 'foo'];

        self::assertSame($expectedContext, $context->toArray());
    }

    public function testTraceCanHaveOptionalsValue(): void
    {
        $context = new Context('foo', ['bar' => 'baz', 'result' => 23]);
        self::assertArrayHasKey('bar', $context->toArray());
        self::assertArrayNotHasKey('result', $context->toArray());
        self::assertSame('baz', $context->toArray()['bar']);
    }
}
