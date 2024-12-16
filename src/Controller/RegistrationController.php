<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $plainPassword = $user->getPassword();
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
        $profile = new Profile();
        $profile->setUsername($user->getUsername());
        $profile->setBio(null);
        $profile->setFirstName(null);
        $profile->setLastName(null);
        $profile->setEmail(null);
        $profile->setPhoneNumber(null);
        $user->setProfile($profile);
        $entityManager->persist($profile);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'User and profile created successfully.', 'user' => $user->getUsername(), 'profile' => $profile->getId()], Response::HTTP_CREATED);
    }
}
