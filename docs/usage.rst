Usage
=====

You can use OpenAI, Mistral, Ollama or Anthropic as LLM engines. Here you can find a list of `supported features for each AI engine </docusaurus/docs/features.md>`_.

OpenAI
------

The most simple way to allow the call to OpenAI is to set the OPENAI_API_KEY environment variable.

.. code-block:: bash

    export OPENAI_API_KEY=sk-XXXXXX

You can also create an OpenAIConfig object and pass it to the constructor of the OpenAIChat or OpenAIEmbeddings.

.. code-block:: php

    $config = new OpenAIConfig();
    $config->apiKey = 'fakeapikey';
    $chat = new OpenAIChat($config);

Gemini
------

We support Gemini through its OpenAI API compatibility. Here is an example:

.. code-block:: php

    $config = new GeminiOpenAIConfig();
    $config->apiKey = "your_api_key"
    $config->model = 'gemini-2.0-flash';
    $chat = new OpenAIChat($config);
    $response = $chat->generateText('what is one + one ?');

If you do not specify an api key, the ``GeminiOpenAIConfig`` tries to read it from the ``GEMINI_API_KEY`` environment variable.

Mistral
-------

If you want to use Mistral, you can just specify the model to use using the ``MistralAIConfig`` object and pass it to the ``MistralAIChat``.
**Note that since version 0.11.0 the usage of ``MistralAIConfig`` instead of ``OpenAIConfig`` is mandatory**

.. code-block:: php

    $config = new MistralAIConfig();
    $config->apiKey = 'fakeapikey';
    $chat = new MistralAIChat($config);

Ollama
------

If you want to use Ollama, you can just specify the model to use using the ``OllamaConfig`` object and pass it to the ``OllamaChat``.

.. code-block:: php

    $config = new OllamaConfig();
    $config->model = 'llama2';
    $chat = new OllamaChat($config);

Anthropic
---------

To call Anthropic models you have to provide an API key . You can set the ANTHROPIC_API_KEY environment variable.

.. code-block:: bash

    export ANTHROPIC_API_KEY=XXXXXX

You also have to specify the model to use using the ``AnthropicConfig`` object and pass it to the ``AnthropicChat``.

.. code-block:: php

    $chat = new AnthropicChat(new AnthropicConfig(AnthropicConfig::CLAUDE_3_5_SONNET));

Creating a chat with no configuration will use a CLAUDE_3_HAIKU model.

.. code-block:: php

    $chat = new AnthropicChat();

OpenAI compatible APIs like LocalAI
------------------------------------

The most simple way to allow the call to OpenAI is to set the OPENAI_API_KEY and OPENAI_BASE_URL environment variable.

.. code-block:: bash

    export OPENAI_API_KEY=-
    export OPENAI_BASE_URL=http://localhost:8080/v1

You can also create an OpenAIConfig object and pass it to the constructor of the OpenAIChat or OpenAIEmbeddings.

.. code-block:: php

    $config = new OpenAIConfig();
    $config->apiKey = '-';
    $config->url = 'http://localhost:8080/v1';
    $chat = new OpenAIChat($config);

Here you can find a `docker compose file for running LocalAI <devx/docker-compose-localai.yml>`_ on your machine for development purposes.

Chat
----

.. note::

    This class can be used to generate content, to create a chatbot or to create a text summarizer.

You can use the ``OpenAIChat``, ``MistralAIChat`` or ``OllamaChat`` to generate text or to create a chat.

We can use it to simply generate text from a prompt.
This will ask directly an answer from the LLM.

.. code-block:: php

    $response = $chat->generateText('what is one + one ?'); // will return something like "Two"

If you want to display in your frontend a stream of text like in ChatGPT you can use the following method.

.. code-block:: php

    return $chat->generateStreamOfText('can you write me a poem of 10 lines about life ?');

You can add instruction so the LLM will behave in a specific manner.

.. code-block:: php

    $chat->setSystemMessage('Whatever we ask you, you MUST answer "ok"');
    $response = $chat->generateText('what is one + one ?'); // will return "ok"

