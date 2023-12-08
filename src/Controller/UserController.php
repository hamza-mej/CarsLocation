<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ReservationService;


class UserController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,  
        private ManagerRegistry $managerRegistry,
        private ReservationService $reservationService
    ){
    }

    // Register
    #[Route('/api/register', name: 'app_user_register', methods: ['POST'])]
    public function register(Request $request)
    {
        $decoded = json_decode($request->getContent());
        $email = $decoded->email;
        $password = $decoded->password;
   
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $em = $this->managerRegistry->getManager();
        $em->persist($user);
        $em->flush();
   
        return $this->json(['message' => 'Registered Successfully']);
    }

    // Login
    #[Route('/api/login', name: 'app_user_login', methods: ['POST'])]
    public function login(Request $request)
    {
        $decoded = json_decode($request->getContent(), true);

        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $decoded['email']]);

        $token = $this->jwtManager->create($user);
        return new JsonResponse(['token' => $token]);
    }

    // Allows user to view their own reservations.
    #[Route('/api/users/{id}/reservations', name: 'app_user_reservations', methods: ['GET'])]
    public function getUserReservations(int $id): JsonResponse
    {
        $userReservations = $this->reservationService->getUserReservations($id);
        return $this->json(['reservations' => $userReservations]);
    }
}
