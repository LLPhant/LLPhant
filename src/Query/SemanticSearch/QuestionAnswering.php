<?php

namespace LLPhant\Query\SemanticSearch;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Message;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use Psr\Http\Message\StreamInterface;

class QuestionAnswering
{
    /** @var Document[] */
    protected array $retrievedDocs;

    public string $systemMessageTemplate = "Use the following pieces of context to answer the question of the user. If you don't know the answer, just say that you don't know, don't try to make up an answer.\n\n{context}.";

    public function __construct(public readonly VectorStoreBase $vectorStoreBase,
        public readonly EmbeddingGeneratorInterface $embeddingGenerator,
        public readonly ChatInterface $chat,
        private readonly QueryTransformer $queryTransformer = new IdentityTransformer(),
        private readonly RetrievedDocumentsTransformer $retrievedDocumentsTransformer = new IdentityDocumentsTransformer(),
        private readonly ChatSessionInterface $session = new NullChatSession()
    ) {
    }

    /**
     * @param  array<string, string|int>|array<mixed[]>  $additionalArguments
     */
    public function answerQuestion(string $question, int $k = 4, array $additionalArguments = []): string
    {
        $systemMessage = $this->searchDocumentAndCreateSystemMessage($question, $k, $additionalArguments);
        $history = $this->session->getHistoryAsString();
        if ($history !== '' && $history !== '0') {
            $systemMessage .= "\nUse also the conversation history to answer the question:\n".$history;
        }
        $this->chat->setSystemMessage($systemMessage);
        $this->session->addMessage(Message::user($question));

        $answer = $this->chat->generateText($question);
        $this->session->addMessage(Message::assistant($answer));

        return $answer;
    }

    /**
     * @param  array<string, string|int>|array<mixed[]>  $additionalArguments
     */
    public function answerQuestionStream(string $question, int $k = 4, array $additionalArguments = []): StreamInterface
    {
        $systemMessage = $this->searchDocumentAndCreateSystemMessage($question, $k, $additionalArguments);
        $history = $this->session->getHistoryAsString();
        if ($history !== '' && $history !== '0') {
            $systemMessage .= "\nUse also the conversation history to answer the question:\n".$history;
        }
        $this->chat->setSystemMessage($systemMessage);
        $this->session->addMessage(Message::user($question));

        $stream = $this->chat->generateStreamOfText($question);

        return $this->session->wrapAnswerStream($stream);
    }

    /**
     * @param  Message[]  $messages
     * @param  array<string, string|int>|array<mixed[]>  $additionalArguments
     *
     * @phpstan-return ($stream is true ? StreamInterface : string)
     */
    public function answerQuestionFromChat(array $messages, int $k = 4, array $additionalArguments = [], bool $stream = true): string|StreamInterface
    {
        // First we need to give the context to openAI with the good instructions
        $userQuestion = $messages[count($messages) - 1]->content;
        $systemMessage = $this->searchDocumentAndCreateSystemMessage($userQuestion, $k, $additionalArguments);
        $this->chat->setSystemMessage($systemMessage);

        // Then we can just give the conversation

        if ($stream) {
            return $this->chat->generateChatStream($messages);
        }

        return $this->chat->generateChat($messages);
    }

    /**
     * @return Document[]
     */
    public function getRetrievedDocuments(): array
    {
        return $this->retrievedDocs;
    }

    public function getTotalTokens(): int
    {
        if (! method_exists($this->chat, 'getTotalTokens')) {
            $chatClass = $this->chat::class;
            throw new \BadMethodCallException("Method getTotalTokens does not exist on the chat object of class {$chatClass}");
        }

        return $this->chat->getTotalTokens();
    }

    /**
     * @param  array<string, string|int>|array<mixed[]>  $additionalArguments
     */
    private function searchDocumentAndCreateSystemMessage(string $question, int $k, array $additionalArguments): string
    {
        $questions = $this->queryTransformer->transformQuery($question);

        $this->retrievedDocs = [];

        foreach ($questions as $question) {
            $embedding = $this->embeddingGenerator->embedText($question);
            $docs = $this->vectorStoreBase->similaritySearch($embedding, $k, $additionalArguments);
            foreach ($docs as $doc) {
                //md5 is needed for removing duplicates
                $this->retrievedDocs[\md5($doc->content)] = $doc;
            }
        }

        // Ensure retro-compatibility and help in resorting the array
        $this->retrievedDocs = \array_values($this->retrievedDocs);

        $this->retrievedDocs = $this->retrievedDocumentsTransformer->transformDocuments($questions, $this->retrievedDocs);

        $context = '';
        $i = 0;
        foreach ($this->retrievedDocs as $document) {
            if ($i >= $k) {
                break;
            }
            $i++;
            $context .= $document->content.' ';
        }

        return $this->getSystemMessage($context);
    }

    private function getSystemMessage(string $context): string
    {
        return str_replace('{context}', $context, $this->systemMessageTemplate);
    }
}
