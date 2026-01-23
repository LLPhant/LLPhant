<?php

namespace App\Controller;

use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3LargeEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Doctrine\ODM\DoctrineODMVectorStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;

class ListingsController extends AbstractController
{
    private EmbeddingGeneratorInterface $embeddingGenerator;

    public function __construct(private readonly DoctrineODMVectorStore $store)
    {
        $this->embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();
    }

    #[Route('/', name: 'app_listings')]
    public function listings(Request $request): Response
    {
        $builder = $this->createFormBuilder();

        $form = $builder
            ->setMethod('GET')
            ->add('query', TextType::class,
                [
                    'constraints' => new Assert\NotBlank(),
                    'label' => 'Enter a query',
                ]
            )
            ->add('type', ChoiceType::class,
                [
                    'label' => 'Search by',
                    'choices' => [
                        'Title' => 'title',
                        'Amenities' => 'amenities',
                    ]
                ]
            )
            ->getForm();

        $form->handleRequest($request);
        $results = $this->handleForm($form);

        return $this->render('/index.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    private function handleForm(FormInterface $form): array
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            return [];
        }

        $query = $form->get('query')->getData();
        $type = $form->get('type')->getData();
        $embeddedQuery = $this->embeddingGenerator->embedText($query);

        return $this->store->similaritySearch(
            $embeddedQuery,
            5,
            ['path' => $type === 'title' ? 'embedding' : 'amenitiesVector']
        );
    }
}
