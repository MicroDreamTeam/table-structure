<?php

namespace Itwmw\Table\Structure\Mysql;

use PDO;

class Mysql
{
    public function __construct(protected MysqlConnection $connection)
    {
    }

    public function listTableColumns(string $table): array
    {
        $table        = $this->connection->getPrefix() . $table;
        $dbName       = $this->connection->getDbname();
        $tableColumns = $this->connection->getPdo()->query(
            "SELECT
							COLUMN_NAME AS Field,
							COLUMN_TYPE AS Type,
							IS_NULLABLE AS `Null`,
							COLUMN_DEFAULT AS `Default`,
							COLUMN_COMMENT AS COMMENT,
							CHARACTER_SET_NAME AS CharacterSet,
							COLLATION_NAME AS COLLATION 
						FROM
							information_schema.COLUMNS 
						WHERE
							TABLE_SCHEMA = '$dbName' 
							AND TABLE_NAME = '$table' 
						ORDER BY
							ORDINAL_POSITION ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        if (empty($tableColumns)) {
            return [];
        }

        $columns = [];
        foreach ($tableColumns as $tableColumn) {
            $columns[] = self::getTableFieldDefinition($tableColumn);
        }
        return $columns;
    }

    public function getDoctrineColumn(string $table, string $column): bool|Column
    {
        $table       = $this->connection->getPrefix() . $table;
        $dbName      = $this->connection->getDbname();
        $tableColumn = $this->connection->getPdo()->query(
            "SELECT
							COLUMN_NAME AS Field,
							COLUMN_TYPE AS Type,
							IS_NULLABLE AS `Null`,
							COLUMN_DEFAULT AS `Default`,
							COLUMN_COMMENT AS COMMENT,
							CHARACTER_SET_NAME AS CharacterSet,
							COLLATION_NAME AS COLLATION 
						FROM
							information_schema.COLUMNS 
						WHERE
							COLUMN_NAME = '$column' 
							AND TABLE_SCHEMA = '$dbName' 
							AND TABLE_NAME = '$table' 
						ORDER BY
							ORDINAL_POSITION ASC"
        )->fetch(PDO::FETCH_ASSOC);
        if (empty($tableColumn)) {
            return false;
        }
        return self::getTableFieldDefinition($tableColumn);
    }

    protected static function getTableFieldDefinition(array $tableColumn): Column
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');
        assert(is_string($dbType));

        $length = $tableColumn['length'] ?? strtok('(), ');

        $length    = false === $length ? null : $length;
        $precision = null;
        $options   = null;

        switch ($dbType) {
            case 'float':
            case 'double':
            case 'real':
            case 'numeric':
            case 'decimal':
                if (
                    1 === preg_match(
                        '([A-Za-z]+\(([0-9]+),([0-9]+)\))',
                        $tableColumn['type'],
                        $match
                    )
                ) {
                    $length    = $match[1];
                    $precision = $match[2];
                }

                break;
            case 'enum':
            case 'set':
                if (
                    1 === preg_match(
                        '/\((.*?)\)/',
                        $tableColumn['type'],
                        $match
                    )
                ) {
                    $option  = $match[1];
                    $options = array_reduce(explode(',', $option), function ($c, $i) {
                        if (!str_starts_with($i, "'")) {
                            $i = array_pop($c) . ',' . $i;
                        }
                        $c[] = $i;
                        return $c;
                    }, []);

                    $options = array_map(function ($value) {
                        $value = substr($value, 1, strlen($value) - 2);
                        $value = str_replace("''", "'", $value);
                        return str_replace('\\\\', '\\', $value);
                    }, $options);
                }

                $length = null;
                break;
            default:
                if (
                    1 === preg_match(
                        '(\d+)',
                        $tableColumn['type'],
                        $match
                    )
                ) {
                    $length = $match[0];
                }
                break;
        }

        return new Column(
            type: $dbType,
            field: $tableColumn['field'],
            length: null !== $length ? (int) $length : null,
            precision: $precision === null ? null : (int) $precision,
            unsigned: str_contains($tableColumn['type'], 'unsigned'),
            default: $tableColumn['default'],
            notNull: 'YES' !== $tableColumn['null'],
            comment: isset($tableColumn['comment']) && '' !== $tableColumn['comment']
                ? $tableColumn['comment']
                : null,
            options: $options ?: [],
        );
    }
}
