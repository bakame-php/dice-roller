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

use Bakame\DiceRoller\Contract\AcceptsTracer;
use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\TooFewSides;
use Bakame\DiceRoller\Exception\UnknownNotation;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function preg_match;
use function random_int;
use function sprintf;

final class SidedDie implements Dice, AcceptsTracer
{
    private const REGEXP_NOTATION = '/^d(?<sides>\d+)$/i';

    /**
     * @var int
     */
    private $sides;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * new instance.
     *
     * @param int $sides side count
     *
     * @throws TooFewSides if a SimpleDice contains less than 2 sides
     */
    public function __construct(int $sides)
    {
        if (2 > $sides) {
            throw new TooFewSides(sprintf('Your dice must have at least 2 sides, `%s` given.', $sides));
        }

        $this->sides = $sides;
        $this->setTracer(new NullTracer());
    }

    /**
     * New instance from a string expression.
     *
     * @throws UnknownNotation if the expression is not valid.
     */
    public static function fromNotation(string $notation): self
    {
        if (1 === preg_match(self::REGEXP_NOTATION, $notation, $matches)) {
            return new self((int) $matches['sides']);
        }

        throw new UnknownNotation(sprintf('the submitted dice format `%s` is invalid ', $notation));
    }

    /**
     * {@inheritdoc}
     */
    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    /**
     * {@inheritdoc}
     */
    public function notation(): string
    {
        return 'D'.$this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function minimum(): int
    {
        $roll = new Toss(1, '1', new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritdoc}
     */
    public function maximum(): int
    {
        $roll = new Toss($this->sides, (string) $this->sides, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        $result = random_int(1, $this->sides);

        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll;
    }
}
