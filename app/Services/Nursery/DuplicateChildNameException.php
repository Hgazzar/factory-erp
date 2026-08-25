<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Child;
use InvalidArgumentException;

final class DuplicateChildNameException extends InvalidArgumentException
{
    public function __construct(
        string $message,
        private readonly Child $existingChild,
    ) {
        parent::__construct($message);
    }

    public function existingChild(): Child
    {
        return $this->existingChild;
    }
}
