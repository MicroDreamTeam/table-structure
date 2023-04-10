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

    protected string $unix_socket;

    protected array $options = [];

    protected ?PDO $pdo = null;

    public function __construct(array $params = [])
    {
        $this->setConnection($params);
    }

    public function setConnection(array $params = []): static
    {
        $this->username    = $params['username']    ?? 'root';
        $this->password    = $params['password']    ?? '';
        $this->host        = $params['host']        ?? '127.0.0.1';
        $this->port        = $params['port']        ?? '3306';
        $this->charset     = $params['charset']     ?? 'utf8';
        $this->dbname      = $params['database']    ?? '';
        $this->prefix      = $params['prefix']      ?? '';
        $this->options     = $params['options']     ?? [];
        $this->unix_socket = $params['unix_socket'] ?? '';
        if (!empty($params['url'])) {
            $this->buildDsnFromUrl($params['url']);
        }
        $this->pdo = null;

        return $this;
    }
    private function buildDsnFromUrl(string $dsnOrUrl): void
    {
        // (pdo_)?sqlite3?:///... => (pdo_)?sqlite3?://localhost/... or else the URL will be invalid
        $url = preg_replace('#^((?:pdo_)?sqlite3?):///#', '$1://localhost/', $dsnOrUrl);

        $params = parse_url($url);

        if (false === $params) {
            return; // If the URL is not valid, let's assume it might be a DSN already.
        }

        $params = array_map('rawurldecode', $params);

        // Override the default username and password. Values passed through options will still win over these in the constructor.
        if (isset($params['user'])) {
            $this->username = $params['user'];
        }

        if (isset($params['pass'])) {
            $this->password = $params['pass'];
        }

        if (!isset($params['scheme'])) {
            throw new \InvalidArgumentException('URLs without scheme are not supported to configure the PdoSessionHandler.');
        }

        $driverAliasMap = [
            'mssql'      => 'sqlsrv',
            'mysql2'     => 'mysql', // Amazon RDS, for some weird reason
            'postgres'   => 'pgsql',
            'postgresql' => 'pgsql',
            'sqlite3'    => 'sqlite',
        ];

        $driver = $driverAliasMap[$params['scheme']] ?? $params['scheme'];

        // Doctrine DBAL supports passing its internal pdo_* driver names directly too (allowing both dashes and underscores). This allows supporting the same here.
        if (str_starts_with($driver, 'pdo_') || str_starts_with($driver, 'pdo-')) {
            $driver = substr($driver, 4);
        }

        if ('mysql' !== $driver) {
            throw new \InvalidArgumentException('Database types other than Mysql are not supported');
        }

        if ('' !== ($params['query'] ?? '')) {
            $queryParams = [];
            parse_str($params['query'], $queryParams);
            if ('' !== ($queryParams['charset'] ?? '')) {
                $this->charset = $queryParams['charset'];
            }

            if ('' !== ($queryParams['unix_socket'] ?? '')) {
                $this->unix_socket = $queryParams['unix_socket'];

                if (isset($params['path'])) {
                    $dbName       = substr($params['path'], 1); // Remove the leading slash
                    $this->dbname = $dbName;
                }
            }
        }
    }
    public function getPdo(): PDO
    {
        if (null === $this->pdo) {
            $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';charset=' . $this->charset . ';dbname=' . $this->dbname;

            if (!empty($this->unix_socket)) {
                $dsn .= ';unix_socket=' . $this->unix_socket;
            }

            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->charset
            ] + $this->options);

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
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
