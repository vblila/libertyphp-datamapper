<?php

namespace Libertyphp\Datamapper\Database;

class SqlQueryProfiler
{
    /** @var SqlQueryProfilerResult[] */
    private array $sqlQueryProfilerResults = [];

    private bool $isEnabled = false;

    public function setEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function addSqlQueryProfilerResult(SqlQueryProfilerResult $result): self
    {
        $this->sqlQueryProfilerResults[] = $result;
        return $this;
    }

    /**
     * @return SqlQueryProfilerResult[]
     */
    public function getSqlQueryProfilerResults(): array
    {
        return $this->sqlQueryProfilerResults;
    }

    public function getSqlQueriesCount(): int
    {
        return count($this->getSqlQueryProfilerResults());
    }

    public function getSqlQueriesTotalTimeSeconds(): float
    {
        $time = 0;
        foreach ($this->getSqlQueryProfilerResults() as $queryProfilerResult) {
            $time += $queryProfilerResult->getSqlQueryTimeSeconds();
        }

        return round($time, 4);
    }
}
