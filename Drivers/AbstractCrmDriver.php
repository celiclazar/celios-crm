<?php

namespace Modules\Crm\Drivers;

use Modules\Crm\Contracts\CrmDriverInterface;

abstract class AbstractCrmDriver implements CrmDriverInterface
{
    public function __construct(
        protected array $config = []
    ) {}

    public function getKey(): string
    {
        return $this->config['driver'] ?? static::class;
    }

    public function getName(): string
    {
        return $this->config['name'] ?? class_basename(static::class);
    }

    public function testConnection(): bool
    {
        return true;
    }
}
