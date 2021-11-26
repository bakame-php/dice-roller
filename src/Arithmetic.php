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

use JsonSerializable;
use function abs;

final class Arithmetic implements JsonSerializable, Modifier, EnablesDeepTracing
{
    private const ADD = '+';
    private const DIV = '/';
    private const MUL = '*';
    private const SUB = '-';
    private const POW = '^';

    private int $value;
    private Tracer $tracer;

    /**
     * @param  Rollable    $rollable
     * @param  string      $operator
     * @throws SyntaxError if the operator is not recognized
     * @throws SyntaxError if the value is invalid for a given operator
     */
    private function __construct(
        private Rollable $rollable,
        private string $operator,
        int $value,
        Tracer $tracer = null
    ) {
        if (0 > $value) {
            throw SyntaxError::dueToOperatorAndValueMismatched($this->operator, $value);
        }

        $this->value = $value;
        $this->setTracer($tracer ?? new NullTracer());
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
        if (0 === $value) {
            throw SyntaxError::dueToOperatorAndValueMismatched(self::DIV, $value);
        }

        return new self($rollable, self::DIV, $value, $tracer);
    }

    public static function pow(Rollable $rollable, int $value, Tracer $tracer = null): self
    {
        return new self($rollable, self::POW, $value, $tracer);
    }

    public static function fromOperation(Rollable $rollable, string $operator, int $value, Tracer $tracer = null): self
    {
        return match (true) {
            $operator === self::POW => self::pow($rollable, $value, $tracer),
            $operator === self::DIV => self::div($rollable, $value, $tracer),
            $operator === self::ADD => self::add($rollable, $value, $tracer),
            $operator === self::SUB => self::sub($rollable, $value, $tracer),
            $operator === self::MUL => self::mul($rollable, $value, $tracer),
            default => throw SyntaxError::dueToInvalidOperator($operator),
        };
    }

    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function setTracerRecursively(Tracer $tracer): void
    {
        $this->setTracer($tracer);
        if ($this->rollable instanceof EnablesDeepTracing) {
            $this->rollable->setTracerRecursively($tracer);
        } elseif ($this->rollable instanceof SupportsTracing) {
            $this->rollable->setTracer($tracer);
        }
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
        if (str_contains($str, self::ADD)) {
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
            TossContext::fromRolling($this, $method)
        );

        $this->tracer->append($roll);

        return $roll;
    }

    private function calculate(int $value): int
    {
        return match (true) {
            self::ADD === $this->operator => $value + $this->value,
            self::SUB === $this->operator => $value - $this->value,
            self::MUL === $this->operator => $value * $this->value,
            self::DIV === $this->operator => intdiv($value, $this->value),
            $value > -1 => $value ** $this->value,
            default => (int) (abs($value) ** $this->value) * -1
        };
    }
}
