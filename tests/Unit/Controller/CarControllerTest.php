<?php

namespace App\Tests\Unit\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Car;

class CarControllerTest extends WebTestCase
{
    private $client;
    private $token;
    private $tokenManager;
    private $car;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->tokenManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $this->token = $this->generateJwtToken();

        $managerRegistry = static::getContainer()->get('doctrine');
        $carRepository = $managerRegistry->getRepository(Car::class);
        $this->car = $carRepository->findOneBy([]) ?? $carRepository->save(new Car());
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

    public function testIndex(): void
    {
        $this->client->request(
            'GET', 
            '/api/cars',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            ]
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Car list successfully retrieved', $responseData['message'] );
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testShowCar(): void
    {
        $this->client->request(
            'GET', 
            '/api/cars/' . $this->car->getId()
            ,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            ]
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($this->car->getId(), $responseData['car_id'] );
        $this->assertSame('Car details successfully shown', $responseData['message'] );
    }

    public function testCreateCar(): void
    {
        $this->client->request('POST', '/api/cars', [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            ],
            json_encode([
                'brand' => 'TestBrand',
                'model' => 'TestModel',
                'year' => '2023',
                'price' => '10000',
        ]));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('TestBrand', $responseData['car']['brand']);
        $this->assertSame('TestModel', $responseData['car']['model']);
        $this->assertSame('2023', $responseData['car']['year']);
        $this->assertSame('10000', $responseData['car']['price']);
    }
}
