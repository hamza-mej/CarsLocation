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
        $role = $decoded->role;
   
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setRoles($role);
        $em = $this->managerRegistry->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'User registered successfully', 'user' => [
            'email' => $user->getEmail(),
        ]]);
    }

    // Login
    #[Route('/api/login', name: 'app_user_login', methods: ['POST'])]
    public function login(Request $request)
    {
        $decoded = json_decode($request->getContent(), true);

        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $decoded['email']]);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $token = $this->jwtManager->create($user);
        // return new JsonResponse(['token' => $token]);
        return $this->json(['message' => 'Login successful', 'user' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ], 'token' => $token]);
    }

    // Allows user to view their own reservations.
    #[Route('/api/users/{id}/reservations', name: 'app_user_reservations', methods: ['GET'])]
    public function getUserReservations(int $id): JsonResponse
    {
        $userReservations = $this->reservationService->getUserReservations($id);
        
        // Assuming at least one reservation is present
        $lastReservation = end($userReservations);
        $user = $lastReservation['user'];

        return $this->json([
            'last_reservation_id' => $lastReservation['reservation_id'],
            'user_id' => $user->getId(),
            'message' => 'Data retrieved successfully',
        ]);
    }
}
