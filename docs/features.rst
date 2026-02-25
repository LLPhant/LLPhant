Features
========

Comparison Table of all supported Language Models
--------------------------------------------------

.. list-table::
   :header-rows: 1
   :widths: 30 15 15 15 15 15 15

   * - Model
     - Text
     - Streaming
     - Tools
     - Images input
     - Images output
     - Speech to text
   * - Anthropic
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
   * - Mistral
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
   * - LM Studio
     - ✅
     - ✅
     - ℹ️
     - ℹ️
     - ❌
     - ❌
   * - Ollama
     - ✅
     - ✅
     - ℹ️
     - ℹ️
     - ❌
     - ❌
   * - OpenAI
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - Gemini (via OpenAI API)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - VoyageAI (via OpenAI API)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅

ℹ️ - Some models of the provider support this feature, but not all. Please check the documentation of the provider for more details.

Supported Vector Stores
------------------------

.. list-table::
   :header-rows: 1

   * - Store
   * - AstraDB
   * - Chroma
   * - PostgreSQL (via Doctrine)
   * - ElasticSearch
   * - Local File System
   * - MariaDB (via Doctrine)
   * - Memory
   * - Milvus
   * - MongoDB
   * - Qdrant
   * - OpenSearch
   * - Redis
   * - Typesense

Supported embedding generators
-------------------------------

.. list-table::
   :header-rows: 1
   :widths: 50 50

   * - API - model
     - Vector length
   * - Mistral
     - 1024
   * - LM Studio
     - model-dependent
   * - Ollama
     - model-dependent
   * - OpenAI - small
     - 1536
   * - OpenAI - large
     - 3072
   * - OpenAI - ADA
     - 1536
   * - VoyageAI
     - model-dependent


.. role:: raw-html(raw)
   :format: html

.. include:: footer.rst
