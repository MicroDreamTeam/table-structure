<?php

namespace Itwmw\Table\Structure\Mysql;

use PDO;

/**
 * Class MysqlConnection
 *
 * @method string getUsername()
 * @method string getPassword()
 * @method string getHost()
 * @method string getPort()
 * @method string getCharset()
 * @method string getDbname()
 * @method string getPrefix()
 */
class MysqlConnection
{
    protected string $username;

    protected string $password;

    protected string $host;

    protected int $port;

    protected string $charset;

    protected string $dbname;

    protected static MysqlConnection $instance;

    protected string $prefix;

    protected ?PDO $pdo = null;

    public function __construct(array $params = [])
    {
        $this->setConnection($params);
    }

    public function setConnection(array $params = []): static
    {
        $this->username = $params['username'] ?? 'root';
        $this->password = $params['password'] ?? '';
        $this->host     = $params['host']     ?? '127.0.0.1';
        $this->port     = $params['port']     ?? '3306';
        $this->charset  = $params['charset']  ?? 'utf8';
        $this->dbname   = $params['database'] ?? '';
        $this->prefix   = $params['prefix']   ?? '';
        $this->pdo      = null;
        return $this;
    }

    public function getPdo(): PDO
    {
        if (null === $this->pdo) {
            $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';charset=' . $this->charset . ';dbname=' . $this->dbname;

            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->charset
            ]);

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }

        return $this->pdo;
    }

    public static function instance(): MysqlConnection
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }

        /** @var static */
        return self::$instance;
    }

    public function __call(string $name, array $arguments)
    {
        if (str_starts_with($name, 'get')) {
            $name = lcfirst(substr($name, 3));
            return $this->$name;
        }
    }
}
