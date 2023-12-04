<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Car;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class ReservationController extends AbstractController
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/api/reservations', name: 'app_reservation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $reservation = new Reservation();
        $reservation->setStartDate(new \DateTime($data['start_date']));
        $reservation->setEndDate(new \DateTime($data['end_date']));

        // car 
        $carId = $data['car_id'];
        $car = $this->managerRegistry->getRepository(Car::class)->find($carId);

        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        $reservation->setCar($car);

        // user 
        $userId = $data['user_id'];
        $user = $this->managerRegistry->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $reservation->setUser($user);


    
        // Check reservation dates
        if ($reservation->getStartDate() >= $reservation->getEndDate()) {
            return $this->json(['error' => 'Invalid reservation dates'], 400);
        }
    
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

        // Update reservation
        $reservation->setStartDate(new \DateTime($data['start_date']));
        $reservation->setEndDate(new \DateTime($data['end_date']));

        // Update car
        $carId = $data['car_id'];
        $car = $this->managerRegistry->getRepository(Car::class)->find($carId);

        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }

        $reservation->setCar($car);

        // Update user
        $userId = $data['user_id'];
        $user = $this->managerRegistry->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $reservation->setUser($user);

        // Check reservation dates
        if ($reservation->getStartDate() >= $reservation->getEndDate()) {
            return $this->json(['error' => 'Invalid reservation dates'], 400);
        }

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
