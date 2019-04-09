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
use Bakame\DiceRoller\Contract\Profiler;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Exception\TooManyObjects;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\LogProfiler;
use function array_map;
use function array_slice;
use function array_sum;
use function count;
use function implode;
use function iterator_to_array;
use function rsort;
use function sprintf;
use function strpos;
use function strtoupper;
use function uasort;

final class DropKeep implements Modifier, Traceable
{
    public const DROP_HIGHEST = 'DH';
    public const DROP_LOWEST = 'DL';
    public const KEEP_HIGHEST = 'KH';
    public const KEEP_LOWEST = 'KL';

    private const OPERATOR = [
        self::DROP_HIGHEST => 1,
        self::DROP_LOWEST => 1,
        self::KEEP_HIGHEST => 1,
        self::KEEP_LOWEST => 1,
    ];

    /**
     * The RollableCollection to decorate.
     *
     * @var Pool
     */
    private $pool;

    /**
     * The threshold number of Rollable object.
     *
     * @var int
     */
    private $threshold;

    /**
     * The given algo.
     *
     * @var string
     */
    private $algo;

    /**
     * @var string
     */
    private $trace = '';

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var bool
     */
    private $is_rollable_wrapped = false;

    /**
     * new instance.
     *
     *
     * @throws UnknownAlgorithm if the algorithm is not recognized
     * @throws TooManyObjects   if the RollableCollection is not valid
     */
    public function __construct(Rollable $pool, string $algo, int $threshold)
    {
        if (!$pool instanceof Pool) {
            $this->is_rollable_wrapped = true;
            $pool = new Cup($pool);
        }

        if (count($pool) < $threshold) {
            throw new TooManyObjects(sprintf('The number of rollable objects `%s` MUST be lesser or equal to the threshold value `%s`', count($pool), $threshold));
        }

        $algo = strtoupper($algo);
        if (!isset(self::OPERATOR[$algo])) {
            throw new UnknownAlgorithm(sprintf('Unknown or unsupported sortable algorithm `%s`', $algo));
        }

        $this->pool = $pool;
        $this->threshold = $threshold;
        $this->algo = $algo;
        $this->setProfiler(LogProfiler::fromNullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfiler(Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfiler(): Profiler
    {
        return $this->profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerRollable(): Rollable
    {
        if (!$this->is_rollable_wrapped) {
            return $this->pool;
        }
        
        $arr = iterator_to_array($this->pool, false);

        return $arr[0];
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        $str = $this->pool->toString();
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->algo.$this->threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $innerRetval = [];
        foreach ($this->pool as $rollable) {
            $innerRetval[] = $rollable->roll();
        }

        return $this->decorate($innerRetval, __METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $innerRetval = [];
        foreach ($this->pool as $rollable) {
            $innerRetval[] = $rollable->getMinimum();
        }

        return $this->decorate($innerRetval, __METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $innerRetval = [];
        foreach ($this->pool as $rollable) {
            $innerRetval[] = $rollable->getMaximum();
        }

        return $this->decorate($innerRetval, __METHOD__);
    }

    /**
     * Decorates the operation returned value.
     */
    private function decorate(array $values, string $method): int
    {
        $values = $this->filter($values);
        $retval = (int) array_sum($values);

        $mapper = function (int $value) {
            if (0 > $value) {
                return '('.$value.')';
            }

            return $value;
        };

        $this->trace = implode(' + ', array_map($mapper, $values));
        $this->profiler->addTrace($this, $method, $retval, $this->trace);

        return $retval;
    }

    /**
     * Computes the sum to be return.
     */
    private function filter(array $values): array
    {
        if (self::DROP_HIGHEST === $this->algo) {
            return $this->dropHighest($values);
        }
        
        if (self::DROP_LOWEST === $this->algo) {
            return $this->dropLowest($values);
        }
        
        if (self::KEEP_HIGHEST === $this->algo) {
            return $this->keepHighest($values);
        }

        return $this->keepLowest($values);
    }

    /**
     * Returns the drop highest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function dropHighest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);

        return array_slice($sum, $this->threshold);
    }

    /**
     *  Value comparison internal method.
     */
    private function drop(int $data1, int $data2): int
    {
        return $data1 <=> $data2;
    }

    /**
     * Returns the drop lowest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function dropLowest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);

        return array_slice($sum, $this->threshold);
    }

    /**
     * Returns the keep highest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function keepHighest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);
        rsort($sum);

        return array_slice($sum, 0, $this->threshold);
    }

    /**
     * Returns the keep lowest value.
     *
     * @param int[] $sum
     *
     * @return int[]
     */
    private function keepLowest(array $sum): array
    {
        uasort($sum, [$this, 'drop']);
        rsort($sum);

        return array_slice($sum, 0, $this->threshold);
    }
}
