<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use Countable;
use IteratorAggregate;
use OutOfRangeException;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * Create a new Cup From Dice definition
     *
     * @param int        $pQuantity Dice count
     * @param int|string $pSize     Dice sides count
     *
     * @throws OutOfRangeException if the quantity is lesser than 1
     *
     * @return self
     */
    public static function createFromDice(int $pQuantity, $pSize): self
    {
        if ($pQuantity < 1) {
            throw new OutOfRangeException(sprintf('The quantity of dice `%s` is not valid', $pQuantity));
        }

        $size = self::filterSize($pSize);
        $dice = ('f' === $size) ? new FudgeDice() : new Dice($size);

        return new self(...array_fill(0, $pQuantity, $dice));
    }

    /**
     * Filter the Dice Slide size
     *
     * @param  int|string $pSize
     *
     * @throws OutOfRangeException if the submitted size is invalid
     *
     * @return int|string
     */
    private static function filterSize($pSize)
    {
        $size = (int) filter_var($pSize, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);
        if ($size > 1) {
            return $size;
        }

        $size = strtolower((string) $pSize);
        if ('f' == $size) {
            return $size;
        }

        throw new OutOfRangeException(sprintf('The number of dice size `%s` is not valid', $pSize));
    }

    /**
     * New instance
     *
     * @param Rollable ...$pItems a list of Rollable objects
     */
    public function __construct(Rollable ...$pItems)
    {
        $this->items = $pItems;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        foreach ($this->items as $rollable) {
            yield $rollable;
        }
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return array_reduce($this->items, [$this, 'minimum'], 0);
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param  int      $pSum  initial sum
     * @param  Rollable $pRollable
     *
     * @return int
     */
    private function minimum(int $pSum, Rollable $pRollable): int
    {
        return $pSum + $pRollable->getMinimum();
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return array_reduce($this->items, [$this, 'maximum'], 0);
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param  int      $pSum  initial sum
     * @param  Rollable $pRollable
     *
     * @return int
     */
    private function maximum(int $pSum, Rollable $pRollable): int
    {
        return $pSum + $pRollable->getMaximum();
    }

    /**
     * @inheritdoc
     */
    public function roll(): int
    {
        return array_reduce($this->items, [$this, 'sum'], 0);
    }

    /**
     * Add the result of the Rollable::roll method
     * to the submitted sum
     *
     * @param  int      $sum  initial sum
     * @param  Rollable $item
     *
     * @return int
     */
    private function sum(int $pSum, Rollable $pRollable): int
    {
        return $pSum + $pRollable->roll();
    }
}