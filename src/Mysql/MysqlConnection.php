<?php

namespace Itwmw\Table\Structure\Mysql;

use PDO;

/**
 * Class MysqlConnection
 *
 * @method static string getUsername()
 * @method static string getPassword()
 * @method static string getHost()
 * @method static string getPort()
 * @method static string getCharset()
 * @method static string getDbname()
 * @method static string getPrefix()
 * @package Itwmw\Validate\Table\Mysql\Drive
 */
class MysqlConnection
{
    /** @var string */
    protected static $username;

    /** @var string */
    protected static $password;

    /** @var string */
    protected static $host;

    /** @var int */
    protected static $port;

    /** @var string */
    protected static $charset;

    /** @var string */
    protected static $dbname;

    /** @var static */
    protected static $instance;

    protected static $prefix;

    /** @var PDO */
    protected static $pdo = null;

    public static function setConnection(array $params)
    {
        self::$username = $params['username'] ?? 'root';
        self::$password = $params['password'] ?? '';
        self::$host     = $params['host']     ?? '127.0.0.1';
        self::$port     = $params['port']     ?? '3306';
        self::$charset  = $params['charset']  ?? 'utf8';
        self::$dbname   = $params['database'] ?? '';
        self::$dbname   = $params['database'] ?? '';
        self::$prefix   = $params['prefix'] ?? '';
        self::$pdo      = null;
    }

    public static function connection(): PDO
    {
        if (null === self::$pdo) {
            $dsn = 'mysql:host=' . self::$host . ';port=' . self::$port . ';charset=' . self::$charset . ';dbname=' . self::$dbname;

            self::$pdo = new PDO($dsn, self::$username, self::$password, [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . self::$charset
            ]);

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }
        
        return self::$pdo;
    }

    public static function instance(): MysqlConnection
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }

        /** @var static */
        return self::$instance;
    }

    public static function __callStatic($name, $arguments)
    {
        if ('get' === substr($name, 0, 3)) {
            $name = lcfirst(substr($name, 3));
            return self::$$name;
        }
    }
}
