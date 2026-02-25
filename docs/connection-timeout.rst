Connection timeout
==================

Sometimes you may run into a timeout when sending requests to the OpenAI or Ollama API.

If you use the default HTTP client provided by the bundle (GuzzleHttp), you can set the timeout by defining the
``timeout`` property of the ``OpenAIConfig`` or the ``OllamaConfig`` objects. The value should be a float representing
the total request timeout in seconds.
Use 0 to wait indefinitely (which is the default behavior).


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
