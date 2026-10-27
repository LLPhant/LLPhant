Guardrails
==========

Guardrails are lightweight, programmable checkpoints that sit between application and the LLM.
After each model response they run an evaluator of your choice (e.g. JSON-syntax checker, "no fallback" detector).
Based on the result, either pass the answer through, retry the call, block it, or route it to a custom callback.

.. code-block:: php

    $llm = new OpenAIChat();

    $guardrails = new Guardrails(llm: $llm);
    $guardrails->addStrategy(new JSONFormatEvaluator(), GuardrailStrategy::STRATEGY_RETRY);

    $response = $guardrails->generateText('generate answer in JSON format with object that consists of "correctKey" as a key and "correctVal" as a value');

result without retry:

.. code-block:: json

    {some invalid JSON}

result after retry:

.. code-block:: json

    {
        "correctKey":"correctVal"
    }

use multiple guardrails evaluators

.. code-block:: php

    $llm = new OpenAIChat();

   $guardrails = new Guardrails(llm: $llm);

    $guardrails->addStrategy(
            evaluator: new NoFallbackAnswerEvaluator(),
            strategy: GuardrailStrategy::STRATEGY_BLOCK
        )->addStrategy(
            evaluator: (new WordLimitEvaluator())->setWordLimit(1),
            strategy: GuardrailStrategy::STRATEGY_BLOCK,
            defaultMessage: "I'm unable to answer your question right now."
        );

    $response = $guardrails->generateText('some prompt message');


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
