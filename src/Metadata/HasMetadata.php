<?php

namespace Nip\Database\Metadata;

use Nip\Database\Metadata\Manager as MetadataManager;

/**
 *
 */
trait HasMetadata
{
    protected ?MetadataManager $metadata = null;
    /**
     * @return MetadataManager
     */
    public function getMetadata()
    {
        if (!$this->metadata) {
            $this->metadata = new MetadataManager();
            $this->metadata->setConnection($this);
        }

        return $this->metadata;
    }

    /**
     * @param $metadata
     * @return static
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

}