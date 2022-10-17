<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers;

use Illuminate\Contracts\Support\Arrayable;

class ArrayableData implements Arrayable
{
    /**
     * @param array<int|string, mixed> $array
     */
    public function __construct(protected array $array)
    {
    }

    /**
     * Get the instance as an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->array;
    }
}
