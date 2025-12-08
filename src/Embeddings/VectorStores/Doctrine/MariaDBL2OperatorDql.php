<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine;

use Doctrine\ORM\Query\SqlWalker;

/**
 * L2DistanceFunction ::= "VEC_DISTANCE_EUCLIDEAN" "(" VectorPrimary "," VectorPrimary ")"
 */
final class MariaDBL2OperatorDql extends AbstractDBL2OperatorDql
{
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'VEC_DISTANCE_EUCLIDEAN('.
            $this->dispatchVectorOne($sqlWalker).', '.
            'VEC_FROMTEXT('.
            $this->dispatchVectorTwo($sqlWalker).
            ')'.
            ')';
    }
}
