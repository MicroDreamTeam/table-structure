<?php

namespace Itwmw\Table\Structure\Mysql;

class Column
{
    /** @var string|null  */
    public $type = null;

    /** @var string|null  */
    public $field = null;

    /** @var int|null  */
    public $length = null;

    /** @var int|null  */
    public $precision = null;

    /** @var bool  */
    public $unsigned = false;

    /** @var mixed|null  */
    public $default = null;

    /** @var bool  */
    public $notNull = false;

    /** @var string|null  */
    public $comment = null;

    /** @var array  */
    public $options = [];

    public function __construct(array $data)
    {
        $this->setParam($data);
    }

    public function setParam(array $data)
    {
        foreach (get_class_vars(get_class($this)) as $name => $value) {
            $this->$name = $data[$this->unCamelize($name)] ?? $value;
        }
    }

    protected function unCamelize(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . '_' . '$2', $name));
    }
}
