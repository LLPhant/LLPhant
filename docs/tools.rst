Tools
=====

This feature is amazing, and it is available for OpenAI, Anthropic and Ollama (`just for a subset of its available models <https://ollama.com/blog/tool-support>`_).

OpenAI has refined its model to determine whether tools should be invoked.
To utilize this, simply send a description of the available tools to OpenAI,
either as a single prompt or within a broader conversation.

In the response, the model will provide the called tools names along with the parameter values,
if it deems the one or more tools should be called.

One potential application is to ascertain if a user has additional queries during a support interaction.
Even more impressively, it can automate actions based on user inquiries.

We made it as simple as possible to use this feature.

Let's see an example of how to use it.
Imagine you have a class that send emails.

.. code-block:: php

    class MailerExample
    {
        /**
         * This function send an email
         */
        public function sendMail(string $subject, string $body, string $email): void
        {
            echo 'The email has been sent to '.$email.' with the subject '.$subject.' and the body '.$body.'.';
        }
    }

You can create a FunctionInfo object that will describe your method to OpenAI.
Then you can add it to the OpenAIChat object.
If the response from OpenAI contains a tools' name and parameters, LLPhant will call the tool.

.. image:: assets/function-flow.png
   :alt: Function flow
   :align: center
   :width: 100%

This PHP script will most likely call the sendMail method that we pass to OpenAI.

.. code-block:: php

    $chat = new OpenAIChat();
    // This helper will automatically gather information to describe the tools
    $tool = FunctionBuilder::buildFunctionInfo(new MailerExample(), 'sendMail');
    $chat->addTool($tool);
    $chat->setSystemMessage('You are an AI that deliver information using the email system.
    When you have enough information to answer the question of the user you send a mail');
    $chat->generateText('Who is Marie Curie in one line? My email is student@foo.com');

If you want to have more control about the description of your function, you can build it manually:

.. code-block:: php

    $chat = new OpenAIChat();
    $subject = new Parameter('subject', 'string', 'the subject of the mail');
    $body = new Parameter('body', 'string', 'the body of the mail');
    $email = new Parameter('email', 'string', 'the email address');

    $tool = new FunctionInfo(
        'sendMail',
        new MailerExample(),
        'send a mail',
        [$subject, $body, $email]
    );

    $chat->addTool($tool);
    $chat->setSystemMessage('You are an AI that deliver information using the email system. When you have enough information to answer the question of the user you send a mail');
    $chat->generateText('Who is Marie Curie in one line? My email is student@foo.com');

You can safely use the following types in the Parameter object: string, int, float, bool.
The array type is supported but still experimental.

With ``AnthropicChat`` you can also tell to the LLM engine to use the results of the tool called locally as an input for the next inference.
Here is a simple example. Suppose we have a ``WeatherExample`` class with a ``currentWeatherForLocation`` method that calls an external service to get weather information.
This method gets in input a string describing the location and returns a string with the description of the current weather.

.. code-block:: php

    $chat = new AnthropicChat();
    $location = new Parameter('location', 'string', 'the name of the city, the state or province and the nation');
    $weatherExample = new WeatherExample();

    $function = new FunctionInfo(
        'currentWeatherForLocation',
        $weatherExample,
        'returns the current weather in the given location. The result contains the description of the weather plus the current temperature in Celsius',
        [$location]
    );

    $chat->addFunction($function);
    $chat->setSystemMessage('You are an AI that answers to questions about weather in certain locations by calling external services to get the information');
    $answer = $chat->generateText('What is the weather in Venice?');


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
