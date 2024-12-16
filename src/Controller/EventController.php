<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', methods: ['GET'])]
    public function index(EventRepository $eventRepository, SerializerInterface $serializer): JsonResponse
    {
        $events = $eventRepository->findAll();

        $responseData = $serializer->serialize($events, 'json', ['groups' => 'event:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/event/{id}', methods: ['GET'])]
    public function show(Event $event, SerializerInterface $serializer): JsonResponse
    {
        $responseData = $serializer->serialize($event, 'json', ['groups' => 'event:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/api/event/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'You must be logged in to create an event.'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['message' => 'Invalid JSON format.'], 400);
        }

        if (!isset($data['name'], $data['place'], $data['startDate'], $data['endDate'])) {
            return $this->json(['message' => 'Missing required fields.'], 400);
        }

        $event = new Event();
        $event->setName($data['name']);
        $event->setDescription($data['description'] ?? null);
        $event->setPlace($data['place']);
        $event->setStartDate(new \DateTime($data['startDate']));
        $event->setEndDate(new \DateTime($data['endDate']));
        $event->setStatus($data['status'] ?? true);
        $event->setTypeOfPlace($data['typeOfPlace'] ?? 'public');

        $event->setOrganizer($user);
        $event->setAuthor($user);

        $entityManager->persist($event);
        $entityManager->flush();

        $responseData = $serializer->serialize($event, 'json', ['groups' => 'event:read']);

        return new JsonResponse($responseData, 201, [], true);
    }

    #[Route('/api/event/update/{id}', methods: ['PUT'])]
    public function update(Request $request, Event $event, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $event->getAuthor()) {
            return $this->json(['message' => 'Access denied: You are not the author of this event.'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['message' => 'Invalid JSON format.'], 400);
        }

        $event->setName($data['name']);
        $event->setDescription($data['description'] ?? $event->getDescription());
        $event->setPlace($data['place'] ?? $event->getPlace());
        $event->setStartDate(new \DateTime($data['startDate'] ?? $event->getStartDate()->format('Y-m-d H:i:s')));
        $event->setEndDate(new \DateTime($data['endDate'] ?? $event->getEndDate()->format('Y-m-d H:i:s')));

        $entityManager->flush();

        $responseData = $serializer->serialize($event, 'json', ['groups' => 'event:read']);

        return new JsonResponse($responseData, 200, [], true);
    }

    #[Route('/api/event/delete/{id}', methods: ['DELETE'])]
    public function delete(Event $event, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $event->getAuthor()) {
            return $this->json(['message' => 'Access denied: You are not the author of this event.'], 403);
        }

        $entityManager->remove($event);
        $entityManager->flush();

        return $this->json(['message' => 'Event deleted successfully'], 200);
    }
}
