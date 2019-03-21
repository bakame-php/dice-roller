# Dice Roller

[![Latest Version](https://img.shields.io/github/release/bakame-php/dice-roller.svg?style=flat-square)](https://github.com/bakame-php/dice-roller/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/bakame-php/dice-roller/master.svg?style=flat-square)](https://travis-ci.org/bakame-php/dice-roller)


A simple Dice Roller implemented in PHP.

```php
<?php

use Bakame\DiceRoller\Factory;

$factory = new Factory();

echo $factory->newInstance('2D6')->roll();     // returns 6
```

This is a fork of [Ethtezahl/Dice-Roller](https://github.com/Ethtezahl/dice-roller). The goal of this package is to build a modern PHP package which follow best practices.

## System Requirements

You need **PHP >= 7.2.0** but the latest stable version of PHP is recommended.

## Installation

```bash
$ composer require bakame-php/dice-roller
```

All classes are defined under the `Bakame\DiceRoller` namespace.

### Usage through the bundle cli command

```bash
$ bin/roll --iteration=3 --logs 2D3+5
 ====== ROLL RESULTS ======= 
 Result #1:  8
 Result #2:  10
 Result #3:  10

 ====== ROLL TRACE ======= 
 [Bakame\DiceRoller\Cup::roll] - 2D3 : 1 + 2 = 3   
 [Bakame\DiceRoller\Modifier\Arithmetic::roll] - 2D3+5 : 3 + 5 = 8   
 [Bakame\DiceRoller\Cup::roll] - 2D3 : 3 + 2 = 5   
 [Bakame\DiceRoller\Modifier\Arithmetic::roll] - 2D3+5 : 5 + 5 = 10   
 [Bakame\DiceRoller\Cup::roll] - 2D3 : 3 + 2 = 5   
 [Bakame\DiceRoller\Modifier\Arithmetic::roll] - 2D3+5 : 5 + 5 = 10  
```

## Basic usage

Use the library factory to simulate the roll of two six-sided die

```php
$factory = new Factory();
$pool = $factory->newInstance('2D6');

echo $pool->toString(); // returns 2D6
echo $pool->roll();     // returns 6
```

## Advance usage

Use the library bundled rollable objects to build a dice pool to roll.

```php
$pool = new Modifier\Arithmetic(
    new Cup(new SidedDie(6), new SidedDie(6)),
    $Modifier\Arithmetic::ADD,
    3
);

echo $pool->toString(); // returns 2D6+3
echo $pool->roll();     // returns 8
```

## Tracing and profiling an operation

```php
$profiler = new Profiler\LogProfiler(new Profiler\Logger());
$cup = new Cup(new SidedDie(6), new SidedDie(6));
$cup->setProfiler($profiler);

echo $cup->roll();     // returns 5
echo $cup->getTrace(); // returns 4 + 1 = 5

foreach ($profiler->getLogger()->getLogs(LogLevel::DEBUG) as $log) {
    echo $logs, PHP_EOL;
}
// [Bakame\DiceRoller\Cup::roll] - 2D6 : 4 + 1 = 5
```

## Documentation

### Rollable

To be rollable, objects MUST implements the `Bakame\DiceRoller\Contract\Rollable` interface.

```php
<?php

namespace Bakame\DiceRoller\Contract;

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

In addition to the `Rollable` interface, all dices objects implement the `Dice` interface. The `getSize` method returns the die sides count.

A die object must have at least 2 sides otherwise a `Bakame\DiceRoller\Exception\CanNotBeRolled` exception is thrown.

The following die type are bundled in the library:

| Class Name |  Definition |
| ---------- | ---------- |
| `SidedDie`| classic die |
| `FudgeDie` | 3 sided die with side values being `-1`, `0` and `1`. |
| `PercentileDie` | 100 sided die with values between `1` and `100`. |
| `CustomDie` | die with custom side values |

#### Object Constructors

- The `SidedDie` constructor unique argument is the dice sides count.
- The `CustomDie` constructor takes a variadic argument which represents the dice side values.
- The `FludgeDice` and `PercentileDie` constructor takes no argument.

```php
<?php

$basic = new SidedDie(3);
echo $basic->toString(); // 'D3';
$basic->roll();          // may return 1, 2 or 3
$basic->getSize();       // returns 3

$custom = new CustomDie(3, 2, 1, 1);
echo $customc->toString();  // 'D[3,2,1,1]';
$custom->roll();            // may return 1, 2 or 3
$custom->getSize();         // returns 4

$fugde = new FudgeDie();
echo $fudgec->toString(); // displays 'DF'
$fudge->roll();           // may return -1, 0, or 1
$fudge->getSize();        // returns 3

$percentile = new PercentileDie();
echo $percentilec->toString(); // displays 'D%'
$percentile->roll();           // returns a value between 1 and 100
$fudge->getSize();             // returns 100
```

### Pool

If you need to roll multiple dice at the same time, you need to implement the  `Bakame\DiceRoller\Contract\Pool` interface.
 
```php
<?php

namespace Bakame\DiceRoller\Contract;

interface Pool implements Countable, IteratorAggregate, Rollable
{
    public function isEmpty(): bool;
}
```
 
A `Pool` is a collection of `Rollable` objects which also implements the `Rollable` interface. The package comes bundle
with the `Bakame\DiceRoller\Cup` class which implements the interface.

```php
<?php

final class Cup implements Pool, Traceable
{
    public function __construct(Rollable ...$rollable);
    public static function createFromRollable(Rollable $rollable, int $quantity = 1, ?Profiler $profiler = null): self;
    public function withAddedRollable(Rollable ...$rollable): self
}
```

The `Cup::createFromRollable` named constructor enables creating uniformed `Cup` objects which contains only 1 type of rollable object.

```php
<?php

echo Cup::createFromRollable(new SidedDie(5), 3)->toString();           // displays 3D5
echo Cup::createFromRollable(new PercentileDie(), 4)->toString();       // displays 4D%
echo Cup::createFromRollable(new CustomDie(1, 2, 2, 4), 2)->toString(); // displays 2D[1,2,2,4]
echo Cup::createFromRollable(new FudgeDie(), 2)->toString();            // displays DF
```

A `Cup` created using `createFromRollable` must contain at least 1 `Rollable` object otherwise a `Bakame\DiceRoller\Exception\CanNotBeRolled` is thrown.

When iterating over a `Cup` object you will get access to all its inner `Rollable` objects.

```php
<?php

foreach (Cup::createFromRollable(new SidedDie(5), 3) as $rollable) {
    echo $rollable; // will always return D5
}
```

Once a `Cup` is instantiated there are no method to alter its properties. However the `Cup::withAddedRollable` method enables you to build complex `Cup` object using the builder pattern. The method will always returns a new `Cup` object but with the added `Rollable` objects while maintaining the state of the current `Cup` object.


```php
<?php

$cup = Cup::createFromRollable(new SidedDie(5), 3);
count($cup);           //returns 3 the number of dices
echo $cup->toString(); //returns 3D5

$alt_cup = $cup->withAddedRollable(new FugdeDice());
count($alt_cup);           //returns 4 the number of dices
echo $alt_cup->toString(); //returns 3D5+DF
```

**WARNING: a `Cup` object can be empty but adding an empty `Cup` object is not possible. The empty `Cup` object will be filtered out.**

### Rollable Decorator

Sometimes you may want to modify the outcome of a roll. The library comes bundle with 3 objects implementing the Decorator pattern, each implementing the `Modifier` interface.
The `Modifier` interface extends the `Rollable` interface by giving access to the rollable object being decorated through the `Modifier::getInnerRollable` method.  

```php
<?php

interface Modifier implements Rollable
{
    public function getInnerRollable(): Rollable;
}
```

#### The Arithmetic decorator

```php
<?php

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;

final class Arithmetic implements Modifier, Traceable
{
    public const ADD = '+';
    public const SUB = '-';
    public const MUL = '*';
    public const DIV = '/';
    public const EXP = '^';

    public function __construct(Rollable $rollable, string $operator, int $value, ?Profiler $profiler = null);
}
```

This modifier decorates a `Rollable` object by applying an arithmetic operation on the submitted `Rollable` object.

The modifier supports the following operators:

- `+` or `Arithmetic::ADD`;
- `-` or `Arithmetic::SUB`;
- `*` or `Arithmetic::MUL`;
- `/` or `Arithmetic::DIV`;
- `^` or `Arithmetic::EXP`;

The value given must be a positive integer or `0`. If the value or the operator are not valid a `CanNotBeRolled` exception will be thrown.

```php
<?php

$modifier = new Modifier\Arithmetic(
    new SidedDie(6),
    Modifier\Arithmetic::MUL,
    3
);
echo $modifier->toString();  // displays D6*3;
```

#### The DropKeep decorator

```php
<?php

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;

final class DropKeep implements Modifier, Traceable
{
    public const DROP_HIGHEST = 'dh';
    public const DROP_LOWEST = 'dl';
    public const KEEP_HIGHEST = 'kh';
    public const KEEP_LOWEST = 'kl';

    public function __construct(Rollable $pool, string $algo, int $threshold);
}
```

This modifier decorates a `Rollable` object by applying the one of the dropkeep algorithm on a `Pool` object. The constructor expects:

- a `Pool` object;
- a algorithm name;
- a threshold to trigger the algorithm;

The supported algorithms are:

- `dh` or `DropKeep::DROP_HIGHEST` to drop the `$threshold` highest results of a given `Cup` object;
- `dl` or `DropKeep::DROP_LOWEST` to drop the `$threshold` lowest results of a given `Cup` object;
- `kh` or `DropKeep::KEEP_HIGHEST` to keep the `$threshold` highest results of a given `Cup` object;
- `kl` or `DropKeep::KEEP_LOWEST` to keep the `$threshold` lowest results of a given `Cup` object;

The `$threshold` MUST be lower or equals to the total numbers of rollable items in the `Cup` object.

If the algorithm or the threshold are not valid a `Bakame\DiceRoller\CanNotBeRolled` will be thrown.

```php
<?php

$cup = Cup::createFromRollable(new SidedDie(6), 4);
$modifier = new Modifier\DropKeep($cup, Modifier\DropKeep::DROP_HIGHEST, 3);
echo $modifier->toString(); // displays '4D6DH3'
```

#### The Explode decorator

```php
<?php

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;

final class Explode implements Modifier, Traceable
{
    public const EQ = '=';
    public const GT= '>';
    public const LT = '<';

    public function __construct(Rollable $pool, string $compare, int $threshold);
}
```

This modifier decorates a `Pool` object by applying one of the explode algorithm. The constructor expects:

- a `Pool` implementing object;
- a comparison operator string;
- a threshold to trigger the algorithm;

The supported comparison operator are:

- `=` or `Explode::EQ` explodes if any inner rollable roll result is equal to the `$threshold`;
- `>` or `Explode::GT` explodes if any inner rollable roll result is greater than the `$threshold`;
- `<` or `Explode::LT` explodes if any inner rollable roll result is lesser than the `$threshold`;

If the comparison operator is not recognized a `CanNotBeRolled` will be thrown.

```php
<?php

$cup = new Cup(new SidedDie(6), new FudgeDie(), new Dice(6), new Dice(6));
$modifier = new Modifier\Explode($cup, Modifier\Explode::EQ, 3);
echo $modifier->toString(); // displays (3D6+DF)!=3
```

### Parsing Dice notation

```php
<?php

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Contract\Parser;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Profiler;

final class Factory
{
    public function __construct(?Parser $parser = null, ?Profiler $profiler = null);
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

$factory = new Factory(new ExpressionParser());
$cup = $factory->newInstance('3D20+4+D4!>3/4^3');

echo $cup->roll();
```

If the `Factory` is not able to parse the submitted dice annotation a `CanNotBeRolled` will be thrown.

##  Tracing and Profiling

If you want to know how internally your roll result is calculated your `Rollable` object must implements the `Traceable` interface.

```php
<?php

namespace Bakame\DiceRoller\Contract;

interface Traceable
{
    public function getTrace(): string;

    public function getProfiler(): Profiler;
}
```
 
The interface enables getting the trace from the last operation as well as profiling the total execution of the operation using a `Bakame\DiceRoller\Contract\Profiler` implementing object.

```php
<?php

namespace Bakame\DiceRoller\Contract;

interface Profiler
{
    public function addTrace(Rollable $rollable, string $method, int $roll, string $trace): void;
}
```

The package comes bundle with two (2) profiler implementations:
 
- a `Bakame\DiceRoller\Contract\Profiler\NullProfiler` that does nothing;
- a `Bakame\DiceRoller\Contract\Profiler\LogProfiler` which sends the traces to a PSR-3 compliant log.

### The LogProfiler

The `LogProfiler` log messages, by default, will match this format:

    [{method}] - {rollable} : {trace} = {result}

You can customize the message format using the `LogProfiler::setLogFormat()`
method, like so:

```php
$profiler->setLogFormat("{trace} -> {result}")
```

The context keys are:

- `{method}`: The method that was called that created the profile entry.
- `{rollable}`: The string representation of the `Rollable` object to be analyzed.
- `{trace}`: The calculation that was done.
- `{result}`: The result from performing the calculation.

### Configuring the LogProfiler

At any moment you can change, using the profiler setter methods:

- the log level
- the log format
- the PSR-3 logger 

```php
use Bakame\DiceRoller\Contract\Profiler\Logger;
use Bakame\DiceRoller\Contract\Profiler\LogProfiler;
use Psr\Log\LogLevel;

$logger = new Logger();
$profiler = new LogProfiler($logger, LogLevel::DEBUG, '{trace} = {result}');
$profiler->setLogLevel(LogLevel::INFO);
$profiler->setLogFormat('{trace} -> {result}');
$profiler->setLogger(new Psr3CompliantLogger());
```

Even though, the library comes bundles with a `Psr\Log\LoggerInterface` implementation you should consider using a better fleshout implementation than the one present out of the box.

**Happy Coding!**