Images
------

Reading images
^^^^^^^^^^^^^^

With `OpenAI <tests/Integration/Chat/Vision/OpenVisionChatTest.php>`_ chat you can use images as input for your chat. For example:

.. code-block:: php

    $config = new OpenAIConfig();
    $config->model = 'gpt-4o-mini';
    $chat = new OpenAIChat($config);
    $messages = [
      VisionMessage::fromImages([
        new ImageSource('https://upload.wikimedia.org/wikipedia/commons/thumb/2/2c/Lecco_riflesso.jpg/800px-Lecco_riflesso.jpg'),
        new ImageSource('https://upload.wikimedia.org/wikipedia/commons/thumb/9/9c/Lecco_con_riflessi_all%27alba.jpg/640px-Lecco_con_riflessi_all%27alba.jpg')
      ], 'What is represented in these images?')
    ];
    $response = $chat->generateChat($messages);

Something similar works for `Anthropic <tests/Integration/Chat/AnthropicChatTest.php>`_ too. Here is an example:

.. code-block:: php

    $chat = new AnthropicChat();
    $fileContents = \file_get_contents('/path/to/my/cat_file.jpeg');
    $base64 = \base64_encode($fileContents);
    $messages = [
        new AnthropicVisionMessage([new AnthropicImage(AnthropicImageType::JPEG, $base64)], 'How many cats are there in this image? Answer in words'),
    ];
    $response = $chat->generateChat($messages);

Generating images
^^^^^^^^^^^^^^^^^

You can use the ``OpenAIImage`` to generate image.

We can use it to simply generate image from a prompt.

.. code-block:: php

    $response = $image->generateImage('A cat in the snow', OpenAIImageStyle::Vivid); // will return a LLPhant\Image\Image object

You can also use ``ModelsLabImage`` to generate images via the `ModelsLab API <https://docs.modelslab.com/image-generation/overview>`_, which supports Flux, SDXL, Stable Diffusion, and 10,000+ community fine-tuned models.

Set your API key as an environment variable:

.. code-block:: bash

    export MODELSLAB_API_KEY=your-api-key

Then generate an image from a prompt:

.. code-block:: php

    $image = new ModelsLabImage();          // reads MODELSLAB_API_KEY from env
    $image->model = 'flux';                 // default: 'flux'
    $image->width = 1024;                   // default: 1024
    $image->height = 1024;                  // default: 1024
    $image->numInferenceSteps = 30;         // default: 30
    $image->guidanceScale = 7.5;            // default: 7.5
    $image->negativePrompt = 'blurry';      // optional

    $response = $image->generateImage('A cozy cabin in the woods at dusk, watercolor style');
    echo $response->url;                    // CDN URL of the generated image

Or pass the API key directly:

.. code-block:: php

    $image = new ModelsLabImage(apiKey: 'your-api-key');

Speech to text
--------------

You can use ``OpenAIAudio`` to transcript audio files.

.. code-block:: php

    $audio = new OpenAIAudio();
    $transcription = $audio->transcribe('/path/to/audio.mp3');  //$transcription->text contains transcription

Translate audio into english
-----------------------------

You can use ``OpenAIAudio`` to translate audio files.

.. code-block:: php

    $audio = new OpenAIAudio();
    $translation = $audio->translate('/path/to/audio.mp3');  //$translation->text contains the english translation

Customizing System Messages in Question Answering
--------------------------------------------------

When using the ``QuestionAnswering`` class, it is possible to customize the system message to guide the AI's response style and context sensitivity according to your specific needs. This feature allows you to enhance the interaction between the user and the AI, making it more tailored and responsive to specific scenarios.

Here's how you can set a custom system message:

.. code-block:: php

    use LLPhant\Query\SemanticSearch\QuestionAnswering;

    $qa = new QuestionAnswering($vectorStore, $embeddingGenerator, $chat);

    $customSystemMessage = 'Your are a helpful assistant. Answer with conversational tone. \\n\\n{context}.';

    $qa->systemMessageTemplate = $customSystemMessage;


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
