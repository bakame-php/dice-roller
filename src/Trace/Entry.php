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

namespace Bakame\DiceRoller\Trace;

use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Trace;

final class Entry implements Trace
{
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
    private $operation;

    /**
     * @var array
     */
    private $extensions;

    public function __construct(
        string $source,
        Rollable $subject,
        string $operation,
        int $result,
        array $extensions = []
    ) {
        unset($extensions['source'], $extensions['subject'], $extensions['operation'], $extensions['result']);

        $this->source = $source;
        $this->subject = $subject;
        $this->operation = $operation;
        $this->result = $result;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function subject(): Rollable
    {
        return $this->subject;
    }

    /**
     * {@inheritDoc}
     */
    public function result(): int
    {
        return $this->result;
    }

    /**
     * {@inheritDoc}
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function operation(): string
    {
        return $this->operation;
    }

    /**
     * {@inheritDoc}
     */
    public function extensions(): array
    {
        return $this->extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function context(): array
    {
        return [
            'source' => $this->source,
            'subject' => $this->subject->toString(),
            'operation' => $this->operation,
            'result' => $this->result,
        ] + $this->extensions;
    }
}
