<?php

declare(strict_types=1);

namespace Nip\Database\Metadata;

/**
 * Provides lazy-loaded metadata manager access.
 *
 * @package Nip\Database\Metadata
 */
trait HasMetadata
{
    protected ?Manager $metadata = null;

    public function getMetadata(): Manager
    {
        if ($this->metadata === null) {
            $this->metadata = new Manager();
            $this->metadata->setConnection($this);
        }

        return $this->metadata;
    }

    public function setMetadata(Manager $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }
}
