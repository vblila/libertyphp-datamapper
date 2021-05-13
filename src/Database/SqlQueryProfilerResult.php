<?php

namespace Libertyphp\Datamapper\Database;

class SqlQueryProfilerResult
{
    private string $sql;

    private array $binds;

    private float $startMicroTimestamp;

    private ?float $finishMicroTimestamp = null;

    public function __construct(string $sql, array $binds = [])
    {
        $this->sql = $sql;
        $this->binds = $binds;
        $this->startMicroTimestamp = microtime(true);
    }

    public function setFinishMicroTimestamp(float $microTimestamp): self
    {
        $this->finishMicroTimestamp = $microTimestamp;
        return $this;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBinds(): array
    {
        return $this->binds;
    }

    public function getStartMicroTimestamp(): float
    {
        return $this->startMicroTimestamp;
    }

    public function getFinishMicroTimestamp(): ?float
    {
        return $this->finishMicroTimestamp;
    }

    public function getSqlQueryTimeSeconds(): float
    {
        return round($this->getFinishMicroTimestamp() - $this->getStartMicroTimestamp(), 4);
    }

}
