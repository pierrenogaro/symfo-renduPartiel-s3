<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profiles', methods: ['GET'])]
    public function index(ProfileRepository $profileRepository, SerializerInterface $serializer): JsonResponse
    {
        $profiles = $profileRepository->findAll();

        $responseData = $serializer->serialize($profiles, 'json', ['groups' => 'profile:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/profile/{id}', methods: ['GET'])]
    public function show(Profile $profile, SerializerInterface $serializer): JsonResponse
    {
        $responseData = $serializer->serialize($profile, 'json', ['groups' => 'profile:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/api/profile/update/{id}', methods: ['PUT'])]
    public function update(Request $request, Profile $profile, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON format.'], 400);
        }

        $profile->setFirstName($data['firstName'] ?? $profile->getFirstName());
        $profile->setLastName($data['lastName'] ?? $profile->getLastName());
        $profile->setEmail($data['email'] ?? $profile->getEmail());
        $profile->setBio($data['bio'] ?? $profile->getBio());
        $profile->setPhoneNumber($data['phoneNumber'] ?? $profile->getPhoneNumber());

        $entityManager->flush();

        $responseData = $serializer->serialize($profile, 'json', ['groups' => 'profile:read']);

        return new JsonResponse($responseData, 200, [], true);
    }
}