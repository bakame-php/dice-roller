<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

namespace Ethtezahl\DiceRoller\Modifier;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Rollable;

final class Explode implements Rollable
{
    const EQUALS = '=';
    const GREATER_THAN = '>';
    const LESSER_THAN = '<';

    /**
     * The Cup object to decorate
     *
     * @var Cup
     */
    private $rollable;

    /**
     * The threshold to apply the explosion to
     *
     * @var int
     */
    private $threshold = -1;

    /**
     * The comparison to use to apply the explosion to
     *
     * @var string
     */
    private $compare;

    /**
     * new instance
     *
     * @param Cup    $pRollable
     * @param string $pCompare
     * @param int    $pThreshold
     */
    public function __construct(Cup $pRollable, string $pCompare, int $pThreshold)
    {
        $this->rollable = $pRollable;
        if (-1 != $pThreshold) {
            $this->threshold = $pThreshold;
        }

        if (!in_array($pCompare, [self::EQUALS, self::GREATER_THAN, self::LESSER_THAN], true)) {
            throw new Exception(sprintf('The submitted compared string `%s` is invalid or unsuported', $pCompare));
        }

        $this->compare = $pCompare;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $prefix = '!';
        if (self::EQUALS != $this->compare ||
            (self::EQUALS == $this->compare && -1 != $this->threshold)
        ) {
            $prefix .= $this->compare;
        }

        if (-1 !== $this->threshold) {
            $prefix .= $this->threshold;
        }

        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$prefix;
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return $this->rollable->getMinimum();
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return PHP_INT_MAX;
    }

    /**
     * @inheritdoc
     */
    public function roll() : int
    {
        $sum = 0;
        foreach ($this->rollable as $innerRoll) {
            $sum = $this->calculate($sum, $innerRoll);
        }

        return $sum;
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param int      $pSum  initial sum
     * @param Rollable $pRollable
     *
     * @return int
     */
    private function calculate(int $pSum, Rollable $pRollable): int
    {
        $threshold = $this->threshold === -1 ? $pRollable->getMaximum() : $this->threshold;
        do {
            $res = $pRollable->roll();
            $pSum += $res;
        } while ($this->isValid($res, $threshold));

        return $pSum;
    }

    /**
     * Returns whether we should call the rollable again
     *
     * @param int $pResult
     * @param int $pThreshold
     *
     * @return bool
     */
    private function isValid(int $pResult, int $pThreshold): bool
    {
        if (self::EQUALS == $this->compare) {
            return $pResult === $pThreshold;
        }

        if (self::GREATER_THAN === $this->compare) {
            return $pResult > $pThreshold;
        }

        return $pResult < $pThreshold;
    }
}
