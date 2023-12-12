<?php

namespace App\Tests\Unit\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Car;
use App\Entity\Reservation;

class ReservationControllerTest extends WebTestCase
{
    private $client;
    private $token;
    private $tokenManager;
    private $user;
    private $car;
    private $reservation;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->tokenManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $this->token = $this->generateJwtToken();

        $managerRegistry = static::getContainer()->get('doctrine');
        $userRepository = $managerRegistry->getRepository(User::class);
        $carRepository = $managerRegistry->getRepository(Car::class);
        $reservationRepository = $managerRegistry->getRepository(Reservation::class);

        $this->user = $userRepository->findOneBy([]) ?? $userRepository->save(new User());
        $this->car = $carRepository->findOneBy([]) ?? $carRepository->save(new Car());
        $this->reservation = $reservationRepository->findOneBy([], ['id' => 'DESC']) ?? $reservationRepository->save(new Reservation());
    }

    private function generateJwtToken(): string
    {
        $managerRegistry = static::getContainer()->get('doctrine');
        $userRepository = $managerRegistry->getRepository(User::class);
        $user = $userRepository->findOneBy([]);

        if (!$user) {
            throw new \RuntimeException('No user found in the database');
        }

        $token = $this->tokenManager->create($user);
        return $token;
    }

    public function testCreateReservation(): void
    {
        $this->client->request('POST', '/api/reservations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ], json_encode([
            'car_id' => $this->car->getId(),
            'user_id' => $this->user->getId(),
            'start_date' => date('2023-11-01'),
            'end_date' => date('2023-11-10'),
        ]));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($this->car->getId(), $responseData['reservation']['car_id']);
        $this->assertSame($this->user->getId(), $responseData['reservation']['user_id']);
        $this->assertSame('2023-11-01', $responseData['reservation']['start_date']);
        $this->assertSame('2023-11-10', $responseData['reservation']['end_date']);
        $this->assertSame('Reservation created successfully', $responseData['message'] );
    }

    public function testUpdateReservation(): void
    {
        $this->client->request('PUT', '/api/reservations/' . $this->reservation->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ], json_encode([
            'car_id' => $this->car->getId(),
            'user_id' => $this->user->getId(),
            'start_date' => '2024-11-05',
            'end_date' => '2024-11-15',
        ]));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($this->car->getId(), $responseData['reservation']['car_id']);
        $this->assertSame($this->user->getId(), $responseData['reservation']['user_id']);
        $this->assertSame('2024-11-05', $responseData['reservation']['start_date']);
        $this->assertSame('2024-11-15', $responseData['reservation']['end_date']);
        $this->assertSame('Reservation updated successfully', $responseData['message'] );
    }

    public function testCancelReservation(): void
    {
        $this->client->request('DELETE', '/api/reservations/'. $this->reservation->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Reservation canceled successfully', $responseData['message'] );
    }
}