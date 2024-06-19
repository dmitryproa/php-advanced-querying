<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;

use function DmitryProA\PhpAdvancedQuerying\makeUpdateValues;

trait InsertStatement
{
    /** @var FieldValue[] */
    protected $onDuplicateUpdateValues = [];
    protected $ignore_ = false;

    /** @param array<ColumnExpression|string,mixed> $values */
    public function onDuplicateKeyUpdate(array $values): self
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Values array cannot be empty');
        }

        $this->ignore_ = false;
        $this->onDuplicateUpdateValues = makeUpdateValues($values);

        return $this;
    }

    public function ignore(): self
    {
        $this->ignore_ = true;

        return $this;
    }

    /** @return FieldValue[] */
    public function getOnDuplicateUpdateValues(): array
    {
        return $this->onDuplicateUpdateValues;
    }

    public function getIgnore(): bool
    {
        return $this->ignore_;
    }
}
