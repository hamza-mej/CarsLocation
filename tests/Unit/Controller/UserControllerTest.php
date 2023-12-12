<?php

namespace App\Tests\Unit\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Car;
use App\Entity\Reservation;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $token;
    private $tokenManager;
    private $user;
    private $reservation;
    private $car;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->tokenManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $this->token = $this->generateJwtToken();
    }
    
    private function generateJwtToken(): string
    {
        $managerRegistry = static::getContainer()->get('doctrine');
        $userRepository = $managerRegistry->getRepository(User::class);
        $carRepository = $managerRegistry->getRepository(Car::class);
        $this->user = $userRepository->findOneBy([], ['id' => 'DESC']);
        $this->car = $carRepository->findOneBy([], ['id' => 'DESC']);

        if (!$this->user) {
            throw new \RuntimeException('No user found in the database');
        }

        $token = $this->tokenManager->create($this->user);
        return $token;
    }
    
    public function testRegisterUser(): void
    {
        $timestamp = time();
        $email = 'test' . $timestamp . '@example.com';

        $this->client->request('POST', '/api/register', [], [], [], json_encode([
            'email' => $email,  
            'password' => '$2y$10$MKN962f3FfJf9Tj7oVrfQuynjtK8tZiN/Dd183aRr0dzJTgOT.JI6',
            'role' => ['ROLE_USER']
        ]));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($email, $responseData['user']['email']);
        $this->assertSame('User registered successfully', $responseData['message']);
    }

    public function testLoginUser(): void
    {
        $latestUser = $this->user;
        $email = $latestUser instanceof User ? $latestUser->getEmail() : 'loginUserTest@example.com';

        $this->client->request('POST', '/api/login', [], [], [], json_encode([
            'email' => $email
        ]));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Login successful', $responseData['message']);
        $this->assertSame($email, $responseData['user']['email']);
        $this->assertNotEmpty($responseData['token']);
    }

    public function testGetUserReservations(): void
    {
        // Create reservation
        $reservationData = [
            'user_id' => $this->user->getId(),
            'car_id' => $this->car->getId(),
            'start_date' => (new \DateTime())->format('Y-m-d'),
            'end_date' => (new \DateTime())->modify('+' . mt_rand(1, 30) . ' days')->format('Y-m-d'),
        ];
    
        $this->client->request('POST', '/api/reservations', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token,
        ], json_encode($reservationData));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $createdReservation = json_decode($this->client->getResponse()->getContent(), true);
        $reservation_id = $createdReservation['reservation']['id'];
    
        // Get user reservations
        $this->client->request('GET', '/api/users/'. $this->user->getId() .'/reservations', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token,
        ]);

        $managerRegistry = static::getContainer()->get('doctrine');
        $reservationRepository = $managerRegistry->getRepository(Reservation::class);
        $this->reservation = $reservationRepository->findOneById($reservation_id);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($this->user->getId(), $responseData['user_id']);
        $this->assertSame($this->reservation->getId(), $responseData['last_reservation_id']);
        $this->assertSame('Data retrieved successfully', $responseData['message']);
    }
}
