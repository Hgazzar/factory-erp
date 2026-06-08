<?php

declare(strict_types=1);

namespace App\Contracts\Core\Documents;

interface DocumentGeneratorInterface
{
    /**
     * Persist a document for the given subject and return its absolute filesystem path.
     */
    public function storeFile(object $subject, int $tenantUserId): string;
}
