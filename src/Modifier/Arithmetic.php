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

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function abs;
use function in_array;
use function strpos;

final class Arithmetic implements Modifier, SupportsTracing
{
    private const ADD = '+';
    private const DIV = '/';
    private const MUL = '*';
    private const SUB = '-';
    private const POW = '^';
    private const ALGORITHM_LIST = [
        self::ADD,
        self::SUB,
        self::DIV,
        self::MUL,
        self::POW,
    ];

    private Rollable $rollable;

    private string $operator;

    private int $value;

    private Tracer $tracer;

    /**
     * @throws SyntaxError if the operator is not recognized
     * @throws SyntaxError if the value is invalid for a given operator
     */
    private function __construct(Rollable $rollable, string $operator, int $value, Tracer $tracer = null)
    {
        $this->isValidOperation($operator, $value);

        $this->rollable = $rollable;
        $this->operator = $operator;
        $this->value = $value;

        $this->setTracer($tracer ?? new NullTracer());
    }

    /**
     * @throws SyntaxError if the operation is invalid
     */
    private function isValidOperation(string $operator, int $value): void
    {
        if (!in_array($operator, self::ALGORITHM_LIST, true)) {
            throw SyntaxError::dueToInvalidOperator($operator);
        }

        if (0 > $value || (0 === $value && $operator === self::DIV)) {
            throw SyntaxError::dueToOperatorAndValueMismatched($operator, $value);
        }
    }

    public static function add(Rollable $rollable, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, self::ADD, $value, $tracer);
    }

    public static function sub(Rollable $rollable, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, self::SUB, $value, $tracer);
    }

    public static function mul(Rollable $rollable, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, self::MUL, $value, $tracer);
    }

    public static function div(Rollable $rollable, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, self::DIV, $value, $tracer);
    }

    public static function pow(Rollable $rollable, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, self::POW, $value, $tracer);
    }

    public static function fromOperation(Rollable $rollable, string $operator, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, $operator, $value, $tracer);
    }

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    public function getInnerRollable(): Rollable
    {
        return $this->rollable;
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    public function notation(): string
    {
        $str = $this->rollable->notation();
        if (false !== strpos($str, self::ADD)) {
            $str = '('.$str.')';
        }

        return $str.$this->operator.$this->value;
    }

    public function roll(): Roll
    {
        return $this->decorate($this->rollable->roll()->value(), __METHOD__);
    }

    public function minimum(): int
    {
        return $this->decorate($this->rollable->minimum(), __METHOD__)->value();
    }

    public function maximum(): int
    {
        return $this->decorate($this->rollable->maximum(), __METHOD__)->value();
    }

    /**
     * Decorates the operation returned value.
     */
    private function decorate(int $value, string $method): Roll
    {
        $roll = new Toss(
            $this->calculate($value),
            $value.' '.$this->operator.' '.$this->value,
            new TossContext($this, $method)
        );

        $this->tracer->append($roll);

        return $roll;
    }

    private function calculate(int $value): int
    {
        if (self::ADD === $this->operator) {
            return $value + $this->value;
        }

        if (self::SUB === $this->operator) {
            return $value - $this->value;
        }

        if (self::MUL === $this->operator) {
            return $value * $this->value;
        }

        if (self::DIV === $this->operator) {
            return intdiv($value, $this->value);
        }

        if ($value > -1) {
            return $value ** $this->value;
        }

        return (int) (abs($value) ** $this->value) * -1;
    }
}
