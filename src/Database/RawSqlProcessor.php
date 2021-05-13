<?php

namespace Libertyphp\Datamapper\Database;

class RawSqlProcessor
{
    private string $sql;

    private array $binds;

    private ?string $processedSql = null;

    private ?array $processedBinds = null;

    public function __construct(string $sql, array $binds)
    {
        $this->sql   = $sql;
        $this->binds = $binds;
    }

    public function getProcessedSql(): string
    {
        if ($this->processedSql === null) {
            $this->process();
        }

        return $this->processedSql;
    }

    public function getProcessedBinds(): array
    {
        if ($this->processedBinds === null) {
            $this->process();
        }

        return $this->processedBinds;
    }

    private function process(): void
    {
        $this->processedSql = $this->sql;

        if (!$this->binds) {
            $this->processedBinds = [];
        } else {
            $this->processedBinds = [];
            foreach ($this->binds as $originBindKey => $value) {
                // "Where in" support
                if (is_array($value)) {
                    $whereInSqlParts = [];
                    foreach ($value as $i => $valueItem) {
                        $whereInBindKey = 'in_' . $originBindKey . '_' . $i;
                        $whereInSqlParts[] = ':' . $whereInBindKey;
                        $this->processedBinds[$whereInBindKey] = $valueItem;
                    }

                    $whereInSqlString = join(',', $whereInSqlParts);

                    $this->processedSql = str_replace(':' . $originBindKey, $whereInSqlString, $this->processedSql);
                } else {
                    $this->processedBinds[$originBindKey] = $value;
                }
            }
        }
    }
}