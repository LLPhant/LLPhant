Quickstart for VectorSearch using LLPhant and Doctrine ODM
==========================================================

This quickstart example demonstrates how to use LLPhant's `DoctrineODMVectorStore` to perform vector search operations 
with a MongoDB database and the Doctrine ODM. It's a simple web application built with Symfony that allows similarity 
search over two fields: `content` and `amenities`, as MongoDB allows for multiple vector embeddings per document.

### Prerequisites
To run this example, you'll need:
- The Symfony CLI installed. You can find installation instructions [here](https://symfony.com/download).
- An OpenAI API key. You can get one by signing up at [OpenAI](https://platform.openai.com/signup).
- A [MongoDB Atlas](https://www.mongodb.com/docs/atlas/getting-started/) cluster. You can run a local cluster using [the Docker image](https://hub.docker.com/r/mongodb/mongodb-atlas-local).

### Configure environment variables
- create a copy of the `.env.example` file and rename it to `.env`
- set the value of `OPENAI_API_KEY` to your OpenAI API key
- set the value of `MONGODB_URI` to your MongoDB Atlas connection string

### Install dependencies
```bash
composer install
```

### Load fixtures
This example uses data from the sample CSV set in `data/Listings.csv`. To load the data into your MongoDB database, run the following command:
```bash
bin/console doctrine:mongodb:fixtures:load
```

This will create the `Listing` collection, the vector index, and load the embedded sample data.

### Run the example
```bash
symfony serve
```

- open [http://localhost:8000](http://localhost:8000) in your browser to see the VectorSearch example 🎉
