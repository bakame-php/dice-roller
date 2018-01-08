<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/dice-roller/
* @version 1.0.0
* @package bakame-php/dice-roller
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Bakame\DiceRoller;

final class Result implements Roll
{
    /**
     * Rollable annotation
     *
     * @var string
     */
    private $annotation;

    /**
     *
     * Additional string to be used for generating
     * the right expression
     *
     * @var string
     */
    private $optional_expression;

    /**
     * Rollable result
     *
     * @var int|null
     */
    private $result;

    /**
     * @var bool
     */
    private $status = self::KEEP_ROLL;

    /**
     * @var Roll[]
     */
    private $children;

    /**
     * New instance
     *
     * @param Rollable $rollable
     * @param int      $result
     * @param Roll[]   $children
     * @param string   $optional_expression
     */
    public function __construct(
        Rollable $rollable,
        int $result = 0,
        array $children = [],
        string $optional_expression = ''
    ) {
        foreach ($children as $roll) {
            if (!$roll instanceof Roll) {
                throw new TypeError(sprintf('children must contain ony `%s` object', Roll::class));
            }
        }

        $this->children = (function (Roll ...$items) {
            return $items;
        })(...$children);

        $this->annotation = (string) $rollable;
        $this->result = $result;
        $this->optional_expression = $optional_expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotation(): string
    {
        return $this->annotation;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): string
    {
        $expression = '';
        if (empty($this->children)) {
            $expression .= (string) $this->result;
        } else {
            $part  = [];
            foreach ($this->children as $innerRoll) {
                if ($innerRoll->isOK()) {
                    $part[] = $innerRoll->getExpression();
                }
            }
            $expression .= implode(' + ', $part);
            if (count($this->children) > 2) {
                $expression = '('.$expression.')';
            }
        }

        if ('' !== $this->optional_expression) {
            $expression .= ' '.$this->optional_expression;
        }

        return $expression;
    }

    /**
     * Set whether the roll should be kept
     *
     * @param int $status
     */
    public function setStatus(int $status)
    {
        if (!in_array($status, [self::KEEP_ROLL, self::DROP_ROLL], true)) {
            throw new Exception(sprintf('Unknown `%s` status', $status));
        }

        $this->status = $status;
    }

    /**
     * {@inheritdoc}
     */
    public function isOK(): bool
    {
        return self::KEEP_ROLL === $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->children as $roll) {
            yield $roll;
        }
    }
}
