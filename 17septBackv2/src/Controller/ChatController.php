<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ReservationRepository;
use App\Entity\Notification;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChatController extends AbstractController
{
    // #[Route('/api/chat', name: 'chat_index', methods: ['GET'])]
    // public function index(ChatRepository $chatRepository, SerializerInterface $serializer): JsonResponse
    // {
    //     $chats = $chatRepository->findAll();
    //     $jsonChats = $serializer->serialize($chats, 'json', ['groups' => 'chat:read']);

    //     return new JsonResponse($jsonChats, Response::HTTP_OK, [], true);
    // }

    #[Route('/api/chat', name: 'chat_index', methods: ['GET'])]
    public function index(ChatRepository $chatRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        // Récupérer les paramètres de la requête
        $id = $request->query->get('id');
        $senderId = $request->query->get('sender');
        $receiverId = $request->query->get('receiver');
        $isOpen = $request->query->get('open');

        // Créer une requête de base
        $chats = $chatRepository->findAll();

        // Filtrer par ID
        if ($id) {
            $chat = $chatRepository->find($id);
            if (!$chat) {
                return new JsonResponse(['error' => 'Chat not found'], Response::HTTP_NOT_FOUND);
            }
            $jsonChat = $serializer->serialize($chat, 'json', ['groups' => 'chat:read']);
            return new JsonResponse($jsonChat, Response::HTTP_OK, [], true);
        }

        // Filtrer par sender
        if ($senderId) {
            $chats = array_filter($chats, fn($chat) => $chat->getSender()?->getId() == $senderId);
        }

        // Filtrer par receiver
        if ($receiverId) {
            $chats = array_filter($chats, fn($chat) => $chat->getReceiver()?->getId() == $receiverId);
        }

        // Filtrer par état ouvert
        if ($isOpen !== null) {
            $isOpen = filter_var($isOpen, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $chats = array_filter($chats, fn($chat) => $chat->isOpen() === $isOpen);
        }

        // Sérialiser les chats filtrés
        $jsonChats = $serializer->serialize($chats, 'json', ['groups' => 'chat:read']);

        return new JsonResponse($jsonChats, Response::HTTP_OK, [], true);
    }

    #[Route('/api/chat/{id}', name: 'chat_show', methods: ['GET'])]
    public function show(Chat $chat, SerializerInterface $serializer): JsonResponse
    {
        $jsonChat = $serializer->serialize($chat, 'json', ['groups' => 'chat:read']);

        return new JsonResponse($jsonChat, Response::HTTP_OK, [], true);
    }

    #[Route('/api/chat', name: 'chat_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Récupérer les utilisateurs à partir des IDs fournis
        $receiverId = $data['receiver'] ?? null;
        $senderId = $data['sender'] ?? null;

        $receiver = $userRepository->find($receiverId);
        $sender = $userRepository->find($senderId);

        if (!$receiver || !$sender) {
            return new JsonResponse(['error' => 'Receiver or sender not found'], Response::HTTP_BAD_REQUEST);
        }

        // Créer un nouveau chat
        $chat = new Chat();
        $chat->setOpen($data['open'] ?? false);
        $chat->setReceiver($receiver);
        $chat->setSender($sender);

        // Valider le chat
        $errors = $validator->validate($chat);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder le nouveau chat
        $em->persist($chat);
        $em->flush();

        return new JsonResponse(['message' => 'Chat created'], Response::HTTP_CREATED);
    }

    #[Route('/api/chat/{id}', name: 'chat_edit', methods: ['PATCH'])]
    public function edit(Request $request, Chat $chat, EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $key => $value) {
            if ($key === 'receiver') {
                $receiver = $userRepository->find((int)$value);
                if (!$receiver) {
                    return new JsonResponse(['error' => 'Receiver not found'], Response::HTTP_BAD_REQUEST);
                }
                $chat->setReceiver($receiver);
            } elseif ($key === 'sender') {
                $sender = $userRepository->find((int)$value);
                if (!$sender) {
                    return new JsonResponse(['error' => 'Sender not found'], Response::HTTP_BAD_REQUEST);
                }
                $chat->setSender($sender);
            } elseif (property_exists($chat, $key)) {
                $setter = 'set' . ucfirst($key);
                if (method_exists($chat, $setter)) {
                    $chat->$setter($value);
                }
            }
        }

        // Validation
        $errors = $validator->validate($chat);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Chat updated'], Response::HTTP_OK);
    }

    #[Route('/api/chat/{id}', name: 'chat_delete', methods: ['DELETE'])]
    public function delete(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($chat);
        $em->flush();

        return new JsonResponse(['message' => 'Chat deleted'], Response::HTTP_NO_CONTENT);
    }

    #[Route('api/chat/create/{guideId}', name: 'chat_create', methods: ['POST'])]
    public function createChat(
        int $guideId,
        UserRepository $userRepository,
        ChatRepository $chatRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Récupérer l'utilisateur connecté (client)
        $user = $this->getUser(); 
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupérer le guide par son ID
        $guide = $userRepository->find($guideId);
        if (!$guide || !$guide->hasRole('ROLE_GUIDE')) {
            return new JsonResponse(['error' => 'Guide not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si un chat entre le client et ce guide existe déjà
        $existingChat = $chatRepository->findOneBy([
            'sender' => $user,
            'receiver' => $guide,
        ]);

        if ($existingChat) {
            return new JsonResponse(['message' => 'Chat already exists', 'chatId' => $existingChat->getId()], JsonResponse::HTTP_OK);
        }

        // Créer un nouveau chat entre l'utilisateur (client) et le guide
        $chat = new Chat();
        $chat->setSender($user);   // L'utilisateur connecté est le client qui initie la discussion
        $chat->setReceiver($guide); // Le guide est le destinataire
        $chat->setOpen(true);

        // Persister le chat dans la base de données
        $em->persist($chat);
        $em->flush();

        return new JsonResponse(['message' => 'Chat created', 'chatId' => $chat->getId()], JsonResponse::HTTP_CREATED);
    }

    //ICI PROBLEME POUR RECUP LA RESA
    #[Route('api/chat/{chatId}/confirm', name: 'chat_confirm_reservation', methods: ['POST'])]
    //#[IsGranted('ROLE_USER')]
    public function confirmReservation(
        int $chatId,
        ReservationRepository $reservationRepository,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();


        // Vérifier que l'utilisateur est bien connecté
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupérer le chat à partir de l'ID
        $chat = $em->getRepository(Chat::class)->find($chatId);
        if (!$chat) {
            return new JsonResponse(['error' => 'Chat not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer la réservation liée au chat
        $reservation = $chat->getReservation(); // Assure-toi d'avoir cette méthode dans Chat
        if (!$reservation) {
            return new JsonResponse(['error' => 'Reservation not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est bien le client de la réservation
        if ($reservation->getCustommer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'You are not authorized to confirm this reservation.'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Mettre à jour le statut de la réservation à 'accepted'
        $reservation->setStatus('accepted');
        $reservation->setGuide($chat->getReceiver()); // Assigner le guide à la réservation
        $em->persist($reservation);
        
        // Instancier et envoyer la notification au guide
        $this->sendNotificationToGuide($chat->getReceiver(), $reservation, $em);

        $em->flush();

        return new JsonResponse(['message' => 'Reservation confirmed and guide notified.'], JsonResponse::HTTP_OK);
    }

    private function sendNotificationToGuide(User $guide, Reservation $reservation
, EntityManagerInterface $em): void
    {
        $notification = new Notification();
        $notification->setUser($guide);
        $notification->setMessage(sprintf("The reservation for '%s' has been accepted.", $reservation->getVisite()->getName()));
        $notification->setStatus('waiting'); // Statut de notification, par exemple 'unread'

        $em->persist($notification);
    }

}
