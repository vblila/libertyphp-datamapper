Liberty PHP Datamapper
----------------------
Simple and powerful datamapper for MySQL and PostgreSQL. 

Philosophy of the project
-------------------------
- Active record is anti-pattern. It violates SOLID principles of object-oriented programming. Moreover, the models of active record pattern are often too heavy, so this leads to a loss of performance.
- Repository pattern is not the best solution for working with relational databases. This pattern is too abstracted. This leads to complexity of the code and also to the loss of performance.
- Query builder is not the best solution for making SQL queries. This solution creates additional abstraction, so we lose advantages when using the specific features of the SQL of various databases.
- Abstraction must help reduce programming complexity and effort, but at the same time it must preserve the advantages of each database.
- Describing relationships in models is bad. This is an unnecessary abstraction and additional restrictions that lead to a loss in performance.
- Never write complex SQL queries especially using table joins with Query Builder

Working with library
------------------------

There are two ways to work with the database:
- Raw SQL queries
- "Row" layer
- "Model" layer


Working with "Row" layer
------------------------

You need to make table class, for example:
```php
class UserTable extends PostgreSqlDatabaseTable
{
    public static function getPrimaryKeyName(): string
    {
        return 'id';
    }

    public static function getTableName(): string
    {
        return 'users';
    }
}

```

You need to make your database instance, for example:
```php
$dsn = 'pgsql:host=172.17.0.1;dbname=mydb';
$pdo = new PDO($dsn, 'postgres', 'mypassword');

$masterDb = new PostgreSqlDatabase($pdo);
```

Now you can make SQL queries to the database, for example:
```php
// Raw SQL
$userRows = $masterDb->select('SELECT * FROM users');

// UserTable's method
$userTable = new UserTable($masterDb);
$userRows = $userTable->getRows([]);


// Raw SQL
$userRow = $masterDb->select('SELECT * FROM users WHERE id = :user_id', ['user_id' => 100]);

// UserTable's method
$userTable = new UserTable($masterDb);
$userRow = $userTable->getRowById(100);


// Raw SQL
$row = $masterDb->selectRow('SELECT COUNT(*) cnt FROM users WHERE group_id = :group_id', ['group_id' => 5]);
echo $row['cnt'];

// UserTable's method
$userTable = new UserTable($masterDb);
$usersCount = $userTable->getCount(['group_id = ?' => 5]);
echo $usersCount;
```

You can make more complex SQL queries, for example:
```php
// Raw SQL
$userRows = $masterDb->select(
    'SELECT * FROM users WHERE group_id IN (:group_ids) AND (created_datetime >= :start_time OR created_datetime < finish_time) AND updated_datetime IS NOT NULL ORDER BY created_datetime ASC'
    ['group_ids' => [5, 6, 12], 'start_time' => '2021-04-01 00:00:00', 'finish_time' => '2021-05-01 00:00:00']
);

// UserTable's method
$userTable = new UserTable($masterDb);
$userRows = $userTable->getRows(
    [
        'group_id IN (?)' => [5, 6, 12],
        'created_datetime >= ? OR created_datetime < ?' => ['2021-04-01 00:00:00', '2021-05-01 00:00:00'],
        'update_datetime IS NOT NULL',
    ],
    ['created_datetime ASC']
);
```

The following trick is suggested for fetching related data in another table:

```php
$userTable = new UserTable($masterDb);
$userRows = $userTable->getRows(['created_datetime >= ?' => '2021-04-01 00:00:00']);

// Now you want get all related rows from groups table
$userGroupIds = array_column($userRows, 'group_id');
$groupTable = new GroupTable($masterDb);
$userGroups = $groupTable->getRowsByIds($userGroupIds);
```

You need only write raw SQL with database class if you want to make SQL with JOIN!

Working with "Model" layer
------------------------
You need to create model class, for example:
```php
class User
{
    private ?int $id;
    public ?string $name = null;
    public string $email;
    public string $createdDatetime;
    public ?string $updatedDatetime = null;

    public function __construct(int $id = null)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

You need to create mapper class, for example:
```php
class UserTableMapper
{
    public static function populateModel(?array $row): ?User
    {
        if ($row === null) {
            return null;
        }

        $user = new User($row['id']);

        $user->name            = $row['name'];
        $user->email           = $row['email'];
        $user->createdDatetime = $row['created_datetime'];
        $user->updatedDatetime = $row['updated_datetime'];

        return $user;
    }

    public static function fillRowFromModel(User $user): array
    {
        return [
            'id'               => $user->getId(),
            'name'             => $user->name,
            'email'            => $user->email,
            'created_datetime' => $user->createdDatetime,
            'updated_datetime' => $user->updatedDatetime,
        ];
    }

    /**
     * @return User[]
     */
    public static function populateModels(array $rows): array
    {
        $models = [];
        foreach ($rows as $row) {
            $models[] = static::populateModel($row);
        }
        return $models;
    }
}
```

You need to add new methods in your UserTable class, for example:
```php
class UserTable extends PostgreSqlDatabaseTable
{
    public static function getPrimaryKeyName(): string
    {
        return 'id';
    }

    public static function getTableName(): string
    {
        return 'users';
    }

    /**
     * @throws SqlQueryException
     */
    public function getModelById(int $id): ?User
    {
        return UserTableMapper::populateModel(parent::getRowById($id));
    }

    /**
     * @return User[]
     * @throws SqlQueryException
     */
    public function getModelsByIds(array $ids): array
    {
        return UserTableMapper::populateModels(parent::getRowsByIds($ids));
    }

    /**
     * @throws SqlQueryException
     */
    public function getModel(array $where, ?array $order = null): ?User
    {
        return UserTableMapper::populateModel(static::getRow($where, $order));
    }

    /**
     * @return User[]
     * @throws SqlQueryException
     */
    public function getModels(array $where, ?array $order = null, ?int $limit = null, ?int $offset = null): array
    {
        return UserTableMapper::populateModels(static::getRows($where, $order, $limit, $offset));
    }

    /**
     * @throws SqlQueryException
     */
    public function save(User $model): User
    {
        $row = static::saveRow(UserTableMapper::fillRowFromModel($model));
        return UserTableMapper::populateModel($row);
    }

    public function saveRow(array $row): array
    {
        $date = date('Y-m-d H:i:s');

        $row['created_datetime'] = $row['created_datetime'] ?? $date;
        $row['updated_datetime'] = $date;

        return parent::saveRow($row);
    }
}
```

Now you can make queries to the table in the same way as on the "row" layer, but using "getModels" methods instead of "getRows":
```php
// Selecting
$user  = $userTable->getModelById(100);
$users = $userTable->getModels(['created_datetime >= ?' => '2021-04-01 00:00:00']);


// Saving
$unsavedUser = new User();
$unsavedUser->groupId = 10;
$unsavedUser->email = 'test@test.local';

// "Save" method returns other instance of User in our example.
$user = $userTable->save($unsavedUser);
```

Copyright
---------

Copyright (c) 2021 Vladimir Lila. See LICENSE for details.