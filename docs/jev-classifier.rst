JevClassifier
=============

The ``JevClassifier`` provides typed classification over a state (usually text)
using TypeSafe's Jev API. You define one or more questions and get structured
answers back with stable PHP value objects.

For the full HTTP API reference, see `TypeSafe API docs <https://docs.typesafe.ai/api>`_.

Authentication (JEV_API_KEY)
----------------------------

By default, ``JevClassifier`` reads the API key from the ``JEV_API_KEY``
environment variable.

.. code-block:: bash

    export JEV_API_KEY=your_api_key

If needed, you can pass the key explicitly with ``JevConfig`` instead of using
environment variables.

Supported question types
------------------------

- ``NoulType``: yes/no probability (0 to 1).
- ``ChoiceType``: one selected label plus probabilities for every label.
- ``ScoreType``: graded evaluation across ordered criteria levels.

The new ``ScoreType`` support lets you model rubric-based classifications such
as sentiment intensity, urgency level, or frustration score.

Basic usage
-----------

.. code-block:: php

    use LLPhant\Classification\JevClassifier;
    use LLPhant\Classification\JevConfig;
    use LLPhant\Classification\NoulType;
    use LLPhant\Classification\ChoiceType;
    use LLPhant\Classification\ScoreType;
    use LLPhant\Classification\ScoreCriteria;

    $classifier = new JevClassifier();
    // Or: $classifier = new JevClassifier(new JevConfig(apiKey: 'your_api_key'));

    $questions = [
        'is_urgent' => new NoulType('Does this convey urgency?'),
        'department' => new ChoiceType(
            'Which team should handle this?',
            [
                'billing' => 'Payments, invoicing, refunds',
                'technical' => 'Bugs, outages, integrations',
                'sales' => 'Pricing, upgrades, new accounts',
            ]
        ),
        'frustration' => new ScoreType(
            'How frustrated is the customer?',
            new ScoreCriteria(['Calm', 'Frustrated', 'Very angry'])
        ),
    ];

    $answers = $classifier->askQuestions(
        'Help! My payouts have been failing for 3 days.',
        $questions
    );

    // $answers['is_urgent']   => NoulAnswer
    // $answers['department']  => ChoiceAnswer
    // $answers['frustration'] => ScoreAnswer

Answer token usage
------------------

Every ``Answer`` object (``NoulAnswer``, ``ChoiceAnswer``, ``ScoreAnswer``)
includes usage metadata populated from Jev API responses:

- ``inputTokens``: request input token count
- ``outputTokens``: model output token count

These values come from the top-level ``usage`` field of the Jev response and
are copied into each returned answer instance.

Score answers
-------------

``ScoreAnswer`` contains:

- ``score``: probability-weighted numeric score across levels.
- ``legend``: map from level index (as string) to human-readable level text.
- ``probabilities``: probability distribution for each level.
- ``confidence``: confidence value from 0 to 1.

This allows both a single numeric score and full explainability through level
probabilities.


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
