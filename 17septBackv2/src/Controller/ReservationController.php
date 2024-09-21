<?php

namespace App\Controller;

// use App\DTO\UserDTO;

use App\Repository\BillingRepository;
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
use App\Repository\UserRepository;
use App\Repository\VisiteRepository;


#[Route('/api/reservation')]
class ReservationController extends AbstractController
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

    // ATESTER QUAND LES AUTRES CONTROLLERS SONT TERMINES ! NOT YET TESTED !
    #[Route('/all', name: 'app_reservation_show_all', methods: ['GET'])]
    public function showAll(Request $request, ReservationRepository $reservationRepository): JsonResponse
    { 

        $status = $request->query->get('status');
        $custommer = $request->query->get('custommer');
        $visite = $request->query->get('visite');
        $billing = $request->query->get('billing');

    
        $queryBuilder = $reservationRepository->createQueryBuilder('reservation');
    
        if ($status) {
            $queryBuilder->andWhere('reservation.status LIKE :status')
                        ->setParameter('status', '%' . $status . '%');
        }
    
        if ($custommer) {
            $queryBuilder->andWhere('reservation.custommer LIKE :custommer')
                        ->setParameter('custommer', '%' . $custommer . '%');
        }

        if ($visite) {
            $queryBuilder->andWhere('reservation.visite LIKE :visite')
                        ->setParameter('visite', '%' . $visite . '%');
        }

        if ($billing) {
            $queryBuilder->andWhere('reservation.billing LIKE :billing')
                        ->setParameter('billing', '%' . $billing . '%');
        }
    
        $reservation = $queryBuilder->getQuery()->getResult();
    
        $data = $this->serializer->serialize($reservation, 'json', ['groups' => 'reservation:read']);
        $data = json_decode($data);
    
        return new JsonResponse(['reservation' => $data], JsonResponse::HTTP_OK);

    }

    #[Route('/new', name: 'reservation_new')]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function register(Request $request, EntityManagerInterface $em, ReservationRepository $reservationRepository, SerializerInterface $serializer, VisiteRepository $visiteRepository, BillingRepository $billingRepository, UserRepository $userRepository)
    {
     $data = json_decode($request->getContent(), true );

     $reservation = new Reservation;
     $reservation->setStatus($data['status']);

     $visite = $visiteRepository->find($data['visite']);
     if (!$visite) {
        return new JsonResponse(['message' => 'Visite introuvable'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $reservation->setVisite($visite);
        $errors = $this->validator->validate($visite);
    
    if($data['billing'] === ''){
        $reservation->setBilling(null);
    } else {   

        $billing = $billingRepository->find($data['billing']);
        if(!$billing){
            return new JsonResponse(['message' => 'Facture introuvable'], JsonResponse::HTTP_BAD_REQUEST);
        }
            $reservation->setBilling($billing);
            $errors = $this->validator->validate($billing);
        }
    

    $custommer = $userRepository->find($data['custommer']);
        if(!$custommer){
            return new JsonResponse(['message' => 'Custommer introuvable'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $reservation->setCustommer($custommer);
        $errors = $this->validator->validate($custommer);

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

     $em->persist($reservation);
     $em->flush();

     $data = $this->serializer->serialize($reservation, 'json', ['groups' => 'reservation:read']);
     $data = json_decode($data);
    
     return new JsonResponse(['message' => "reservation créée"], JsonResponse::HTTP_CREATED);
    }


    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['PATCH'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function edit(Request $request, VisiteRepository $visiteRepository, EntityManagerInterface $em, ReservationRepository $reservationRepository, Reservation $reservation, BillingRepository $billingRepository, UserRepository $userRepository): Response
    {

        $data = json_decode($request->getContent(), true);

        foreach($data as $key => $value){

            
            if ($key === 'visite') {
                $visite = $visiteRepository->find((int) $value);
                if (!$visite) {
                    return new JsonResponse(['message' => 'Site introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $reservation->setVisite($visite);
            }

            elseif ($key === 'billing') {
                $billing = $billingRepository->find((int) $value);
                if (!$billing) {
                    return new JsonResponse(['message' => 'Facture introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $reservation->setBilling($billing);
            }

            elseif ($key === 'custommer') {
                $custommer = $userRepository->find((int) $value);
                if (!$custommer) {
                    return new JsonResponse(['message' => 'Client introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $reservation->setCustommer($custommer);
            }

            elseif ($key === 'guide') {
                $guide = $userRepository->find((int) $value);
                if (!$guide) {
                    return new JsonResponse(['message' => 'Guide introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $reservation->setGuide($guide);
            } else {
                if(property_exists($reservation, $key)){
                    $setter = 'set'. ucfirst($key);
                }
                    
                if(method_exists($reservation, $setter)){
                    $reservation->$setter($value);
                        // $this->logger->notice($value);
                }
            }
        }
        

        $errors = $this->validator->validate($reservation);

        if(count($errors) > 0 ){
          return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->serializer->serialize($reservation, 'json' , ['groups'=> 'reservation:read']);

        $data = json_decode($data);

        $em->flush();

        return new JsonResponse(['visite' => $data], JsonResponse::HTTP_OK);
    }


    #[Route('/{id}', name: 'reservation_show', methods: ['GET'])]
    public function show(reservation $reservation): Response
    {
      $reservationData = [
        'status' => $reservation->getStatus(),
        'billing' => $reservation->getBilling(),
        'custommer' => $reservation->getCustommer(),
        'guide' => $reservation->getGuide()
      ];

      $reservationData = $this->serializer->serialize($reservation, 'json' , ['groups'=> 'reservation:read']);

      $reservationData = json_decode($reservationData);

    // Retourner la réponse JSON
    return new JsonResponse($reservationData);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['DELETE'])]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager)
    {
      // $this->logger->notice("DELETING");
      if (!$reservation) {
        return new JsonResponse([
            'error' => 'reservation not found'
        ], Response::HTTP_NOT_FOUND); // 404
      }

            $entityManager->remove($reservation);
            $entityManager->flush();
        //}

        //return new JsonResponse(['message' => 'user deleted'], JsonResponse::HTTP_ACCEPTED);
        return new JsonResponse([
          'message' => 'reservation deleted successfully'
      ], Response::HTTP_ACCEPTED);
    }


}
