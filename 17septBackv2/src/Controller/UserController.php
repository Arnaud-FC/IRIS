<?php

namespace App\Controller;

use App\DTO\UserDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Form\UserType;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

#[Route('/api/user')]
class UserController extends AbstractController
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


  

    #[Route('/register', name: 'user_register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, LoggerInterface $logger, ValidatorInterface $validatorInterface, SerializerInterface $serializer)
    {
    

     $data = json_decode($request->getContent(), true );


     $emailToCheck = $userRepository->findBy(['email' => $data['email'] ]);

     if (count($emailToCheck) > 0) {
        return new JsonResponse(['message' => 'Email dèjà utilisé'],JsonResponse::HTTP_BAD_REQUEST);
     }

    
     $logger->notice("------------------\"" . $data['email'] . "\"");
     $logger->notice("------------------\"" . count($emailToCheck) . "\"");


     $user = new User;
     $user->setEmail($data['email']);
     $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
     $user->setPassword($hashedPassword);
     $user->setRoles($data['roles']);
     $user->setFirstName($data['first_name']);
     $user->setLastName($data['last_name']);
     $errors = $validatorInterface->validate($user);

     if (count($errors) > 0) {
        $errorMessages = [];
        
        // Récupérer les messages d'erreurs et les loguer
        foreach ($errors as $error) {
            $logger->notice($error->getMessage()); // Log l'erreur
            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
        }

        // Retourner les erreurs sous forme de JSON
        return new JsonResponse(['message' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
      }

     $em->persist($user);
     $em->flush();
    
     return new JsonResponse(['message' => "test"], JsonResponse::HTTP_CREATED);
    }

    #[Route('/list', name: 'user_list')]
    public function userList(UserRepository $userRepository, SerializerInterface $serializer)
    {
      $users = $userRepository->findAll();

      $data = $serializer->serialize($users, 'json', ['groups'=> 'user:read']);
    
      $data = json_decode($data);

      return new JsonResponse(['users' => $data]);

    }

    #[Route('/notifs', name: 'user_list')]
    public function getNotifs(UserRepository $userRepository, SerializerInterface $serializer)
    {
      $user = $userRepository->findOneById(1);
      $notifs = $user->getNotifications();

       $data = $serializer->serialize($notifs, 'json', ['groups'=> 'user:read'] );
    
       $data = json_decode($data);

      return new JsonResponse(['notifs' => $data]);

    }

    #[Route('/me', name:'app_user_me', methods:['GET'])]
    public function showMe(){

      $user = $this->getUser();
      $data = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
      $data = json_decode($data);

      // return new JsonResponse(["user" => $data], JsonResponse::HTTP_OK);
      return $this->json($data, JsonResponse::HTTP_OK);


    }

    #[Route('/me/delete', name:'app_delete_me', methods:['GET'])]
    public function deleteMe(EntityManagerInterface $em){

      $user = $this->getUser();
      $em->remove($user);
      $em->flush($em);
      // $data = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

      // $data = json_decode($data);

      // return new JsonResponse(["user" => $data], JsonResponse::HTTP_OK);
      return new JsonResponse(['message' =>'you have been deleted'], Response::HTTP_ACCEPTED);

    }

    #[Route('/me/edit', name:'app_edit_me', methods:['PATCH'])]
    public function updateMe(EntityManagerInterface $em, Request $request){

      $user = $this->getUser();

      $data = json_decode($request->getContent(), true);

      foreach($data as $key => $value){

        if(property_exists($user, $key)){
          $setter = 'set'. ucfirst($key);
        }
        
        if(method_exists($user, $setter)){
          $user->$setter($value);
          // $this->logger->notice($value);
        }
      }

      $errors = $this->validator->validate($user);

      if(count($errors) > 0 ){
        return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
      }

      $data = $this->serializer->serialize($user, 'json', ['groups'=> 'user:read']);

      $data = json_decode($data);
      
      $em->flush($em);
      // $data = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

      // $data = json_decode($data);

      // return new JsonResponse(["user" => $data], JsonResponse::HTTP_OK);
      return new JsonResponse(['message' =>'you have been updated'], Response::HTTP_ACCEPTED);

    }



  
    #[Route('/all', name: 'app_user_show_all', methods: ['GET'])]
    public function showAll(Request $request, UserRepository $userRepository): JsonResponse
    { 

      $firstName = $request->query->get('firstName');
      $lastName = $request->query->get('lastName');
      $email = $request->query->get('email');
      $roles = $request->query->get('roles');


      
      
      //$criteria =[];
      $queryBuilder = $userRepository->createQueryBuilder('u');

      if ($firstName) {
          // Recherche partielle (commence ou contient la valeur)
          $queryBuilder->andWhere('u.firstName LIKE :firstName')
                      ->setParameter('firstName', '%' . $firstName . '%');
      }
      
      if ($lastName) {
          // Recherche partielle (commence ou contient la valeur)
          $queryBuilder->andWhere('u.lastName LIKE :lastName')
                      ->setParameter('lastName', '%' . $lastName . '%');
      }
      
      if ($email) {
          // Recherche partielle (commence ou contient la valeur)
          $queryBuilder->andWhere('u.email LIKE :email')
                      ->setParameter('email', '%' . $email . '%');
      }
      
      if ($roles) {
          // Recherche exacte pour les rôles (ou ajustez selon vos besoins)
          $queryBuilder->andWhere('u.roles LIKE :roles')
                      ->setParameter('roles', '%' . $roles . '%');
      }

      // if ($firstName) {
      //     $criteria['firstName'] = $firstName;
      // }
      // if ($lastName) {
      //     $criteria['lastName'] = $lastName;
      // }
      // if ($email) {
      //     $criteria['email'] = $email;
      // }
      // if ($roles) {
      //   $criteria['roles'] = $roles;
      // }

      
      $users = $queryBuilder->getQuery()->getResult();
      // $users = $userRepository->findBy($criteria);

      $data = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);

      $data = json_decode($data);

      return new JsonResponse(['users' => $data], JsonResponse::HTTP_OK );

    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
      $userData = [
        'id' => $user->getId(),
        'email' => $user->getEmail(),
        'roles' => $user->getRoles(),
        'lastName' => $user->getLastName(),
        'firstName' => $user->getFirstName(),
        'picture' => $user->getPicture(),
        'description' => $user->getDescription(),
      ];

    // Retourner la réponse JSON
    return new JsonResponse($userData);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['PATCH'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_MANAGER")'))]
    public function edit(Request $request, User $user, EntityManagerInterface $em): Response
    {

        $data = json_decode($request->getContent(), true);

        foreach($data as $key => $value){

          if(property_exists($user, $key)){
            $setter = 'set'. ucfirst($key);
          }
          
          if(method_exists($user, $setter)){
            $user->$setter($value);
            // $this->logger->notice($value);
          }
        }

        $errors = $this->validator->validate($user);

        if(count($errors) > 0 ){
          return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->serializer->serialize($user, 'json', ['groups'=> 'user:read']);

        $data = json_decode($data);

        $em->flush();

        return new JsonResponse(['users' => $data], JsonResponse::HTTP_OK);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager)
    {
      // $this->logger->notice("DELETING");
      if (!$user) {
        return new JsonResponse([
            'error' => 'User not found'
        ], Response::HTTP_NOT_FOUND); // 404
      }
        // if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
        

            $entityManager->remove($user);
            $entityManager->flush();
        //}

        //return new JsonResponse(['message' => 'user deleted'], JsonResponse::HTTP_ACCEPTED);
        return new JsonResponse([
          'status' => 'User deleted successfully'
      ], Response::HTTP_NO_CONTENT);
    }

}
