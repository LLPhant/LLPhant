<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine;

use Doctrine\ORM\Query\SqlWalker;

/**
 * L2DistanceFunction ::= "L2_DISTANCE" "(" VectorPrimary "," VectorPrimary ")"
 */
final class PgVectorL2OperatorDql extends AbstractDBL2OperatorDql
{
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'L2_DISTANCE('.
            $this->dispatchVectorOne($sqlWalker).', '.
            $this->dispatchVectorTwo($sqlWalker).
            ')';
    }
}
