<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;

use function DmitryProA\PhpAdvancedQuerying\checkType;

class RawExpression extends Expression
{
    /** @var array */
    public $parts;

    /**
     * @param array<Expression|mixed> $parts
     */
    public function __construct(array $parts)
    {
        foreach ($parts as $part) {
            if (is_object($part)) {
                checkType($part, Expression::class);
            }
            if (is_array($part) || is_null($part)) {
                throw new \InvalidArgumentException();
            }
        }
        $this->parts = $parts;
    }
}
