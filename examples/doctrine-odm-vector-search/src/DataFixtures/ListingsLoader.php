<?php

namespace App\DataFixtures;

use App\Document\Listing;
use Doctrine\Bundle\MongoDBBundle\Fixture\ODMFixtureInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3LargeEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMVectorStore;

class ListingsLoader implements ODMFixtureInterface
{
    const MAX_LISTINGS = 100;

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof DocumentManager) {
            throw new \InvalidArgumentException('Expected a DocumentManager instance.');
        }

        $embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();

        $row = -1;
        $listings = [];

        if (($handle = fopen(__DIR__ . "/../../data/Listings.csv", "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false && $row < self::MAX_LISTINGS) {
                $row++;

                if ($row === 0) {
                    continue;
                }

                $amenities =json_decode($data[21], true)  ?? [];
                $listings[] = new Listing(
                    content: $data[1],
                    amenities: $amenities,
                    amenitiesVector: $embeddingGenerator->embedText(join(', ', $amenities)),
                    published: mt_rand(1, 100) <= 90
                );
            }
            fclose($handle);
        }

        $embeddedListings = $embeddingGenerator->embedDocuments($listings);
        $store = new DoctrineODMVectorStore($manager, Listing::class);

        $store->addDocuments($embeddedListings);
    }
}
