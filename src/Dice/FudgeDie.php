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

namespace Bakame\DiceRoller\Dice;

use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function random_int;

final class FudgeDie implements Dice, SupportsTracing
{
    private Tracer $tracer;

    public function __construct(?Tracer $tracer = null)
    {
        $this->setTracer($tracer ?? new NullTracer());
    }

    /**
     * {@inheritDoc}
     */
    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    /**
     * {@inheritDoc}
     */
    public function notation(): string
    {
        return 'DF';
    }

    /**
     * Returns the side count.
     *
     */
    public function size(): int
    {
        return 3;
    }

    /**
     * {@inheritDoc}
     */
    public function minimum(): int
    {
        $roll = new Toss(-1, '-1', new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritDoc}
     */
    public function maximum(): int
    {
        $roll = new Toss(1, '1', new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritDoc}
     */
    public function roll(): Roll
    {
        $result = random_int(-1, 1);
        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll;
    }
}
