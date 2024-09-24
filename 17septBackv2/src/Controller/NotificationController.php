<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;




class NotificationController extends AbstractController
{

    // #[Route('/api/notification', name: 'notification_index', methods: ['GET'])]
    // //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    // public function index(NotificationRepository $notificationRepository, SerializerInterface $serializer): JsonResponse
    // {
    //     $notifications = $notificationRepository->findAll();
    //     $jsonNotifications = $serializer->serialize($notifications, 'json', ['groups' => 'notification:read']);

    //     return new JsonResponse($jsonNotifications, Response::HTTP_OK, [], true);
    // }
        #[Route('/api/notification', name: 'notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        // Récupérer les paramètres de la requête
        $id = $request->query->get('id');
        $userId = $request->query->get('user');
        $status = $request->query->get('status');

        // Récupérer toutes les notifications
        $notifications = $notificationRepository->findAll();

        // Filtrer par ID
        if ($id) {
            $notification = $notificationRepository->find($id);
            if (!$notification) {
                return new JsonResponse(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
            }
            $jsonNotification = $serializer->serialize($notification, 'json', ['groups' => 'notification:read']);
            return new JsonResponse($jsonNotification, Response::HTTP_OK, [], true);
        }

        // Filtrer par utilisateur
        if ($userId) {
            $notifications = array_filter($notifications, fn($notification) => $notification->getUser()?->getId() == $userId);
        }

        // Filtrer par statut
        if ($status) {
            $notifications = array_filter($notifications, fn($notification) => $notification->getStatus() === $status);
        }

        // Sérialiser les notifications filtrées
        $jsonNotifications = $serializer->serialize($notifications, 'json', ['groups' => 'notification:read']);

        return new JsonResponse($jsonNotifications, Response::HTTP_OK, [], true);
    }

    #[Route('/api/notification/{id}', name: 'notification_show', methods: ['GET'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function show(Notification $notification, SerializerInterface $serializer): JsonResponse
    {
        $jsonNotification = $serializer->serialize($notification, 'json', ['groups' => 'notification:read']);

        return new JsonResponse($jsonNotification, Response::HTTP_OK, [], true);
    }

    #[Route('/api/notification', name: 'notification_create', methods: ['POST'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        // Récupérer les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Récupérer l'utilisateur à partir de l'ID fourni
        $userId = $data['user'] ?? null;
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle notification
        $notification = new Notification();
        $notification->setStatus($data['status'] ?? null);
        $notification->setMessage($data['message'] ?? null);
        $notification->setUser($user);  // Associer l'utilisateur à la notification

        // Valider la notification
        $errors = $validator->validate($notification);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder la nouvelle notification
        $em->persist($notification);
        $em->flush();

        return new JsonResponse(['message' => 'notification créée'], Response::HTTP_CREATED);
    }

    // #[Route('/api/notification/{id}', name: 'notification_update', methods: ['PATCH'])]
    // public function update(Request $request, Notification $notification, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    // {
        
    //     // Désérialisation
    //     $serializer->deserialize($request->getContent(), Notification::class, 'json', ['object_to_populate' => $notification]);

    //     // Vérifie si l'utilisateur est présent dans la requête
    //     $userId = $notification->getUser() ? $notification->getUser()->getId() : null;

    //     if ($userId !== null) {
    //         $user = $userRepository->find($userId);

    //         if (!$user) {
    //             return new JsonResponse(['message' => 'Utilisateur introuvable.'], Response::HTTP_BAD_REQUEST);
    //         }

    //         // Met à jour l'utilisateur de la notification
    //         $notification->setUser($user);
    //     }

    //     // Validation
    //     $errors = $validator->validate($notification);
    //     if (count($errors) > 0) {
    //         return new JsonResponse((string) $errors, Response::HTTP_BAD_REQUEST);
    //     }

    //     // Enregistrement des changements
    //     $em->flush();

    //     return new JsonResponse(['message' => 'Notification modifiée'], Response::HTTP_ACCEPTED);
    // }

    #[Route('api/notification/{id}', name: 'app_notification_edit', methods: ['PATCH'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function edit(Request $request, EntityManagerInterface $em, UserRepository $userRepository, Notification $notification, ValidatorInterface $validator,  SerializerInterface $serializer): Response
    {

        $data = json_decode($request->getContent(), true);

        foreach($data as $key => $value){

            
            if ($key === 'user') {
                $user = $userRepository->find((int) $value);
                if (!$user) {
                    return new JsonResponse(['message' => 'Utilisateur introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $notification->setUser($user);
            } else {
                if(property_exists($notification, $key)){
                    $setter = 'set'. ucfirst($key);
                }
                    
                if(method_exists($notification, $setter)){
                    $notification->$setter($value);
                        // $this->logger->notice($value);
                }
            }
        }
        

        $errors = $validator->validate($notification);

        if(count($errors) > 0 ){
          return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $data = $serializer->serialize($notification, 'json' , ['groups'=> 'notification:read']);

        $data = json_decode($data);

        $em->flush();

        return new JsonResponse(['notification' => $data], JsonResponse::HTTP_OK);
    }

    #[Route('/api/notification/{id}', name: 'notification_delete', methods: ['DELETE'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function delete(Notification $notification, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($notification);
        $em->flush();

        return new JsonResponse(['message' => 'Notification supprimée'], Response::HTTP_ACCEPTED);
    }
}
