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
        self::DROP_HIGHEST => 'dropHighest',
        self::DROP_LOWEST => 'dropLowest',
        self::KEEP_HIGHEST => 'keepHighest',
        self::KEEP_LOWEST => 'keepLowest',
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
     * new instance.
     *
     * @throws Exception if the algorithm is not recognized
     * @throws Exception if the Cup is not valid
     */
    public function __construct(Cup $rollable, string $algo, int $threshold)
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

        return $this->calculate($res);
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

        return $this->calculate($res);
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

        return $this->calculate($res);
    }

    /**
     * Computes the sum to be return.
     */
    private function calculate(array $values): int
    {
        if (self::DROP_HIGHEST === $this->algo) {
            return (int) array_sum($this->dropHighest($values));
        }
        
        if (self::DROP_LOWEST === $this->algo) {
            return (int) array_sum($this->dropLowest($values));
        }
        
        if (self::KEEP_HIGHEST === $this->algo) {
            return (int) array_sum($this->keepHighest($values));
        }

        return (int) array_sum($this->keepLowest($values));
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
