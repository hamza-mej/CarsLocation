<?php

namespace App\Controller;

use App\Entity\Car;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class CarController extends AbstractController
{

    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/api/cars', name: 'app_cars_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $cars = $this->managerRegistry->getRepository(Car::class)->findAll();
        
        return $this->json($cars);
    }

    #[Route('/api/cars/{id}', name: 'app_car_show', methods: ['GET'])]
    public function show(int|string $id): JsonResponse
    {
        $car = $this->managerRegistry->getRepository(Car::class)->find($id);

        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        return $this->json($car);
    }

    #[Route('/api/cars', name: 'app_car_create', methods: ['POST'])]
    public function createCar(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $car = new Car();
        $car->setBrand($data['brand']);
        $car->setModel($data['model']);
        $car->setYear($data['year']);
        $car->setPrice($data['price']);

        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($car);
        $entityManager->flush();

        return $this->json(['message' => 'Car created successfully', 'id' => $car->getId()]);
    }
}
