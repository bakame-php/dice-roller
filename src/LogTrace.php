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

use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Trace;
use function array_filter;
use function in_array;
use const ARRAY_FILTER_USE_KEY;

final class LogTrace implements Trace
{
    private const REQUIRED_CONTEXT_FIELDS = ['source', 'subject', 'line', 'result'];

    /**
     * @var Rollable
     */
    private $subject;

    /**
     * @var int
     */
    private $result;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $line;

    /**
     * @var array
     */
    private $optionals;

    public function __construct(
        Rollable $subject,
        int $result,
        string $source,
        string $line,
        array $optionals = []
    ) {
        $this->subject = $subject;
        $this->result = $result;
        $this->source = $source;
        $this->line = $line;
        $this->optionals = $optionals;
    }

    public function subject(): Rollable
    {
        return $this->subject;
    }

    public function result(): int
    {
        return $this->result;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function line(): string
    {
        return $this->line;
    }

    public function optionals(): array
    {
        return $this->optionals;
    }

    public function context(): array
    {
        $filterOutRequiredKeys = function ($offset): bool {
            return !in_array($offset, self::REQUIRED_CONTEXT_FIELDS, true);
        };

        $optionals = array_filter($this->optionals, $filterOutRequiredKeys, ARRAY_FILTER_USE_KEY);

        return [
            'source' => $this->source,
            'subject' => $this->subject->toString(),
            'result' => $this->result,
            'line' => $this->line,
        ] + $optionals;
    }
}
