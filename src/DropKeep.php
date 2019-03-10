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

use Bakame\DiceRoller\Exception\TooManyObjects;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;

final class DropKeep implements Rollable
{
    public const DROP_HIGHEST = 'dh';
    public const DROP_LOWEST = 'dl';
    public const KEEP_HIGHEST = 'kh';
    public const KEEP_LOWEST = 'kl';

    private const OPERATOR = [
        self::DROP_HIGHEST => 1,
        self::DROP_LOWEST => 1,
        self::KEEP_HIGHEST => 1,
        self::KEEP_LOWEST => 1,
    ];

    /**
     * The Cup object to decorate.
     *
     * @var Cup
     */
    private $rollable;

    /**
     * The threshold number of rollable object.
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
     * @var Profiler|null
     */
    private $profiler;

    /**
     * new instance.
     *
     * @param  ?Profiler $profiler
     * @throws Exception if the algorithm is not recognized
     * @throws Exception if the Cup is not valid
     */
    public function __construct(Cup $rollable, string $algo, int $threshold, ?Profiler $profiler = null)
    {
        if (count($rollable) < $threshold) {
            throw new TooManyObjects(sprintf('The number of rollable objects `%s` MUST be lesser or equal to the threshold value `%s`', count($rollable), $threshold));
        }

        if (!isset(self::OPERATOR[$algo])) {
            throw new UnknownAlgorithm(sprintf('Unknown or unsupported sortable algorithm `%s`', $algo));
        }

        $this->rollable = $rollable;
        $this->threshold = $threshold;
        $this->algo = $algo;
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.strtoupper($this->algo).$this->threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $res = [];
        foreach ($this->rollable as $rollable) {
            $res[] = $rollable->roll();
        }

        $res = $this->calculate($res);

        $retval = (int) array_sum($res);
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->profile(__METHOD__, $this, $this->setTrace($res), $retval);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $res = [];
        foreach ($this->rollable as $rollable) {
            $res[] = $rollable->getMinimum();
        }

        $res = $this->calculate($res);

        $retval = (int) array_sum($res);
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->profile(__METHOD__, $this, $this->setTrace($res), $retval);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $res = [];
        foreach ($this->rollable as $rollable) {
            $res[] = $rollable->getMaximum();
        }

        $res = $this->calculate($res);

        $retval = (int) array_sum($res);
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->profile(__METHOD__, $this, $this->setTrace($res), $retval);

        return $retval;
    }

    /**
     * Computes the sum to be return.
     */
    private function calculate(array $values): array
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

    /**
     * Format the trace as string.
     */
    private function setTrace(array $traces): string
    {
        $mapper = static function (int $value): string {
            return '('.$value.')';
        };

        $arr = array_map($mapper, $traces);

        return implode(' + ', $arr);
    }
}
