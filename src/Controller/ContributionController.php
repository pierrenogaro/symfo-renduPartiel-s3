<?php

namespace App\Controller;

use App\Entity\Contribution;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContributionController extends AbstractController
{
    #[Route('/api/event/{id}/add/contribution', methods: ['POST'])]
    public function addSuggestion(Event $event, Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        if ($event->getOrganizer() !== $user && !$event->getParticipants()->contains($user)) {
            return $this->json(['message' => 'You are not invited to this event.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $suggestion = $data['suggestion'] ?? null;

        if (!$suggestion) {
            return $this->json(['message' => 'Suggestion is required'], 400);
        }

        $contribution = new Contribution();
        $contribution->setEvent($event);
        $contribution->setSuggestion($suggestion);
        $contribution->setCreatedBy($user);

        $manager->persist($contribution);
        $manager->flush();

        return $this->json(['message' => 'Suggestion added successfully.']);
    }

    #[Route('/api/contribution/{id}/take', methods: ['PUT'])]
    public function takeContribution(Contribution $contribution, EntityManagerInterface $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $event = $contribution->getEvent();

        if ($event->getOrganizer() !== $user && !$event->getParticipants()->contains($user)) {
            return $this->json(['message' => 'You are not invited to this event.'], 403);
        }

        if ($contribution->getTakenBy() !== null) {
            return $this->json(['message' => 'This suggestion is already taken.'], 400);
        }

        $contribution->setTakenBy($user);
        $contribution->setStatus('taken');

        $manager->flush();

        return $this->json(['message' => 'Contribution taken successfully.']);
    }


    #[Route('/api/contribution/{id}/untake', methods: ['PUT'])]
    public function untakeContribution(Contribution $contribution, EntityManagerInterface $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        if ($contribution->getTakenBy() !== $user) {
            return $this->json(['message' => 'You are not allowed to untake this suggestion.'], 403);
        }

        $contribution->setTakenBy(null);
        $contribution->setStatus('pending');

        $manager->flush();

        return $this->json(['message' => 'Contribution untaken successfully.']);
    }


    #[Route('/api/contribution/delete/{id}', methods: ['DELETE'])]
    public function deleteContribution(Contribution $contribution, EntityManagerInterface $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $event = $contribution->getEvent();

        if ($contribution->getCreatedBy() !== $user && $event->getOrganizer() !== $user) {
            return $this->json(['message' => 'You are not allowed to delete this suggestion.'], 403);
        }

        $manager->remove($contribution);
        $manager->flush();

        return $this->json(['message' => 'Contribution deleted successfully.']);
    }
}