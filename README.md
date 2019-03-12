# Dice Roller

[![Latest Version](https://img.shields.io/github/release/bakame-php/dice-roller.svg?style=flat-square)](https://github.com/bakame-php/dice-roller/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/bakame-php/dice-roller/master.svg?style=flat-square)](https://travis-ci.org/bakame-php/dice-roller)


A simple Dice Roller implemented in PHP.

This is a fork of [Ethtezahl/Dice-Roller](https://github.com/Ethtezahl/dice-roller)

## System Requirements

You need **PHP >= 7.2.0** but the latest stable version of PHP is recommended.

## Installation

```bash
$ composer require bakame-php/dice-roller
```

## Basic usage

Use the library factory to simulate the roll of two six-sided die

```php
<?php

use Bakame\DiceRoller\Factory;

$factory = new Factory();
$cup = $factory->newInstance('2D6');
echo $cup->toString(); // returns 2D6
echo $cup->roll();     // returns 6
```

## Advanced use

Use the library bundle rollable objects directly to simulate the roll of two six-sided die

```php
<?php

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;

$cup = (new Cup())->withAddedRollable(new Dice(6), new Dice(6));

echo $cup->toString(); // returns 2D6
echo $cup->roll();     // returns 8
```

## Documentation

### Rollable

To be rollable, objects MUST implements the `Bakame\DiceRoller\Rollable` interface.

```php
<?php

namespace Bakame\DiceRoller\Type;

interface Rollable
{
    public function getMinimum(): int;
    public function getMaximum(): int;
    public function roll(): int;
    public function toString(): string;
}
```

- `Rollable::getMinimum` returns the minimum value the rollable object can return during a roll;
- `Rollable::getMaximum` returns the maximum value the rollable object can return during a roll;
- `Rollable::roll` returns a value from a roll.
- `Rollable::toString` returns the string notation of the Rollable` object.

### Dices Type

In addition to the `Rollable` interface, all dices objects implement the `Countable` interface. The `count` method returns the die sides count.

A die object must have at least 2 sides otherwise a `Bakame\DiceRoller\Exception\RollException` exception is thrown.

The following die type are bundled in the library:

| Class Name |  Definition |
| ---------- | ---------- |
| `Bakame\DiceRoller\Dice`| classic die |
| `Bakame\DiceRoller\FudgeDice` | 3 sided die with side values being `-1`, `0` and `1`. |
| `Bakame\DiceRoller\PercentileDice` | 100 sided die with values between `1` and `100`. |
| `Bakame\DiceRoller\CustomDice` | die with custom side values |

#### Object Constructors

- The `Dice` constructor unique argument is the dice sides count.
- The `CustomDice` constructor takes a variadic argument which represents the dice side values.
- The `FludgeDice` and `PercentileDice` constructor takes no argument.

```php
<?php

use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\FudgeDice;
use Bakame\DiceRoller\PercentileDice;

$basic = new Dice(3);
echo $basic->toString(); // 'D3';
$basic->roll();          // may return 1, 2 or 3
count($basic);           // returns 3

$custom = new CustomDice(3, 2, 1, 1);
echo $customc->toString();  // 'D[3,2,1,1]';
$custom->roll();            // may return 1, 2 or 3
count($custom);             // returns 4

$fugde = new FudgeDice();
echo $fudgec->toString(); // displays 'DF'
$fudge->roll();           // may return -1, 0, or 1
count($fudge);            // returns 3

$percentile = new PercentileDice();
echo $percentilec->toString(); // displays 'D%'
$percentile->roll();           // returns a value between 1 and 100
count($fudge);                 // returns 100
```

### Pool

If you need to roll multiple dices at the same time, you need to implement the  `Bakame\DiceRoller\Pool` interface.
 
```php
<?php

namespace Bakame\DiceRoller;

interface Pool implements Countable, IteratorAggregate, Rollable
{
    public function isEmpty(): bool;
}
```
 
A `Pool` is a collection of `Rollable` objects which also implements the `Rollable` interface. The package comes bundle
with the `Bakame\DiceRoller\Cup` class which implements the interface. A `Cup` can contains any type of dices but others `Pool` objects as well.

```php
<?php

namespace Bakame\DiceRoller;

final class Cup implements Pool
{
    public function __construct(?Tracer $tracker = null);
    public static function createFromRollable(int $quantity, Rollable $rollable, ?Tracer $profiler = null): self;
    public function withAddedRollable(Rollable ...$rollable): self
}
```

The `Cup::createFromRollable` named constructor enables creating uniformed `Cup` objects which contains only 1 type of rollable object.

```php
<?php

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\FudgeDice;
use Bakame\DiceRoller\PercentileDice;

echo Cup::createFromRollable(3, new Dice(5))->toString();                // displays 3D5
echo Cup::createFromRollable(4, new PercentileDice())->toString();       // displays 4D%
echo Cup::createFromRollable(2, new CustomDice(1, 2, 2, 4))->toString(); // displays 2D[1,2,2,4]
echo Cup::createFromRollable(1, new FudgeDice())->toString();            // displays DF
```

A `Cup` created using `createFromRollable` must contain at least 1 `Rollable` object otherwise a `Bakame\DiceRoller\Exception\CanNotBeRolled` is thrown.

When iterating over a `Cup` object you will get access to all its inner `Rollable` objects.

```php
<?php

use Bakame\DiceRoller\Cup;

foreach (Cup::createFromRollable(3, new Dice(5)) as $rollable) {
    echo $rollable; // will always return D5
}
```

Once a `Cup` is instantiated there are no method to alter its properties. However the `Cup::withAddedRollable` method enables you to build complex `Cup` object using the builder pattern. The method will always returns a new `Cup` object but with the added `Rollable` objects while maintaining the state of the current `Cup` object.


```php
<?php

use Bakame\DiceRoller\Cup;

$cup = Cup::createFromRollable(3, new Dice(5));
count($cup);           //returns 3 the number of dices
echo $cup->toString(); //returns 3D5

$alt_cup = $cup->withAddedRollable(new FugdeDice());
count($alt_cup);           //returns 4 the number of dices
echo $alt_cup->toString(); //returns 3D5+DF
```

**WARNING: a `Cup` object can be empty but adding an empty `Cup` object is not possible. The empty `Cup` object will be filtered out.**


### Rollable Decorator

Sometimes you may want to modify the outcome of a roll. The library comes bundle with 3 object implementing the Decorator pattern, each implementing the `Rollable` and the `RollableDecorator` interfaces.

#### The Arithmetic decorator

```php
<?php

namespace Bakame\DiceRoller\Decorator;

use Bakame\DiceRoller\Rollable;
use Bakame\DiceRoller\RollableDecorator;

final class Arithmetic implements Rollable, RollableDecorator
{
    public const ADDITION = '+';
    public const SUBSTRACTION = '-';
    public const MULTIPLICATION = '*';
    public const DIVISION = '/';
    public const EXPONENTIATION = '^';

    public function __construct(Rollable $rollable, string $operator, int $value, ?Tracer $profiler = null);
    public function getInnerRollable() : Rollable;
}
```

This modifier decorates a `Rollable` object by applying an arithmetic operation on the submitted `Rollable` object.

The modifier supports the following operators:

- `+` or `Arithmetic::ADDITION`;
- `-` or `Arithmetic::SUBSTRACTION`;
- `*` or `Arithmetic::MULTIPLICATION`;
- `/` or `Arithmetic::DIVISION`;
- `^` or `Arithmetic::EXPONENTIATION`;

The value given must be a positive integer or `0`. If the value or the operator are not valid a `CanNotBeRolled` exception will be thrown.

```php
<?php

use Bakame\DiceRoller\Decorator\Arithmetic;
use Bakame\DiceRoller\Dice;

$modifier = new Arithmetic(new Dice(6), Arithmetic::MULTIPLICATION, 3);
echo $modifier->toString();  // displays D6*3;
```

#### The DropKeep decorator

```php
<?php

namespace Bakame\DiceRoller\Decorator;

use Bakame\DiceRoller\Pool;
use Bakame\DiceRoller\Rollable;
use Bakame\DiceRoller\RollableDecorator;

final class DropKeep implements Rollable, RollableDecorator
{
    public const DROP_HIGHEST = 'dh';
    public const DROP_LOWEST = 'dl';
    public const KEEP_HIGHEST = 'kh';
    public const KEEP_LOWEST = 'kl';

    public function __construct(Pool $pool, string $algo, int $threshold, ?Tracer $tracer = null);    public function getInnerRollable() : Rollable;
    public function getInnerRollable() : Rollable;
}
```

This modifier decorates a `Rollable` object by applying the one of the dropkeep algorithm on a `Pool` object. The constructor expects:

- a `Pool` object;
- a algorithm name;
- a threshold to trigger the algorithm;

The supported algorithms are:

- `dh` or `DropKeep::DROP_HIGHEST` to drop the `$pThreshold` highest results of a given `Cup` object;
- `dl` or `DropKeep::DROP_LOWEST` to drop the `$pThreshold` lowest results of a given `Cup` object;
- `kh` or `DropKeep::KEEP_HIGHEST` to keep the `$pThreshold` highest results of a given `Cup` object;
- `kl` or `DropKeep::KEEP_LOWEST` to keep the `$pThreshold` lowest results of a given `Cup` object;

The `$threshold` MUST be lower or equals to the total numbers of rollable items in the `Cup` object.

If the algorithm or the threshold are not valid a `Bakame\DiceRoller\CanNotBeRolled` will be thrown.

```php
<?php

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Decorator\DropKeep;

$cup = Cup::createFromRollable(4, new Dice(6));
$modifier = new DropKeep($cup, DropKeep::DROP_HIGHEST, 3);
echo $modifier->toString(); // displays '4D6DH3'
```

#### The Explode decorator

```php
<?php

namespace Bakame\DiceRoller\Decorator;

use Bakame\DiceRoller\Pool;
use Bakame\DiceRoller\Rollable;
use Bakame\DiceRoller\RollableDecorator;

final class Explode implements Rollable, RollableDecorator
{
    public const EQUALS = '=';
    public const GREATER_THAN = '>';
    public const LESSER_THAN = '<';

    public function __construct(Pool $pool, string $compare, int $threshold, ?Tracer $tracer = null);
    public function getInnerRollable() : Rollable;
}
```

This modifier decorates a `Pool` object by applying one of the explode algorithm. The constructor expects:

- a `Pool` implementing object;
- a comparison operator string;
- a threshold to trigger the algorithm;

The supported comparison operator are:

- `=` or `Explode::EQUALS` explodes if any inner rollable roll result is equal to the `$pThreshold`;
- `>` or `Explode::GREATER_THAN` explodes if any inner rollable roll result is greater than the `$pThreshold`;
- `<` or `Explode::LESSER_THAN` explodes if any inner rollable roll result is lesser than the `$pThreshold`;

If the comparison operator is not recognized a `CanNotBeRolled` will be thrown.

```php
<?php

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Decorator\Explode;
use Bakame\DiceRoller\FudgeDice;

$cup = (new Cup())->withAddedRollable(new Dice(6), new FudgeDice(), new Dice(6), new Dice(6));
$modifier = new Explode($cup, Explode::EQUALS, 3);
echo $modifier->toString(); // displays (3D6+DF)!=3
```

### Parsing Dice notation

```php
<?php

namespace Bakame\DiceRoller;

final class Factory
{
    public function __construct(?Tracer $tracer = null);
    public function newInstance(string $annotation): Rollable;
}
```

The package comes bundles with a parser class to ease `Rollable` instance creation. The parser supports basic roll annotation rules in a case insentitive way:


| Annotation | Examples  | Description |
| ---------- | -------- | -------- |
|  `NDX`     |  `3D4` |  create a dice pool where `N` represents the number of dices and `X` the number of sides. If `X` is omitted this means you are requesting a 6 sides basic dice. If `N` is omitted this means that you are requestion a single dice. |
|  `NDF`     |  `3DF` |  create a dice pool where `N` represents the number of fudge dices. If `N` is omitted this means that you are requestion a single fugde dice. |
|  `ND%`     |  `3D%` |  create a dice pool where `N` represents the number of percentile dices. If `N` is omitted this means that you are requestion a single percentile dice. |
|  `ND[x,x,x,x,...]`     |  `2D[1,2,2,5]` |  create a dice pool where `N` represents the number of custom dices and `x` the value of a specific dice side. The number of `x` represents the side count. If `N` is omitted this means that you are requestion a single custome dice. a Custom dice must contain at least 2 sides. |
| `oc`       | `^3`| where `o` represents the supported operators (`+`, `-`, `*`, `/`, `^`) and `c` a positive integer |
|  `!oc`     | `!>3` | an explode modifier where `o` represents one of the supported comparision operator (`>`, `<`, `=`)  and `c` a positive integer |
|  `[dh,dl,kh,kl]z` | `dh4` | keeping or dropping the lowest or highest z dice |


- **Only 2 arithmetic modifiers can be appended to a given dice pool.**  
- *The `=` comparison sign when using the explode modifier can be omitted*

By applying these rules the `Parser` can construct the following `Rollable` object:

```php
<?php

use Bakame\DiceRoller\Factory;

$factory = new Factory();
$cup = $factory->newInstance('3D20+4+D4!>3/4^3');

echo $cup->roll();
```

If the `Factory` is not able to parse the submitted dice annotation a `CanNotBeRolled` will be thrown.

##  Tracing

If you want to know how internally your roll result is calculated you will need to attach a tracer to a `Rollable` object. 
The tracer logs the object actions and implements the `Bakame\DiceRoller\Tracer` interface.

The package comes bundle with two (2) tracer implementations:
 
- a `Bakame\DiceRoller\Tracer\NullTracer` that does nothing;
- a `Bakame\DiceRoller\Tracer\LogTracer` which sends the traces to a PSR-3 compliant log.

### The LogTracer

The `LogTracer` log messages, by default, will match this format:

    [{method}] - {rollable} : {trace} = {result}

You can customize the message format using the `LogTracer::setLogFormat()`
method, like so:

```php
$tracer->setLogFormat("{trace} -> {result}")
```

The context keys are:

- `{method}`: The method that was called that created the profile entry.
- `{rollable}`: The string representation of the `Rollable` object to be analyzed.
- `{trace}`: The calculation that was done.
- `{result}`: The result from performing the calculation.

### Configuring the LogTracer

At any moment you can change, using the profiler setter methods:

- the log level
- the log format
- the PSR-3 logger 

```php
use Bakame\DiceRoller\Tracer\Logger;
use Bakame\DiceRoller\Tracer\LogTracer;
use Psr\Log\LogLevel;

$logger = new Logger();
$tracer = new LogTracer($logger, LogLevel::DEBUG, '{trace} = {result}');
$tracer->setLogLevel(LogLevel::INFO);
$tracer->setLogFormat('{trace} -> {result}');
$tracer->setLogger(new Psr3CompliantLogger());
```

Even though, the library comes bundles with a `Psr\Log\LoggerInterface` implementation you should consider using a better fleshout implementation than the one present out of the box.

**Happy Coding!**
