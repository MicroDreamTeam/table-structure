<?php

namespace Itwmw\Table\Structure\Mysql;

use PDO;

class Mysql
{
    public function listTableColumns(string $table): array
    {
        $dbName       = MysqlConnection::getDbname();
        $tableColumns = MysqlConnection::connection()->query(
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
            $columns[] = $this->getTableFieldDefinition($tableColumn);
        }
        return $columns;
    }

    public function getDoctrineColumn(string $table, string $column)
    {
        $dbName      = MysqlConnection::getDbname();
        $tableColumn = MysqlConnection::connection()->query(
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
        return $this->getTableFieldDefinition($tableColumn);
    }

    protected function getTableFieldDefinition(array $tableColumn): Column
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');
        assert(is_string($dbType));

        $length = $tableColumn['length'] ?? strtok('(), ');

        if (! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

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
                    $options = array_map(function ($value) {
                        return substr($value, 1, strlen($value) - 2);
                    }, explode(',', $match[1]));
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

        $options = [
            'type'      => $dbType,
            'field'     => $tableColumn['field'],
            'length'    => null !== $length ? (int) $length : null,
            'unsigned'  => false !== strpos($tableColumn['type'], 'unsigned'),
            'default'   => $tableColumn['default'],
            'notNull'   => 'YES' !== $tableColumn['null'],
            'options'   => $options ?: [],
            'precision' => null,
            'comment'   => isset($tableColumn['comment']) && '' !== $tableColumn['comment']
                ? $tableColumn['comment']
                : null,
        ];

        if (null !== $precision) {
            $options['precision'] = (int) $precision;
        }

        return new Column($options);
    }
}
