<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\ChatInterface;

class MultiQuery implements QueryTransformer
{
    private readonly string $prompt;

    public function __construct(public readonly ChatInterface $chat, int $nrOfDifferentQueries = 3)
    {
        // This prompt comes from LangChain: https://github.com/langchain-ai/langchain/blob/60d025b83be4d4f884c67819904383ccd89cff87/libs/langchain/langchain/retrievers/multi_query.py#L38
        $this->prompt = <<<END
        You are an AI language model assistant. Your task is to generate {$nrOfDifferentQueries} different versions
        of the given user question to retrieve relevant documents from a vector database.
        By generating multiple perspectives on the user question, your goal is to help the user overcome some of the limitations
        of distance-based similarity search. Provide these alternative questions separated by newlines without numbering them.
        END;
    }

    /**
     * @return string[]
     */
    public function transformQuery(string $query): array
    {
        $this->chat->setSystemMessage($this->prompt);
        $response = $this->chat->generateText($query);

        $splitStrings = explode("\n", $response);

        return [$query, ...$splitStrings];
    }
}
