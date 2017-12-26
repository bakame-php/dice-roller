<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use Countable;
use IteratorAggregate;

final class Cup implements Countable, IteratorAggregate, Rollable
{
    /**
     * @var Rollable[]
     */
    private $items = [];

    /**
     * @var string
     */
    private $explain;

    /**
     * Create a new Cup From Dice definition.
     *
     * @param int        $quantity Dice count
     * @param int|string $nbSides  Dice sides count
     *
     * @throws Exception if the quantity is lesser than 1
     *
     * @return self
     */
    public static function createFromDice(int $quantity, $nbSides): self
    {
        if ($quantity < 1) {
            throw new Exception(sprintf('The quantity of dice `%s` is not valid', $quantity));
        }

        $size = self::filterSize($nbSides);
        $data = [];
        $class = 'f' === $size ? FudgeDice::class : Dice::class;
        for ($i = 0; $i < $quantity; ++$i) {
            $data[] = new $class($size);
        }

        return new self($data);
    }

    /**
     * Filter the Dice Slide size.
     *
     * @param int|string $nbSides
     *
     * @throws Exception if the submitted size is invalid
     *
     * @return int|string
     */
    private static function filterSize($nbSides)
    {
        $size = (int) filter_var($nbSides, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);
        if ($size > 1) {
            return $size;
        }

        $size = strtolower((string) $nbSides);
        if ('f' == $size) {
            return $size;
        }

        throw new Exception(sprintf('The number of dice `%s` is not valid', $nbSides));
    }

    /**
     * new instance
     *
     * @param mixed $items a list of Rollable objects (iterable array or Traversable object)
     */
    public function __construct($items = [])
    {
        if (!is_array($items)) {
            $items = iterator_to_array($items, false);
        }

        $this->items = array_filter($items, function (Rollable $item) {
            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $parts = array_map(function (Rollable $rollable) {
            return (string) $rollable;
        }, $this->items);

        $parts = array_filter($parts, function (string $value) {
            return '' !== $value;
        });

        $pool = array_count_values($parts);
        array_walk($pool, function (&$value, $offset) {
            $value = $value > 1 ? $value.$offset : $offset;
        });

        return implode('+', $pool);
    }

    /**
     * Returns the number of Rollable objects.
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->items as $rollable) {
            yield $rollable;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return array_reduce($this->items, [$this, 'minimum'], 0);
    }

    /**
     * Add the result of the Rollable::getMinimum method to the submitted sum.
     *
     * @param int      $sum      initial sum
     * @param Rollable $rollable
     *
     * @return int
     */
    private function minimum(int $sum, Rollable $rollable): int
    {
        return $sum + $rollable->getMinimum();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return array_reduce($this->items, [$this, 'maximum'], 0);
    }

    /**
     * Add the result of the Rollable::getMaximum method to the submitted sum.
     *
     * @param int      $sum      initial sum
     * @param Rollable $rollable
     *
     * @return int
     */
    private function maximum(int $sum, Rollable $rollable): int
    {
        return $sum + $rollable->getMaximum();
    }

    /**
     * {@inheritdoc}
     */
    public function explain(): string
    {
        return (string) $this->explain;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $res = array_reduce($this->items, [$this, 'calculate'], []);

        $roll = array_sum(array_column($res, 'roll'));
        $this->explain = implode(' + ', array_column($res, 'explain'));

        return $roll;
    }

    /**
     * Add the result of the Rollable::roll method to the submitted sum.
     *
     * @param array    $res
     * @param Rollable $rollable
     *
     * @return array
     */
    private function calculate(array $res, Rollable $rollable): array
    {
        $roll = $rollable->roll();
        $explain = $rollable->explain();
        if (false !== strpos($explain, '+')) {
            $explain = '('.$explain.')';
        }

        $res[] = [
            'roll' => $roll,
            'explain' => $explain,
        ];

        return $res;
    }
}
