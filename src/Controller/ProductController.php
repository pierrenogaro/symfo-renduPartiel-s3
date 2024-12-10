<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', methods: ['GET'])]
    public function index(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $products = $productRepository->findAll();

        $responseData = $serializer->serialize($products, 'json', ['groups' => 'product:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/product/{id}', methods: ['GET'])]
    public function show(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $responseData = $serializer->serialize($product, 'json', ['groups' => 'product:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/api/product/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description']);
        $product->setAuthor($this->getUser());

        $entityManager->persist($product);
        $entityManager->flush();

        $responseData = $serializer->serialize($product, 'json', ['groups' => 'product:read']);

        return new JsonResponse($responseData, 201, [], true);
    }

    #[Route('/api/product/update/{id}', methods: ['PUT'])]
    public function update(Request $request, Product $product, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $product->getAuthor()) {
            return $this->json(['message' => 'Access denied: You are not the author of this product.'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $product->setName($data['name']);
        $product->setDescription($data['description']);

        $entityManager->flush();

        $responseData = $serializer->serialize($product, 'json', ['groups' => 'product:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/api/product/delete/{id}', methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $product->getAuthor()) {
            return $this->json(['message' => 'Access denied: You are not the author of this product.'], 403);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Product deleted successfully'], 200);
    }
}
