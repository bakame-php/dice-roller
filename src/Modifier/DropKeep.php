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
use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function array_map;
use function array_slice;
use function array_sum;
use function count;
use function implode;
use function iterator_to_array;
use function rsort;
use function strpos;
use function uasort;

final class DropKeep implements Modifier, SupportsTracing
{
    private const DROP_HIGHEST = 'DH';
    private const DROP_LOWEST = 'DL';
    private const KEEP_HIGHEST = 'KH';
    private const KEEP_LOWEST = 'KL';

    private Pool $pool;

    /**
     * The threshold number of Rollable object.
     */
    private int $threshold;

    private string $algorithm;

    private Tracer $tracer;

    private bool $is_rollable_wrapped = false;

    private function __construct(Rollable $pool, string $algorithm, int $threshold, Tracer $tracer = null)
    {
        if (!$pool instanceof Pool) {
            $this->is_rollable_wrapped = true;
            $pool = new Cup($pool);
        }

        if (count($pool) < $threshold) {
            throw SyntaxError::dueToTooManyRollableInstances($pool, $threshold);
        }

        $this->pool = $pool;
        $this->algorithm = $algorithm;
        $this->threshold = $threshold;
        $this->setTracer($tracer ?? new NullTracer());
    }

    public static function dropLowest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::DROP_LOWEST, $threshold, $tracer);
    }

    public static function dropHighest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::DROP_HIGHEST, $threshold, $tracer);
    }

    public static function keepLowest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::KEEP_LOWEST, $threshold, $tracer);
    }

    public static function keepHighest(Rollable $pool, int $threshold, Tracer $tracer = null): self
    {
        return new self($pool, self::KEEP_HIGHEST, $threshold, $tracer);
    }

    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    public function getInnerRollable(): Rollable
    {
        if (!$this->is_rollable_wrapped) {
            return $this->pool;
        }

        return iterator_to_array($this->pool, false)[0];
    }

    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    public function notation(): string
    {
        $str = $this->pool->notation();
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->algorithm.$this->threshold;
    }

    public function roll(): Roll
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values[] = $rollable->roll()->value();
        }

        return $this->decorate($values, __METHOD__);
    }

    public function minimum(): int
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values[] = $rollable->minimum();
        }

        return $this->decorate($values, __METHOD__)->value();
    }

    public function maximum(): int
    {
        $values = [];
        foreach ($this->pool as $rollable) {
            $values[] = $rollable->maximum();
        }

        return $this->decorate($values, __METHOD__)->value();
    }

    /**
     * Decorates the operation returned value.
     */
    private function decorate(array $values, string $method): Roll
    {
        $result = $this->slice($values);
        $operation = implode(' + ', array_map(fn ($value) => (0 > $value) ? '('.$value.')' : $value, $result));
        $roll = new Toss((int) array_sum($result), $operation, new TossContext($this, $method));

        $this->tracer->append($roll);

        return $roll;
    }

    private function slice(array $values): array
    {
        uasort($values, static fn (int $data1, int $data2): int => $data1 <=> $data2);

        if (self::DROP_HIGHEST === $this->algorithm) {
            return array_slice($values, 0, $this->threshold);
        }

        if (self::DROP_LOWEST === $this->algorithm) {
            return array_slice($values, $this->threshold);
        }

        rsort($values);

        if (self::KEEP_HIGHEST === $this->algorithm) {
            return array_slice($values, 0, $this->threshold);
        }

        return array_slice($values, $this->threshold);
    }
}
