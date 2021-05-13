<?php

namespace Libertyphp\Datamapper\Tests\Database\MySql;

use Libertyphp\Datamapper\Database\MySql\MySqlDatabaseTable;

final class TestUserTable extends MySqlDatabaseTable
{
    public static function getTableName(): string
    {
        return 'test_users';
    }

    public static function getPrimaryKeyName(): string
    {
        return 'id';
    }
}