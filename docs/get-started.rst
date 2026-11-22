Get Started
===========

.. note::
   **Requires** `PHP 8.1+ <https://php.net/releases/>`_

First, install LLPhant via the `Composer <https://getcomposer.org/>`_ package manager:

.. code-block:: bash

   composer require theodo-group/llphant

In case you have not installed the GD extension, and you do not want to add it to your PHP setup,
you can use the ``--ignore-platform-req=ext-gd`` option

.. code-block:: bash

   composer require theodo-group/llphant --ignore-platform-req=ext-gd

If you want to try the latest features of this library, you can use:

.. code-block:: bash

   composer require theodo-group/llphant:dev-main

You may also want to check the requirements for `OpenAI PHP SDK <https://github.com/openai-php/client>`_ as it is the main client.


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
