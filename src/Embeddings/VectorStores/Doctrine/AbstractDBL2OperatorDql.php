<?php

namespace LLPhant\Embeddings\VectorStores\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

abstract class AbstractDBL2OperatorDql extends FunctionNode
{
    private Node|string $vectorTwo;

    private Node|string $vectorOne;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->vectorOne = $parser->ArithmeticFactor();

        $parser->match(TokenType::T_COMMA);

        $this->vectorTwo = $parser->ArithmeticFactor();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function dispatchVectorOne(SqlWalker $walker): string
    {
        if ($this->vectorOne instanceof Node) {
            return $this->vectorOne->dispatch($walker);
        }

        return $this->vectorOne;
    }

    public function dispatchVectorTwo(SqlWalker $walker): string
    {
        if ($this->vectorTwo instanceof Node) {
            return $this->vectorTwo->dispatch($walker);
        }

        return $this->vectorTwo;
    }
}
