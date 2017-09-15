<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use OutOfRangeException;

final class SortModifier implements Rollable
{
    const DROP_HIGHEST = 'dh';
    const DROP_LOWEST = 'dl';
    const KEEP_HIGHEST = 'kh';
    const KEEP_LOWEST = 'kl';

    private $methodList = [
        self::DROP_HIGHEST => 'dropHighest',
        self::DROP_LOWEST => 'dropLowest',
        self::KEEP_HIGHEST => 'keepHighest',
        self::KEEP_LOWEST => 'keepLowest',
    ];

    /**
     * The Cup object to decorate
     *
     * @var Cup
     */
    private $rollable;

    /**
     * The threshold number of rollable object
     *
     * @var int
     */
    private $threshold;

    /**
     * The method name associated with a given algo
     *
     * @var string
     */
    private $method;

    /**
     * new instance
     *
     * @param Cup    $pRollable
     * @param int    $pThreshold
     * @param string $pAlgo
     */
    public function __construct(Cup $pRollable, int $pThreshold, string $pAlgo)
    {
        if (count($pRollable) < $pThreshold) {
            throw new OutOfRangeException(sprintf('The number of rollable objects %s must be lesser or equal to the threshold value %s', count($pRollable), $pThreshold));
        }

        if (!isset($this->methodList[$pAlgo])) {
            throw new OutOfRangeException('Unknown or unsupported sortable algorithm');
        }

        $this->rollable = $pRollable;
        $this->threshold = $pThreshold;
        $this->method = $this->methodList[$pAlgo];
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return $this->sum('getMinimum');
    }

    /**
     * Compute the sum to be return
     *
     * @param string $pMethod One of the Rollable method
     *
     * @return int
     */
    private function sum(string $pMethod): int
    {
        $res = [];
        foreach ($this->rollable as $rollable) {
            $res[] = $rollable->$pMethod();
        }

        return $this->{$this->method}($res);
    }

    /**
     * Returns the drop highest value
     *
     * @param int[] $pSum
     *
     * @return int
     */
    private function dropHighest(array $pSum): int
    {
        rsort($pSum);

        return array_sum(array_slice($pSum, $this->threshold));
    }

    /**
     * Returns the drop lowest value
     *
     * @param int[] $pSum
     *
     * @return int
     */
    private function dropLowest(array $pSum): int
    {
        sort($pSum);

        return array_sum(array_slice($pSum, $this->threshold));
    }

    /**
     * Returns the keep highest value
     *
     * @param int[] $pSum
     *
     * @return int
     */
    private function keepHighest(array $pSum): int
    {
        rsort($pSum);

        return array_sum(array_slice($pSum, 0, $this->threshold));
    }

    /**
     * Returns the keep lowest value
     *
     * @param int[] $pSum
     *
     * @return int
     */
    private function keepLowest(array $pSum): int
    {
        sort($pSum);

        return array_sum(array_slice($pSum, 0, $this->threshold));
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return $this->sum('getMaximum');
    }

    /**
     * @inheritdoc
     */
    public function roll(): int
    {
        return $this->sum('roll');
    }
}
