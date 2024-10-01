<?php

namespace App\Controller;

// use App\DTO\UserDTO;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Entity\Decision;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Repository\DecisionRepository;
use App\Entity\User;
use App\Entity\Notification;
use App\Repository\UserRepository;


#[Route('/api/decision')]
class DecisionController extends AbstractController
{

  private $validator;
  private $entityManager;
  private $logger;
  private $serializer;

  public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, LoggerInterface $logger, SerializerInterface $serializer)
  {
    $this->validator = $validator;
    $this->entityManager = $entityManager;
    $this->logger= $logger;
    $this->serializer = $serializer;
  }


    #[Route('/all', name: 'app_decision_show_all', methods: ['GET'])]
    public function showAll(Request $request, DecisionRepository $decisionRepository): JsonResponse
    { 

        $status = $request->query->get('status');
        $guide = $request->query->get('guide');
        $reservation = $request->query->get('reservation');

    
        $queryBuilder = $decisionRepository->createQueryBuilder('decision');
    
        if ($status) {
            $queryBuilder->andWhere('decision.status LIKE :status')
                        ->setParameter('status', '%' . $status . '%');
        }
    
        if ($guide) {
            $queryBuilder->andWhere('decision.guide LIKE :guide')
                        ->setParameter('guide', '%' . $guide . '%');
        }

        if ($reservation) {
            $queryBuilder->andWhere('decision.reservation LIKE :reservation')
                        ->setParameter('reservation', '%' . $reservation . '%');
        }

        
        $decision = $queryBuilder->getQuery()->getResult();
    
        $data = $this->serializer->serialize($decision, 'json', ['groups' => 'decision:read']);
        $data = json_decode($data);
    
        return new JsonResponse(['decision' => $data], JsonResponse::HTTP_OK);

    }

    #[Route('/new', name: 'decision_new')]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function register(Request $request, EntityManagerInterface $em, ReservationRepository $reservationRepository, SerializerInterface $serializer,UserRepository $userRepository, DecisionRepository $decisionRepository )
    {
     $data = json_decode($request->getContent(), true );

     $decision = new Decision;
     $decision->setStatus($data['status']);

     $guide = $userRepository->find($data['guide']);
     if (!$guide) {
        return new JsonResponse(['message' => 'Guide introuvable'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $decision->setGuide($guide);
        $errors = $this->validator->validate($guide);
    
    $reservation = $reservationRepository->find($data['reservation']);
        if(!$reservation){
            return new JsonResponse(['message' => 'reservation introuvable'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $decision->setReservation($reservation);
        $errors = $this->validator->validate($decision);

     if (count($errors) > 0) {
        $errorMessages = [];
        
        // Récupérer les messages d'erreurs et les loguer
        foreach ($errors as $error) {
            $this->logger->notice($error->getMessage()); // Log l'erreur
            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
        }

        // Retourner les erreurs sous forme de JSON
        return new JsonResponse(['message' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
      }

     $em->persist($decision);
     $em->flush();

     $data = $this->serializer->serialize($decision, 'json', ['groups' => 'decision:read']);
     $data = json_decode($data);
    
     return new JsonResponse(['message' => "decision créée"], JsonResponse::HTTP_CREATED);
    }


    #[Route('/{id}/edit', name: 'app_decision_edit', methods: ['PATCH'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function edit(Request $request, EntityManagerInterface $em, ReservationRepository $reservationRepository, Reservation $reservation, UserRepository $userRepository, Decision $decision): Response
    {

        $data = json_decode($request->getContent(), true);

        foreach($data as $key => $value){

            
            if ($key === 'reservation') {
                $reservation = $reservationRepository->find((int) $value);
                if (!$reservation) {
                    return new JsonResponse(['message' => 'reservation introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $decision->setReservation($reservation);
            }

            elseif ($key === 'guide') {
                $guide = $userRepository->find((int) $value);
                if (!$guide) {
                    return new JsonResponse(['message' => 'Guide introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $decision->setGuide($guide);
            }

            else {
                if(property_exists($decision, $key)){
                    $setter = 'set'. ucfirst($key);
                }
                    
                if(method_exists($decision, $setter)){
                    $decision->$setter($value);
                        // $this->logger->notice($value);
                }
            }
        }
        

        $errors = $this->validator->validate($decision);

        if(count($errors) > 0 ){
          return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->serializer->serialize($decision, 'json' , ['groups'=> 'decision:read']);

        $data = json_decode($data);

        $em->flush();

        return new JsonResponse(['decision' => $data], JsonResponse::HTTP_OK);
    }


    #[Route('/{id}', name: 'decision_show', methods: ['GET'])]
    public function show(Decision $decision): Response
    {
      $decisionData = [
        'guide' => $decision->getGuide(),
        'reservation' => $decision->getReservation(),
        'status' => $decision->getStatus()
      ];

      $decisionData = $this->serializer->serialize($decision, 'json' , ['groups'=> 'decision:read']);

      $decisionData = json_decode($decisionData);

    // Retourner la réponse JSON
    return new JsonResponse($decisionData);
    }

    #[Route('/{id}/delete', name: 'app_decision_delete', methods: ['DELETE'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function delete(Request $request, Decision $decision, EntityManagerInterface $entityManager)
    {
      // $this->logger->notice("DELETING");
      if (!$decision) {
        return new JsonResponse([
            'error' => 'decision not found'
        ], Response::HTTP_NOT_FOUND); // 404
      }

            $entityManager->remove($decision);
            $entityManager->flush();
        //}

        //return new JsonResponse(['message' => 'user deleted'], JsonResponse::HTTP_ACCEPTED);
        return new JsonResponse([
          'message' => 'decision deleted successfully'
      ], Response::HTTP_ACCEPTED);
    }

    // #[Route('/{id}/response', name: 'app_decision_response', methods:['POST'])]
    // public function guideDecision(Request $request, Decision $decision, User $user){

    //     $user = $this->getUser(); 

    //     $decision->setStatus($request['status']);

        
    // }

    #[Route('/{id}/response', name: 'decision_response', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_GUIDE") or is_granted("ROLE_ADMIN")'))]
    public function respondToDecision(int $id, Request $request, DecisionRepository $decisionRepository, Decision $decision): JsonResponse
    {
        // Récupérer l'utilisateur connecté via JWT
        $user = $this->getUser(); // Ceci fonctionne avec JWT

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupérer la décision à partir de l'ID
        // $decision = $decisionRepository->find($id);
        // if (!$decision) {
        //     return new JsonResponse(['error' => 'Decision not found'], JsonResponse::HTTP_NOT_FOUND);
        // }


        // Vérifier que l'utilisateur connecté est bien le guide lié à la décision
        if ($decision->getGuide()->getId() !== $user->getId()) {
            throw new AccessDeniedException('You are not authorized to respond to this decision.');
        }

        // Récupérer le statut de la décision à partir de la requête (accepté ou refusé)
        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        // Valider le statut
        if (!in_array($status, ['accepted', 'denied'])) {
            return new JsonResponse(['error' => 'Invalid status'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le statut de la décision
        $decision->setStatus($status);


        $this->entityManager->persist($decision);
        $this->entityManager->flush();

        // je dois recuperer le custommer et la decision
        // //creer une notif
        // createNotification($custommer, $decision, $reservation);
        // Notifier le client si la décision est acceptée
        if ($status === 'accepted') {
            $this->createCustommerNotification($decision->getReservation()->getCustommer(), $decision->getReservation());
        }

        // Retourner la réponse avec les données de la décision mise à jour
        $responseData = $this->serializer->serialize($decision, 'json', ['groups' => 'decision:read']);
        return new JsonResponse(json_decode($responseData), JsonResponse::HTTP_OK);
    }

    private function createCustommerNotification(User $custommer, Reservation $reservation)
    {

        $notification = new Notification();
        $notification->setUser($custommer);
        $notification->setMessage(sprintf(" One guide accept your reservation '%s'.", $reservation->getVisite()->getName()));
        $notification->setStatus('waiting'); // Par exemple, statut 'unread'

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }


}
