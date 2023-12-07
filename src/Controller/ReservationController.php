<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Car;
use App\Service\CarService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends AbstractController
{
    public function __construct(private ManagerRegistry $managerRegistry, private CarService $carService)
    {
    }

    #[Route('/api/reservations', name: 'app_reservation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $carId = $data['car_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $startDate = new \DateTime($data['start_date']) ?? null;
        $endDate = new \DateTime($data['end_date']) ?? null;
        
        $car = $this->managerRegistry->getRepository(Car::class)->find($carId);
        $user = $this->managerRegistry->getRepository(User::class)->find($userId);

        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        // Check availability dates
        if (!$this->carService->isCarAvailable($car, $startDate, $endDate)) {
            return new Response("Car not available for the specified dates.", Response::HTTP_BAD_REQUEST);
        }

        // Check reservation dates
        if (!$this->carService->validateReservationDates($startDate, $endDate)) {
            return new Response("Invalid reservation dates", Response::HTTP_BAD_REQUEST);
        }

        $reservation = new Reservation();
        $reservation->setCar($car);
        $reservation->setUser($user);
        $reservation->setStartDate(new \DateTime($data['start_date']));
        $reservation->setEndDate(new \DateTime($data['end_date']));
    
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->json(['message' => 'Reservation created successfully']);
    }

    #[Route('/api/reservations/{id}', name: 'app_reservation_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $reservation = $this->managerRegistry->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $carId = $data['car_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $startDate = isset($data['start_date']) ? new \DateTime($data['start_date']) : null;
        $endDate = isset($data['end_date']) ? new \DateTime($data['end_date']) : null;

        $car = $this->managerRegistry->getRepository(Car::class)->find($carId);
        $user = $this->managerRegistry->getRepository(User::class)->find($userId);

        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Check availability dates
        if (!$this->carService->isCarAvailable($car, $startDate, $endDate)) {
            return new Response("Car not available for the specified dates.", Response::HTTP_BAD_REQUEST);
        }

        // Check reservation dates
        if (!$this->carService->validateReservationDates($startDate, $endDate)) {
            return new Response("Invalid reservation dates", Response::HTTP_BAD_REQUEST);
        }

        $reservation->setCar($car);
        $reservation->setUser($user);
        $reservation->setStartDate($startDate);
        $reservation->setEndDate($endDate);
        
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->flush();

        return $this->json(['message' => 'Reservation updated successfully']);
    }

    #[Route('/api/reservations/{id}', name: 'app_reservation_cancel', methods: ['DELETE'])]
    public function cancel(int $id): JsonResponse
    {
        $reservation = $this->managerRegistry->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $entityManager = $this->managerRegistry->getManager();
        $entityManager->remove($reservation);
        $entityManager->flush();

        return $this->json(['message' => 'Reservation canceled successfully']);
    }
}
