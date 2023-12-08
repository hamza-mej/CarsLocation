<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Service\CarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ReservationService;

class ReservationController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry, 
        private CarService $carService,
        private ReservationService $reservationService
    ){
    }

    #[Route('/api/reservations', name: 'app_reservation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        [$car, $user] = $this->reservationService->findCarAndUser($data['car_id'] ?? null, $data['user_id'] ?? null);

        $validationResponse = $this->reservationService->checkAvailabilityAndDates(
            $car,
            $user,
            $data['start_date'],
            $data['end_date']
        );

        if ($validationResponse !== true) {
            return $validationResponse;
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
        
        
        $reservation = $this->managerRegistry->getRepository(Reservation::class)->find($id);
        // Checking if the reservation exists
        if (!$this->managerRegistry->getRepository(Reservation::class)->find($id)) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $currentUser = $this->getUser();
        // Check if the current user is the owner of the reservation
        if ($reservation->getUser() !== $currentUser) {
            return $this->json(['error' => 'You are not allowed to update this reservation'], 403);
        }

        $data = json_decode($request->getContent(), true);

        [$car, $user] = $this->reservationService->findCarAndUser($data['car_id'] ?? null, $data['user_id'] ?? null);
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        $validationResponse = $this->reservationService->checkAvailabilityAndDates(
            $car,
            $user,
            $data['start_date'],
            $data['end_date']
        );

        if ($validationResponse !== true) {
            return $validationResponse;
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
