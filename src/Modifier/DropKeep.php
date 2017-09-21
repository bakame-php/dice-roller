<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller\Modifier;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Rollable;

final class DropKeep implements Rollable
{
    const DROP_HIGHEST = 'dh';
    const DROP_LOWEST = 'dl';
    const KEEP_HIGHEST = 'kh';
    const KEEP_LOWEST = 'kl';

    private static $methodList = [
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
     * @param string $pAlgo
     * @param int    $pThreshold
     */
    public function __construct(Cup $pRollable, string $pAlgo, int $pThreshold)
    {
        if (count($pRollable) < $pThreshold) {
            throw new Exception(sprintf('The number of rollable objects `%s` MUST be lesser or equal to the threshold value `%s`', count($pRollable), $pThreshold));
        }

        if (!isset(self::$methodList[$pAlgo])) {
            throw new Exception(sprintf('Unknown or unsupported sortable algorithm `%s`', $pAlgo));
        }

        $this->rollable = $pRollable;
        $this->threshold = $pThreshold;
        $this->method = self::$methodList[$pAlgo];
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str
            .strtoupper(array_search($this->method, self::$methodList))
            .$this->threshold;
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return $this->calculate('getMinimum');
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return $this->calculate('getMaximum');
    }

    /**
     * @inheritdoc
     */
    public function roll(): int
    {
        return $this->calculate('roll');
    }

    /**
     * Computes the sum to be return
     *
     * @param string $pMethod One of the Rollable method
     *
     * @return int
     */
    private function calculate(string $pMethod): int
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
}
