<?php

namespace Tests\Unit\Chat;

use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\AssistantsContract;
use OpenAI\Contracts\Resources\AudioContract;
use OpenAI\Contracts\Resources\BatchesContract;
use OpenAI\Contracts\Resources\ChatContract;
use OpenAI\Contracts\Resources\CompletionsContract;
use OpenAI\Contracts\Resources\EditsContract;
use OpenAI\Contracts\Resources\EmbeddingsContract;
use OpenAI\Contracts\Resources\FilesContract;
use OpenAI\Contracts\Resources\FineTunesContract;
use OpenAI\Contracts\Resources\FineTuningContract;
use OpenAI\Contracts\Resources\ImagesContract;
use OpenAI\Contracts\Resources\ModelsContract;
use OpenAI\Contracts\Resources\ModerationsContract;
use OpenAI\Contracts\Resources\RealtimeContract;
use OpenAI\Contracts\Resources\ResponsesContract;
use OpenAI\Contracts\Resources\ThreadsContract;
use OpenAI\Contracts\Resources\VectorStoresContract;

class MockOpenAIClient implements ClientContract
{
    public function completions(): CompletionsContract
    {
        // TODO: Implement completions() method.
    }

    public function chat(): ChatContract
    {
        return new MockOpenAIChat();
    }

    public function responses(): ResponsesContract
    {
        // TODO: Implement responses() method.
    }

    public function embeddings(): EmbeddingsContract
    {
        return new MockOpenAIEmbeddings();
    }

    public function audio(): AudioContract
    {
        // TODO: Implement audio() method.
    }

    public function edits(): EditsContract
    {
        // TODO: Implement edits() method.
    }

    public function files(): FilesContract
    {
        // TODO: Implement files() method.
    }

    public function models(): ModelsContract
    {
        // TODO: Implement models() method.
    }

    public function fineTuning(): FineTuningContract
    {
        // TODO: Implement fineTuning() method.
    }

    public function fineTunes(): FineTunesContract
    {
        // TODO: Implement fineTunes() method.
    }

    public function moderations(): ModerationsContract
    {
        // TODO: Implement moderations() method.
    }

    public function images(): ImagesContract
    {
        // TODO: Implement images() method.
    }

    public function assistants(): AssistantsContract
    {
        // TODO: Implement assistants() method.
    }

    public function threads(): ThreadsContract
    {
        // TODO: Implement threads() method.
    }

    public function batches(): BatchesContract
    {
        // TODO: Implement batches() method.
    }

    public function vectorStores(): VectorStoresContract
    {
        // TODO: Implement vectorStores() method.
    }

    public function realtime(): RealtimeContract
    {
        // TODO: Implement realtime() method.
    }
}
