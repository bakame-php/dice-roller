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
    private $trace;

    /**
     * Create a new Cup From Dice definition.
     *
     * The returned Cup object will contain only one type of Rollable object.
     *
     * @param int    $quantity   Dice count
     * @param string $definition Dice definition
     *
     * @throws Exception if the quantity is lesser than 1
     *
     * @return self
     */
    public static function createFromDice(int $quantity, string $definition): self
    {
        if ($quantity < 1) {
            throw new Exception(sprintf('The quantity of dice `%s` is not valid', $quantity));
        }

        $size = (int) filter_var($definition, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);
        if ($size > 1) {
            return self::createFromSidedDice($quantity, $size);
        }

        $definition = strtolower((string) $definition);
        if ('f' === $definition) {
            return self::createFromFudgeDice($quantity);
        }

        if ('%' === $definition) {
            return self::createFromPercentileDice($quantity);
        }

        if ('][' === substr($definition.$definition, strlen($definition) - 1, 2)) {
            $sideValues = explode(',', substr($definition, 1, -1));
            $sideValues = filter_var($sideValues, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]);

            return self::createFromCustomDice($quantity, $sideValues);
        }

        throw new Exception(sprintf('The dice definition `%s` is invalid or not supported', $definition));
    }

    /**
     * Create a new Cup containing only Sided Dices
     *
     * @param int $quantity
     * @param int $size
     *
     * @return self
     */
    public static function createFromSidedDice(int $quantity, int $size): self
    {
        $data = [];
        for ($i = 0; $i < $quantity; ++$i) {
            $data[] = new Dice($size);
        }

        return new self(...$data);
    }

    /**
     * Create a new Cup containing only Fudge Dices
     *
     * @param int $quantity
     *
     * @return self
     */
    public static function createFromFudgeDice(int $quantity): self
    {
        $data = [];
        for ($i = 0; $i < $quantity; ++$i) {
            $data[] = new FudgeDice();
        }

        return new self(...$data);
    }

    /**
     * Create a new Cup containing only Percentile Dices
     *
     * @param int $quantity
     *
     * @return self
     */
    public static function createFromPercentileDice(int $quantity): self
    {
        $data = [];
        for ($i = 0; $i < $quantity; ++$i) {
            $data[] = new PercentileDice();
        }

        return new self(...$data);
    }

    /**
     * Create a new Cup containing only custome sided Dices
     *
     * @param int $quantity
     * @param int $sidesValues
     *
     * @return self
     */
    public static function createFromCustomDice(int $quantity, array $sidesValues): self
    {
        $data = [];
        for ($i = 0; $i < $quantity; ++$i) {
            $data[] = new CustomDice(...$sidesValues);
        }

        return new self(...$data);
    }

    /**
     * new instance
     *
     * @param mixed $items a list of Rollable objects (iterable array or Traversable object)
     */
    public function __construct(Rollable ...$items)
    {
        $this->items = $items;
        $this->trace = '';
    }

    /**
     * Return an instance with the added Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     *
     * @param Rollable $rollable
     *
     * @return self
     */
    public function withRollable(Rollable $rollable): self
    {
        $cup = new self();
        foreach ($this->items as $value) {
            $cup->items[] = clone $value;
        }
        $cup->items[] = $rollable;

        return $cup;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

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
        $this->trace = '';

        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->trace = '';

        foreach ($this->items as $rollable) {
            yield $rollable;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

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
        $this->trace = '';

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
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $res = array_reduce($this->items, [$this, 'calculate'], []);

        $roll = array_sum(array_column($res, 'roll'));

        $this->trace = implode(' + ', array_map(function (string $value) {
            if (false !== strpos($value, '+')) {
                return '('.$value.')';
            }

            return $value;
        }, array_column($res, 'trace')));

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
        $trace = $rollable->getTrace();

        $res[] = [
            'roll' => $roll,
            'trace' => $trace,
        ];

        return $res;
    }
}
