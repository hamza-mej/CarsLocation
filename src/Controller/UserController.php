<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\ReservationService;

#[Route('/register', name: 'app_user_register', methods: ['POST'])]
class UserController extends AbstractController
{


    public function __construct(private JWTTokenManagerInterface $jwtManager,  private ManagerRegistry $managerRegistry,
                            private UserPasswordHasherInterface $passwordHasher, private Security $security,
                            private ReservationService $reservationService)
    {
    }

    // Register
    public function register(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email']);

        // $hashedPassword = $this->passwordHasher->hashPassword(
        //     $user,
        //     $data['password']
        // );

        // $user->setPassword($hashedPassword);
        $user->setPassword($data['password']);
        
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User registered successfully']);
    }

    // Login
    #[Route('/login', name: 'app_user_login', methods: ['POST'])]
    public function login(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        // dd($user);
        $token = $this->jwtManager->create($user);
        dd($token);
        return new JsonResponse(['token' => $token]);
    }

    #[Route('/users/{id}/reservations', name: 'app_user_reservations', methods: ['GET'])]
    public function getUserReservations(int $id): JsonResponse
    {

        $userReservations = $this->reservationService->getUserReservations($id);

        return $this->json(['reservations' => $userReservations]);
    }
}
