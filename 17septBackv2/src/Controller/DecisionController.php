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
use App\Repository\DecisionRepository;
use App\Entity\User;
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


    // #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['PATCH'])]
    // //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    // public function edit(Request $request, VisiteRepository $visiteRepository, EntityManagerInterface $em, ReservationRepository $reservationRepository, Reservation $reservation, BillingRepository $billingRepository, UserRepository $userRepository): Response
    // {

    //     $data = json_decode($request->getContent(), true);

    //     foreach($data as $key => $value){

            
    //         if ($key === 'visite') {
    //             $visite = $visiteRepository->find((int) $value);
    //             if (!$visite) {
    //                 return new JsonResponse(['message' => 'Site introuvable'], JsonResponse::HTTP_BAD_REQUEST);
    //             }
    //             $reservation->setVisite($visite);
    //         }

    //         elseif ($key === 'billing') {
    //             $billing = $billingRepository->find((int) $value);
    //             if (!$billing) {
    //                 return new JsonResponse(['message' => 'Facture introuvable'], JsonResponse::HTTP_BAD_REQUEST);
    //             }
    //             $reservation->setBilling($billing);
    //         }

    //         elseif ($key === 'custommer') {
    //             $custommer = $userRepository->find((int) $value);
    //             if (!$custommer) {
    //                 return new JsonResponse(['message' => 'Client introuvable'], JsonResponse::HTTP_BAD_REQUEST);
    //             }
    //             $reservation->setCustommer($custommer);
    //         }

    //         elseif ($key === 'guide') {
    //             $guide = $userRepository->find((int) $value);
    //             if (!$guide) {
    //                 return new JsonResponse(['message' => 'Guide introuvable'], JsonResponse::HTTP_BAD_REQUEST);
    //             }
    //             $reservation->setGuide($guide);
    //         } else {
    //             if(property_exists($reservation, $key)){
    //                 $setter = 'set'. ucfirst($key);
    //             }
                    
    //             if(method_exists($reservation, $setter)){
    //                 $reservation->$setter($value);
    //                     // $this->logger->notice($value);
    //             }
    //         }
    //     }
        

    //     $errors = $this->validator->validate($reservation);

    //     if(count($errors) > 0 ){
    //       return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
    //     }

    //     $data = $this->serializer->serialize($reservation, 'json' , ['groups'=> 'reservation:read']);

    //     $data = json_decode($data);

    //     $em->flush();

    //     return new JsonResponse(['visite' => $data], JsonResponse::HTTP_OK);
    // }


    // #[Route('/{id}', name: 'reservation_show', methods: ['GET'])]
    // public function show(reservation $reservation): Response
    // {
    //   $reservationData = [
    //     'status' => $reservation->getStatus(),
    //     'billing' => $reservation->getBilling(),
    //     'custommer' => $reservation->getCustommer(),
    //     'guide' => $reservation->getGuide()
    //   ];

    //   $reservationData = $this->serializer->serialize($reservation, 'json' , ['groups'=> 'reservation:read']);

    //   $reservationData = json_decode($reservationData);

    // // Retourner la réponse JSON
    // return new JsonResponse($reservationData);
    // }

    // #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['DELETE'])]
    // //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    // public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager)
    // {
    //   // $this->logger->notice("DELETING");
    //   if (!$reservation) {
    //     return new JsonResponse([
    //         'error' => 'reservation not found'
    //     ], Response::HTTP_NOT_FOUND); // 404
    //   }

    //         $entityManager->remove($reservation);
    //         $entityManager->flush();
    //     //}

    //     //return new JsonResponse(['message' => 'user deleted'], JsonResponse::HTTP_ACCEPTED);
    //     return new JsonResponse([
    //       'message' => 'reservation deleted successfully'
    //   ], Response::HTTP_ACCEPTED);
    // }


}
