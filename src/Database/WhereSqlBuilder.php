<?php

namespace Libertyphp\Datamapper\Database;

class WhereSqlBuilder
{
    private array $where;

    private ?string $sql = null;

    private ?array $binds = null;

    public function __construct(array $where)
    {
        $this->where = $where;
    }

    public function getSql(): string
    {
        if ($this->sql === null) {
            $this->process();
        }

        return $this->sql;
    }

    public function getBinds(): array
    {
        if ($this->binds === null) {
            $this->process();
        }

        return $this->binds;
    }

    private function process(): void
    {
        $sqlParts = ['1 = 1'];
        $binds    = [];

        $bindIndex = 0;

        foreach ($this->where as $whereCondition => $value) {
            if (is_numeric($whereCondition)) {
                $whereCondition = $value;
            }

            if ($whereCondition !== $value) {
                $bindsCount = substr_count($whereCondition, '?');

                $replacedBinds = [];

                for ($i = 0; $i < $bindsCount; $i++) {
                    $bindKey = 'v_' . $bindIndex;
                    $binds[$bindKey] = $bindsCount > 1 ? $value[$i] : $value;

                    $replacedBinds[] = ':' . $bindKey;

                    $bindIndex++;
                }

                foreach ($replacedBinds as $replacedBind) {
                    $whereCondition = $this->strReplaceFirst('?', $replacedBind, $whereCondition);
                }
            }

            $sqlParts[] = '(' . $whereCondition . ')';
        }

        $this->sql   = join(' AND ', $sqlParts);
        $this->binds = $binds;
    }

    private function strReplaceFirst(string $search, string $replace, string $subject): string {
        if (($pos = strpos($subject, $search)) !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }
}