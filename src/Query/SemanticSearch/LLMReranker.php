<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\ChatInterface;
use LLPhant\Embeddings\Document;

class LLMReranker implements RetrievedDocumentsTransformer
{
    /**
     * @var string
     */
    private const SYSTEM_MESSAGE = <<<'TEXT'
        Your task is to sort documents by relevance in relation to a set of questions. You will receive a list of questions and a list of documents
        labeled with a number. You must return a list of such numbers sorted from the most relevant document to the least one.

        Example input format:

        Question: <text of first query>
        Question: <text of second question>
        Document 1: <text of first document>
        Document 2: <text of second document>
        Document 3: <text of third document>

        Example output format (YOU MUST RETURN THE ANSWER IN THE FORM PROVIDED BY THE FOLLOWING EXAMPLE WITHOUT CHANGING ANYTHING BESIDES THE NUMBERS!)

        Relevance order: 3, 1, 2
        TEXT;

    public function __construct(private readonly ChatInterface $chat, private readonly int $nrOfOutputDocuments)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function transformDocuments(array $questions, array $retrievedDocs): array
    {
        $this->chat->setSystemMessage(self::SYSTEM_MESSAGE);

        $answer = $this->chat->generateText($this->formatQuestionsAndDocuments($questions, $retrievedDocs));

        return $this->sortArrayByRelevanceOrder($answer, $retrievedDocs);
    }

    /**
     * @param  string[]  $questions
     * @param  array<int, Document>  $documents
     */
    private function formatQuestionsAndDocuments(array $questions, array $documents): string
    {
        $output = '';

        foreach ($questions as $query) {
            $output .= "Question: {$query}".\PHP_EOL;
        }

        foreach ($documents as $index => $document) {
            $output .= 'Document '.($index + 1).": {$document->content}".\PHP_EOL;
        }

        return $output;
    }

    /**
     * @param  array<int, Document>  $documents
     * @return array<int, Document>
     */
    private function sortArrayByRelevanceOrder(string $inputString, array $documents): array
    {
        preg_match('/Relevance order:\s*(.*)/', $inputString, $matches);
        if (\count($matches) !== 2) {
            throw new \Exception('Invalid input format. Expected "Relevance order: <numbers>"');
        }
        $relevanceOrder = array_map('intval', explode(', ', $matches[1]));

        if (count($relevanceOrder) !== count($documents)) {
            throw new \Exception('The relevance order does not match the input array size.');
        }

        $mappedArray = [];
        foreach ($relevanceOrder as $position) {
            $adjustedPosition = $position - 1;
            if (isset($documents[$adjustedPosition])) {
                $mappedArray[] = $documents[$adjustedPosition];
            } else {
                throw new \Exception("Invalid position '$position' in relevance order.");
            }
            if (count($mappedArray) >= $this->nrOfOutputDocuments) {
                break;
            }
        }

        return $mappedArray;
    }
}
