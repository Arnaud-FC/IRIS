<?php

namespace App\Controller;

// use App\DTO\UserDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Visite;
use App\Entity\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;
use App\Repository\VisiteRepository;
use App\Repository\SiteRepository;

#[Route('/api/visite')]
class VisiteController extends AbstractController
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


    #[Route('/all', name: 'app_visite_show_all', methods: ['GET'])]
    public function showAll(Request $request, VisiteRepository $visiteRepository): JsonResponse
    { 

        $name = $request->query->get('name');
        $duration = $request->query->get('duration');
        $price = $request->query->get('price');

    
        $queryBuilder = $visiteRepository->createQueryBuilder('visite');
    
        if ($name) {
            $queryBuilder->andWhere('visite.name LIKE :name')
                        ->setParameter('name', '%' . $name . '%');
        }
    
        if ($duration) {
            $queryBuilder->andWhere('visite.duration LIKE :duration')
                        ->setParameter('duration', '%' . $duration . '%');
        }

        if ($price) {
            $queryBuilder->andWhere('visite.price LIKE :price')
                        ->setParameter('price', '%' . $price . '%');
        }
    
        $visites = $queryBuilder->getQuery()->getResult();
    
        $data = $this->serializer->serialize($visites, 'json', ['groups' => 'site:read']);
        $data = json_decode($data);
    
        return new JsonResponse(['Visites' => $data], JsonResponse::HTTP_OK);

    }

    #[Route('/new', name: 'visite_new')]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function register(Request $request, EntityManagerInterface $em, VisiteRepository $visiteRepository, SerializerInterface $serializer, SiteRepository $siteRepository)
    {
     $data = json_decode($request->getContent(), true );
     $visiteToCheck = $visiteRepository->findBy(['name' => $data['name'] ]);

     if (count($visiteToCheck) > 0) {
        return new JsonResponse(['message' => 'visite deja existante'],JsonResponse::HTTP_BAD_REQUEST);
     }

     $visite = new Visite;
     $visite->setName($data['name']);
     $visite->setDuration($data['duration']);
     $visite->setPrice($data['price']);
     $visite->setDescription($data['description']);

     $site = $siteRepository->find($data['site']);
     if (!$site) {
        return new JsonResponse(['message' => 'Site introuvable'], JsonResponse::HTTP_BAD_REQUEST);
    }


     $visite->setSite($site);
     $errors = $this->validator->validate($visite);

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

     $em->persist($visite);
     $em->flush();

     $data = $this->serializer->serialize($visite, 'json', ['groups' => 'site:read']);
     $data = json_decode($data);
    
     return new JsonResponse(['message' => "visite créée"], JsonResponse::HTTP_CREATED);
    }

    // #[Route('/{id}', name: 'app_site_show', methods: ['GET'])]
    // public function show(Site $site): Response
    // {
    //   $siteData = [
    //     'name' => $site->getName(),
    //     'city' => $site->getCity()
    //   ];

    // // Retourner la réponse JSON
    // return new JsonResponse($siteData);
    // }

    // #[Route('/{id}/edit', name: 'app_site_edit', methods: ['PATCH'])]
    // #[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    // public function edit(Request $request, Site $site, EntityManagerInterface $em): Response
    // {

    //     $data = json_decode($request->getContent(), true);

    //     foreach($data as $key => $value){
    //       if(property_exists($site, $key)){
    //         $setter = 'set'. ucfirst($key);
    //       }
          
    //       if(method_exists($site, $setter)){
    //         $site->$setter($value);
    //         // $this->logger->notice($value);
    //       }
    //     }

    //     $errors = $this->validator->validate($site);

    //     if(count($errors) > 0 ){
    //       return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
    //     }

    //     $data = $this->serializer->serialize($site, 'json' , ['groups'=> 'site:read']);

    //     $data = json_decode($data);

    //     $em->flush();

    //     return new JsonResponse(['site' => $data], JsonResponse::HTTP_OK);
    // }

    // #[Route('/{id}/delete', name: 'app_site_delete', methods: ['DELETE'])]
    // #[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    // public function delete(Request $request, site $site, EntityManagerInterface $entityManager)
    // {
    //   // $this->logger->notice("DELETING");
    //   if (!$site) {
    //     return new JsonResponse([
    //         'error' => 'User not found'
    //     ], Response::HTTP_NOT_FOUND); // 404
    //   }
    //     // if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
        

    //         $entityManager->remove($site);
    //         $entityManager->flush();
    //     //}

    //     //return new JsonResponse(['message' => 'user deleted'], JsonResponse::HTTP_ACCEPTED);
    //     return new JsonResponse([
    //       'message' => 'Site deleted successfully'
    //   ], Response::HTTP_ACCEPTED);
    // }

    

}
