<?php

namespace App\Controller;

use App\Entity\LanguagesAvailable;
use App\Repository\LanguagesAvailableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;


class LanguagesAvailableController extends AbstractController
{
    /**
     * Récupère la liste de toutes les langues disponibles, avec possibilité de filtrer par nom.
     *
     * Si aucun paramètre n'est passé, toutes les langues sont renvoyées.
     * Si un paramètre 'name' est passé, il filtre les langues par nom.
     *
     * @param Request $request La requête contenant les paramètres de filtre éventuels
     * @param LanguagesAvailableRepository $languagesRepository Le repository pour accéder aux langues
     * @param SerializerInterface $serializer Le service pour sérialiser les entités en JSON
     * @return JsonResponse Une réponse JSON contenant les langues trouvées
     */
    #[Route('/api/languages', name: 'languages_index', methods: ['GET'])]
    public function index(LanguagesAvailableRepository $languagesRepository, Request $request, SerializerInterface $serializer): JsonResponse
    {
        // Récupérer les paramètres de la requête pour le filtrage (par nom)
        $name = $request->query->get('name');

        // Si un nom est passé, filtrer les langues par ce nom, sinon récupérer toutes les langues
        if ($name) {
            $languages = $languagesRepository->findByNameLike($name);
        } else {
            $languages = $languagesRepository->findAll();
        }

        // Sérialiser les langues récupérées
        $jsonLanguages = $serializer->serialize($languages, 'json', ['groups' => 'language:read']);

        // Retourner la liste des langues au format JSON avec un statut HTTP 200 (OK)
        return new JsonResponse($jsonLanguages, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/languages/{id}', name: 'languages_show', methods: ['GET'])]
    public function show(int $id, LanguagesAvailableRepository $languagesAvailableRepository, SerializerInterface $serializer): JsonResponse
    {
        // Recherche la langue avec l'ID donné
        $language = $languagesAvailableRepository->find($id);

        // Si la langue n'existe pas, renvoyer une réponse 404
        if (!$language) {
            return new JsonResponse(['error' => 'Language not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Sérialiser la langue trouvée
        $jsonLanguage = $serializer->serialize($language, 'json', ['groups' => 'language:read']);

        // Renvoyer la langue sérialisée avec le statut 200 OK
        return new JsonResponse($jsonLanguage, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Crée une nouvelle langue disponible.
     * 
     * @param Request $request La requête contenant les données de la langue à créer
     * @param EntityManagerInterface $em Le gestionnaire d'entités pour la persistance
     * @param ValidatorInterface $validator Le validateur pour les entités
     * @param SerializerInterface $serializer Le service de sérialisation
     * @return JsonResponse Une réponse JSON contenant la langue créée ou un message d'erreur
     */
    #[Route('/api/languages', name: 'languages_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $language = new LanguagesAvailable();
        $language->setName($data['name'] ?? null);

        $errors = $validator->validate($language);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($language);
        $em->flush();

        $jsonLanguage = $serializer->serialize($language, 'json', ['groups' => 'language:read']);
        return new JsonResponse($jsonLanguage, JsonResponse::HTTP_CREATED, [], true);
    }

    /**
     * Met à jour une langue existante.
     * 
     * @param Request $request La requête contenant les données à mettre à jour
     * @param LanguagesAvailable $language L'entité de langue à mettre à jour
     * @param EntityManagerInterface $em Le gestionnaire d'entités pour sauvegarder les modifications
     * @param ValidatorInterface $validator Le validateur pour valider les données
     * @param SerializerInterface $serializer Le service de sérialisation
     * @return JsonResponse Une réponse JSON contenant la langue mise à jour
     */
    #[Route('/api/languages/{id}', name: 'languages_update', methods: ['PATCH'])]
    public function update(Request $request, LanguagesAvailable $language, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $language->setName($data['name']);
        }

        $errors = $validator->validate($language);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        $jsonLanguage = $serializer->serialize($language, 'json', ['groups' => 'language:read']);
        return new JsonResponse($jsonLanguage, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Supprime une langue existante.
     * 
     * @param LanguagesAvailable $language La langue à supprimer
     * @param EntityManagerInterface $em Le gestionnaire d'entités pour la suppression
     * @return JsonResponse Une réponse JSON confirmant la suppression
     */
    #[Route('/api/languages/{id}', name: 'languages_delete', methods: ['DELETE'])]
    public function delete(LanguagesAvailable $language, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($language);
        $em->flush();

        return new JsonResponse(['message' => 'Language deleted'], JsonResponse::HTTP_NO_CONTENT);
    }
}
