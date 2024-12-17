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
        $acceptedParticipants = $event->getParticipants()->filter(function ($participant) use ($event) {
            foreach ($event->getInvitations() as $invitation) {
                if ($invitation->getInvitedUser() === $participant && $invitation->getStatus() === 'accepted') {
                    return true;
                }
            }
            return false;
        });

        $pendingInvitations = $event->getInvitations()->filter(function ($invitation) {
            return $invitation->getStatus() === 'pending';
        });

        $rejectedInvitations = $event->getInvitations()->filter(function ($invitation) {
            return $invitation->getStatus() === 'rejected';
        });

        $contributions = $event->getContributions();

        $eventData = $serializer->serialize($event, 'json', ['groups' => 'event:read']);
        $acceptedData = $serializer->serialize($acceptedParticipants, 'json', ['groups' => 'userjson']);
        $pendingData = $serializer->serialize($pendingInvitations, 'json', ['groups' => 'invitation:read']);
        $rejectedData = $serializer->serialize($rejectedInvitations, 'json', ['groups' => 'invitation:read']);
        $contributionData = $serializer->serialize($contributions, 'json', ['groups' => 'contribution:read']);

        return new JsonResponse([
            'event' => json_decode($eventData),
            'accepted_participants' => json_decode($acceptedData),
            'pending_invitations' => json_decode($pendingData),
            'rejected_invitations' => json_decode($rejectedData),
            'contributions' => json_decode($contributionData),
        ], 200);
    }

    #[Route('/api/event/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'], $data['place'], $data['startDate'], $data['endDate'])) {
            return $this->json(['message' => 'Missing required fields.'], 400);
        }

        $startDate = new \DateTime($data['startDate']);
        if ($startDate < new \DateTime()) {
            return $this->json(['message' => 'Start date cannot be in the past.'], 400);
        }

        $endDate = new \DateTime($data['endDate']);
        if ($endDate <= $startDate) {
            return $this->json(['message' => 'End date must be after the start date.'], 400);
        }

        $typeOfPlace = $data['typeOfPlace'] ?? 'public';
        if (!in_array($typeOfPlace, ['public', 'private'])) {
            return $this->json(['message' => 'Invalid typeOfPlace. Must be "public" or "private".'], 400);
        }

        $event = new Event();
        $event->setName($data['name']);
        $event->setDescription($data['description'] ?? null);
        $event->setPlace($data['place']);
        $event->setStartDate($startDate);
        $event->setEndDate($endDate);
        $event->setState($data['state'] ?? true);
        $event->setStatus($data['status'] ?? true);
        $event->setTypeOfPlace($typeOfPlace);
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

        if (isset($data['state'])) {
            $event->setState((bool) $data['state']);
        }

        if (isset($data['status'])) {
            $event->setStatus((bool) $data['status']);
        }

        $event->setName($data['name'] ?? $event->getName());
        $event->setDescription($data['description'] ?? $event->getDescription());
        $event->setPlace($data['place'] ?? $event->getPlace());

        if (isset($data['startDate'])) {
            $startDate = new \DateTime($data['startDate']);
            if ($startDate < new \DateTime()) {
                return $this->json(['message' => 'Start date cannot be in the past.'], 400);
            }
            $event->setStartDate($startDate);
        }

        if (isset($data['endDate'])) {
            $endDate = new \DateTime($data['endDate']);
            if ($endDate <= $event->getStartDate()) {
                return $this->json(['message' => 'End date must be after the start date.'], 400);
            }
            $event->setEndDate($endDate);
        }

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

    #[Route('/api/event/participate/{id}', methods: ['POST'])]
    public function participate(Event $event, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        if ($event->getState() === false) {
            return $this->json(['message' => 'This event has been canceled.'], 400);
        }

        if ($event->getParticipants()->contains($user)) {
            return $this->json(['message' => 'You are already a participant in this event.'], 400);
        }

        $event->addParticipant($user);
        $entityManager->flush();

        return $this->json(['message' => 'You have successfully registered for the event.'], 200);
    }
}
