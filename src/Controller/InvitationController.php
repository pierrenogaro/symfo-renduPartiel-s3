<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    #[Route('/api/my/invitations', methods: ['GET'])]
    public function index(InvitationRepository $invitationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $invitations = $invitationRepository->findBy(['invitedUser' => $user]);

        return $this->json($invitations, 200, [], ['groups' => 'invitation:read']);
    }

    #[Route('/api/invite/{eventId}', methods: ['POST'])]
    public function invite(
        int $eventId,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = $entityManager->getRepository(Event::class)->find($eventId);

        if (!$event) {
            return $this->json(['message' => 'Event not found.'], 404);
        }

        if ($event->getState() === false) {
            return $this->json(['message' => 'You cannot send invitations for a canceled event.'], 403);
        }

        if ($event->getStatus() === true) {
            return $this->json(['message' => 'You cannot send invitations for a public event.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $invitedUser = $entityManager->getRepository(User::class)->find($data['userId'] ?? 0);

        if (!$invitedUser) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        $invitation = new Invitation();
        $invitation->setEvent($event);
        $invitation->setInvitedUser($invitedUser);

        $entityManager->persist($invitation);
        $entityManager->flush();

        return $this->json(['message' => 'Invitation sent successfully.'], 201);
    }


    #[Route('/api/invitation/{id}/respond', methods: ['PUT'])]
    public function respond(
        int $id,
        Request $request,
        InvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $invitation = $invitationRepository->find($id);

        if (!$invitation || $invitation->getInvitedUser() !== $this->getUser()) {
            return $this->json(['message' => 'Invalid invitation.'], 403);
        }

        if ($invitation->getEvent()->getState() === false) {
            return $this->json(['message' => 'You cannot respond to invitations for a canceled event.'], 403);
        }

        if ($invitation->getEvent()->getStatus() === true) {
            return $this->json(['message' => 'This event is public and does not require invitations.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $state = $data['state'] ?? null;

        if (!in_array($state, ['accepted', 'rejected'])) {
            return $this->json(['message' => 'Invalid state.'], 400);
        }

        $invitation->setStatus($state);

        if ($state === 'accepted') {
            $invitation->getEvent()->addParticipant($this->getUser());
        }

        $entityManager->flush();

        return $this->json(['message' => 'Invitation responded successfully.']);
    }

}
