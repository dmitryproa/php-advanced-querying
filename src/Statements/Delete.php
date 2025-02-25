<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Statement;
use DmitryProA\PhpAdvancedQuerying\Table;

/**
 * @method Delete setCondition(Condition $cond)
 */
class Delete extends Statement
{
    use ConditionalStatement;

    /**
     * @param null|Table $table
     */
    public function __construct($table = null)
    {
        $this->setTable($table);
    }
}
