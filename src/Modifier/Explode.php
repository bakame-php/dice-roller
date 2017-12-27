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
     * The threshold.
     *
     * @var int
     */
    private $threshold = -1;

    /**
     * The comparison to use.
     *
     * @var string
     */
    private $compare;

    /**
     * @var string
     */
    private $trace;

    /**
     * new instance
     *
     * @param Cup    $rollable
     * @param string $compare
     * @param int    $threshold
     */
    public function __construct(Cup $rollable, string $compare, int $threshold)
    {
        $this->rollable = $rollable;
        if (-1 != $threshold) {
            $this->threshold = $threshold;
        }

        if (!in_array($compare, [self::EQUALS, self::GREATER_THAN, self::LESSER_THAN], true)) {
            throw new Exception(sprintf('The submitted compared string `%s` is invalid or unsuported', $compare));
        }

        $this->compare = $compare;
        $this->trace = '';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';
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
     * {@inheritdoc}
     */
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';
        return $this->rollable->getMinimum();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';
        return PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $sum = 0;
        $this->trace = '';
        foreach ($this->rollable as $innerRoll) {
            $sum = $this->calculate($sum, $innerRoll);
        }

        return $sum;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     *
     * @param int      $sum
     * @param Rollable $rollable
     *
     * @return int
     */
    private function calculate(int $sum, Rollable $rollable): int
    {
        $trace = [];
        $threshold = $this->threshold === -1 ? $rollable->getMaximum() : $this->threshold;
        do {
            $res = $rollable->roll();
            $sum += $res;
            $str = $rollable->getTrace();
            if (false !== strpos($str, '+')) {
                $str = '('.$str.')';
            }
            $trace[] = $str;
        } while ($this->isValid($res, $threshold));

        $trace = implode(' + ', $trace);
        if ('' !== $this->trace) {
            $trace = ' + '.$trace;
        }

        $this->trace .= $trace;

        return $sum;
    }

    /**
     * Returns whether we should call the rollable again.
     *
     * @param int $pResult
     * @param int $threshold
     *
     * @return bool
     */
    private function isValid(int $pResult, int $threshold): bool
    {
        if (self::EQUALS == $this->compare) {
            return $pResult === $threshold;
        }

        if (self::GREATER_THAN === $this->compare) {
            return $pResult > $threshold;
        }

        return $pResult < $threshold;
    }
}
