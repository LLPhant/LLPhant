<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LLPhant\Classification\Enums\ChatRole;
use LLPhant\Classification\Message;
use LLPhant\Classification\OpenAIChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingFormatter\EmbeddingFormatter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAIADA002EmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Chat extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): StreamedResponse
    {
        $filesVectorStore = new FileSystemVectorStore();
        $embeddingGenerator = new OpenAIADA002EmbeddingGenerator();

        if (! $filesVectorStore->isEmpty()) {
            $dataReader = new FileDataReader(__DIR__.'/The_Star_H.G_Wells.txt');
            $documents = $dataReader->getDocuments();
            $splittedDocuments = DocumentSplitter::splitDocuments($documents, 2000);
            $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splittedDocuments);

            $embeddedDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);
            $filesVectorStore->addDocuments($embeddedDocuments);
        }

        $qa = new QuestionAnswering(
            $filesVectorStore,
            $embeddingGenerator,
            new OpenAIChat()
        );
        /** @var Message[] $messages */
        $bodyContent = $request->getContent();

        $data = json_decode($bodyContent);

        $messages = [];
        foreach ($data->messages as $value) {
            $message = new Message();
            $message->content = $value->content;
            $message->role = ChatRole::from($value->role);
            $messages[] = $message;
        }

        return $qa->answerQuestionFromChat($messages);
    }
}
