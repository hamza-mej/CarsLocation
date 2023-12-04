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

#[Route('/register', name: 'user_register', methods: ['POST'])]
class UserController extends AbstractController
{
    private $jwtManager;
    private $managerRegistry;
    private $passwordHasher;

    public function __construct(JWTTokenManagerInterface $jwtManager, ManagerRegistry $managerRegistry, UserPasswordHasherInterface $passwordHasher)
    {
        $this->managerRegistry = $managerRegistry;
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
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
    #[Route('/login', name: 'user_login', methods: ['POST'])]
    public function login(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        // dd($user);
        $token = $this->jwtManager->create($user);
        dd($token);
        return new JsonResponse(['token' => $token]);
    }
}
