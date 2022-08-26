<?php

namespace StubTests\Model;

use ArrayAccess;
use Countable;
use Iterator;
use ReturnTypeWillChange;
use RuntimeException;
use function sizeof;

class PhpVersions implements ArrayAccess, Iterator, Countable
{
    private $position = 0;
    private static $versions = [5.3, 5.4, 5.5, 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3];

    public function __construct() {
        $this->position = 0;
    }

    public static function getLatest()
    {
        return self::$versions[sizeof(self::$versions) - 1];
    }

    /**
     * @return float
     */
    public static function getFirst()
    {
        return self::$versions[0];
    }

    /**
     * @param $offset
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset(self::$versions[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? self::$versions[$offset] : null;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new RuntimeException('Unsupported operation');
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new RuntimeException('Unsupported operation');
    }

    public function current(): mixed
    {
        return self::$versions[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset(self::$versions[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return sizeof(self::$versions);
    }
}
