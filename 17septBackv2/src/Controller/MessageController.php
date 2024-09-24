<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Chat;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageController extends AbstractController
{
    #[Route('/api/message', name: 'message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        // Récupérer les paramètres de la requête
        $userId = $request->query->get('user');
        $chatId = $request->query->get('chat');
        $openStatus = $request->query->get('open');

        // Récupérer toutes les messages
        $messages = $messageRepository->findAll();

        // Filtrer par utilisateur
        if ($userId) {
            $messages = array_filter($messages, fn($message) => $message->getSender()?->getId() == $userId || $message->getReceiver()?->getId() == $userId);
        }

        // Filtrer par chat
        if ($chatId) {
            $messages = array_filter($messages, fn($message) => $message->getChat()?->getId() == $chatId);
        }

        // Filtrer par statut ouvert
        if ($openStatus !== null) {
            $openStatus = filter_var($openStatus, FILTER_VALIDATE_BOOLEAN);
            $messages = array_filter($messages, fn($message) => $message->isOpen() === $openStatus);
        }

        // Sérialiser les messages filtrés
        $jsonMessages = $serializer->serialize($messages, 'json', ['groups' => 'message:read']);

        return new JsonResponse($jsonMessages, Response::HTTP_OK, [], true);
    }

    #[Route('/api/message/{id}', name: 'message_show', methods: ['GET'])]
    public function show(Message $message, SerializerInterface $serializer): JsonResponse
    {
        $jsonMessage = $serializer->serialize($message, 'json', ['groups' => 'message:read']);
        return new JsonResponse($jsonMessage, Response::HTTP_OK, [], true);
    }

    #[Route('/api/message', name: 'message_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        // Récupérer les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Créer un nouveau message
        $message = new Message();
        $message->setMessage($data['message'] ?? null);
        $message->setOpen($data['open'] ?? false);

        // Récupérer l'expéditeur et le destinataire
        $senderId = $data['sender'] ?? null;
        $receiverId = $data['receiver'] ?? null;
        $chatId = $data['chat'] ?? null;

        $sender = $em->getRepository(User::class)->find($senderId);
        $receiver = $em->getRepository(User::class)->find($receiverId);
        $chat = $em->getRepository(Chat::class)->find($chatId);

        if (!$sender || !$receiver || !$chat) {
            return new JsonResponse(['error' => 'Sender, receiver or chat not found'], Response::HTTP_BAD_REQUEST);
        }

        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setChat($chat);

        // Validation
        $errors = $validator->validate($message);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder le message
        $em->persist($message);
        $em->flush();

        return new JsonResponse(['message' => 'Message created'], Response::HTTP_CREATED);
    }

    #[Route('/api/message/{id}', name: 'message_update', methods: ['PATCH'])]
    public function edit(
        Request $request,
        Message $message,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Récupération du repository User pour les utilisateurs
        $userRepository = $em->getRepository(User::class);

        // Met à jour le contenu du message si fourni
        if (isset($data['message'])) {
            $message->setMessage($data['message']);
        }

        // Met à jour le statut 'open' du message si fourni
        if (isset($data['open'])) {
            $message->setOpen(filter_var($data['open'], FILTER_VALIDATE_BOOLEAN));
        }

        // Met à jour le sender (expéditeur) si fourni
        if (isset($data['sender'])) {
            $sender = $userRepository->find($data['sender']);
            if (!$sender) {
                return new JsonResponse(['error' => 'Sender not found'], Response::HTTP_BAD_REQUEST);
            }
            $message->setSender($sender);
        }

        // Met à jour le receiver (destinataire) si fourni
        if (isset($data['receiver'])) {
            $receiver = $userRepository->find($data['receiver']);
            if (!$receiver) {
                return new JsonResponse(['error' => 'Receiver not found'], Response::HTTP_BAD_REQUEST);
            }
            $message->setReceiver($receiver);
        }

        // Validation des données
        $errors = $validator->validate($message);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        // Enregistrement des changements dans la base de données
        $em->flush();

        // Sérialisation de l'objet message mis à jour
        $jsonMessage = $serializer->serialize($message, 'json', ['groups' => 'message:read']);
        return new JsonResponse($jsonMessage, Response::HTTP_OK, [], true);
    }

    #[Route('/api/message/{id}', name: 'message_delete', methods: ['DELETE'])]
    public function delete(Message $message, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($message);
        $em->flush();

        return new JsonResponse(['message' => 'Message deleted'], Response::HTTP_ACCEPTED);
    }
}
