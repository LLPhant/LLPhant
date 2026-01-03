# Comparison Table of all supported Language Models

| Model                     | Text | Streaming |    Tools    | Images input | Images output | Speech to text |
|---------------------------|:----:|:---------:|:-----------:|:------------:|:-------------:|:--------------:|
| Anthropic                 |  ✅   |     ✅     |      ✅      |      ✅       |               |                |
| Mistral                   |  ✅   |     ✅     |      ✅      |              |               |                |
| LM Studio                 |  ✅   |     ✅     | Some models | Some models  |               |                |
| Ollama                    |  ✅   |     ✅     | Some models | Some models  |               |                |
| OpenAI                    |  ✅   |     ✅     |      ✅      |      ✅       |       ✅       |       ✅        |
| Gemini (via OpenAI API)   |  ✅   |     ✅     |      ✅      |      ✅       |       ✅       |       ✅        |
| VoyageAI (via OpenAI API) |  ✅   |     ✅     |      ✅      |      ✅       |       ✅       |       ✅        |

# Supported Vector Stores

| Store                     |
|---------------------------|
| AstraDB                   |
| Chroma                    |
| PostgreSQL (via Doctrine) |
| ElasticSearch             |
| Local File System         |
| MariaDB (via Doctrine)    |
| Memory                    |
| Milvus                    |
| MongoDB                   |
| Qdrant                    |
| OpenSearch                |
| Redis                     |
| Typesense                 |

# Supported embedding generators

| API - model    |  Vector length  |
|----------------|:---------------:|
| Mistral        |      1024       |
| LM Studio      | model-dependent |
| Ollama         | model-dependent |
| OpenAI - small |      1536       |
| OpenAI - large |      3072       |
| OpenAI - ADA   |      1536       |
| VoyageAI       | model-dependent |
