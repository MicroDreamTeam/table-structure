<?php

namespace Itwmw\Table\Structure\Mysql;

class Column
{
    public function __construct(
        public ?string $type = null,
        public ?string $field = null,
        public ?int $length = null,
        public ?int $precision = null,
        public bool $unsigned = false,
        public mixed $default = null,
        public bool $notNull = false,
        public ?string $comment = null,
        public array $options = []
    )
    {
    }
}
