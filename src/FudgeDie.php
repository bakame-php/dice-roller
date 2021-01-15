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

final class FudgeDie implements Dice, \JsonSerializable, SupportsTracing
{
    private RandomIntGenerator $randomIntGenerator;

    private Tracer $tracer;

    public function __construct(RandomIntGenerator $randomIntGenerator = null, Tracer $tracer = null)
    {
        $this->randomIntGenerator = $randomIntGenerator ?? new SystemRandomInt();
        $this->tracer = $tracer ?? new NullTracer();
    }

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    public function notation(): string
    {
        return 'DF';
    }

    public function size(): int
    {
        return 3;
    }

    public function minimum(): int
    {
        $roll = new Toss(-1, '-1', TossContext::fromRolling($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function maximum(): int
    {
        $roll = new Toss(1, '1', TossContext::fromRolling($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    public function roll(): Roll
    {
        $result = $this->randomIntGenerator->generateInt(-1, 1);
        $roll = new Toss($result, (string) $result, TossContext::fromRolling($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll;
    }
}
