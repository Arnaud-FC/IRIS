<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
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
}
