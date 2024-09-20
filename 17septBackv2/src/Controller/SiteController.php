<?php

namespace App\Controller;

// use App\DTO\UserDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;
use App\Repository\SiteRepository;

#[Route('/api/site')]
class SiteController extends AbstractController
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


    #[Route('/all', name: 'app_site_show_all', methods: ['GET'])]
    public function showAll(Request $request, SiteRepository $siteRepository): JsonResponse
    { 

        $name = $request->query->get('name');
        $city = $request->query->get('city');
    
        $queryBuilder = $siteRepository->createQueryBuilder('site');
    
        if ($name) {
            $queryBuilder->andWhere('site.name LIKE :name')
                        ->setParameter('name', '%' . $name . '%');
        }
    
        if ($city) {
            $queryBuilder->andWhere('site.city LIKE :city')
                        ->setParameter('city', '%' . $city . '%');
        }
    
        $sites = $queryBuilder->getQuery()->getResult();
    
        $data = $this->serializer->serialize($sites, 'json', ['groups' => 'site:read']);
        $data = json_decode($data);
    
        return new JsonResponse(['sites' => $data], JsonResponse::HTTP_OK);

    }

    #[Route('/new', name: 'site_new')]
    //#[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function register(Request $request, EntityManagerInterface $em, SiteRepository $siteRepository, SerializerInterface $serializer)
    {
     $data = json_decode($request->getContent(), true );
     $siteToCheck = $siteRepository->findBy(['name' => $data['name'] ]);

     if (count($siteToCheck) > 0) {
        return new JsonResponse(['message' => 'Site deja existant'],JsonResponse::HTTP_BAD_REQUEST);
     }


     $site = new Site;
     $site->setName($data['name']);
     $site->setCity($data['city']);
     $errors = $this->validator->validate($site);

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

     $em->persist($site);
     $em->flush();
    
     return new JsonResponse(['message' => "test"], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_site_show', methods: ['GET'])]
    public function show(Site $site): Response
    {
      $siteData = [
        'name' => $site->getName(),
        'city' => $site->getCity()
      ];

    // Retourner la réponse JSON
    return new JsonResponse($siteData);
    }

    #[Route('/{id}/edit', name: 'app_site_edit', methods: ['PATCH'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function edit(Request $request, Site $site, EntityManagerInterface $em): Response
    {

        $data = json_decode($request->getContent(), true);

        foreach($data as $key => $value){
          if(property_exists($site, $key)){
            $setter = 'set'. ucfirst($key);
          }
          
          if(method_exists($site, $setter)){
            $site->$setter($value);
            // $this->logger->notice($value);
          }
        }

        $errors = $this->validator->validate($site);

        if(count($errors) > 0 ){
          return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->serializer->serialize($site, 'json' , ['groups'=> 'site:read']);

        $data = json_decode($data);

        $em->flush();

        return new JsonResponse(['site' => $data], JsonResponse::HTTP_OK);
    }

    #[Route('/{id}/delete', name: 'app_site_delete', methods: ['DELETE'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN")'))]
    public function delete(Request $request, site $site, EntityManagerInterface $entityManager)
    {
      // $this->logger->notice("DELETING");
      if (!$site) {
        return new JsonResponse([
            'error' => 'User not found'
        ], Response::HTTP_NOT_FOUND); // 404
      }
        // if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
        

            $entityManager->remove($site);
            $entityManager->flush();
        //}

        //return new JsonResponse(['message' => 'user deleted'], JsonResponse::HTTP_ACCEPTED);
        return new JsonResponse([
          'message' => 'Site deleted successfully'
      ], Response::HTTP_ACCEPTED);
    }

    

}
