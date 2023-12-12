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

        if (!($validationResponse && $validationResponse->getStatusCode() === 200)) {
            return $this->json(['message' => 'Invalid status code in the JSON response'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $reservation = new Reservation();
        $reservation->setCar($car);
        $reservation->setUser($user);
        $reservation->setStartDate(new \DateTime($data['start_date']));
        $reservation->setEndDate(new \DateTime($data['end_date']));
    
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        $createdReservation = $this->managerRegistry->getRepository(Reservation::class)->find($reservation->getId());

        return $this->json(['message' => 'Reservation created successfully', 'reservation' => [
            'id' => $createdReservation->getId(),
            'car_id' => $createdReservation->getCar()->getId(),
            'user_id' => $createdReservation->getUser()->getId(),
            'start_date' => $createdReservation->getStartDate()->format('Y-m-d'),
            'end_date' => $createdReservation->getEndDate()->format('Y-m-d'),
        ]], JsonResponse::HTTP_OK);
    }

    #[Route('/api/reservations/{id}', name: 'app_reservation_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $reservation = $this->managerRegistry->getRepository(Reservation::class)->find($id);
        // Checking if the reservation exists
        if (!$this->managerRegistry->getRepository(Reservation::class)->find($id)) {
            return $this->json(['error' => 'Reservation not found'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $currentUser = $this->getUser();
        // Check if the current user is the owner of the reservation
        if ($reservation->getUser() !== $currentUser) {
            return $this->json(['error' => 'You are not allowed to update this reservation'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        [$car, $user] = $this->reservationService->findCarAndUser($data['car_id'] ?? null, $data['user_id'] ?? null);
        $startDate = new \DateTime($data['start_date'] ?? 'now');
        $endDate = new \DateTime($data['end_date'] ?? 'now'); 

        $reservation->setCar($car);
        $reservation->setUser($user);
        $reservation->setStartDate($startDate);
        $reservation->setEndDate($endDate);
        
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->flush();

        $updatedReservation = $this->managerRegistry->getRepository(Reservation::class)->find($reservation->getId());

        return $this->json(['message' => 'Reservation updated successfully', 'reservation' => [
            'car_id' => $updatedReservation->getCar()->getId(),
            'user_id' => $updatedReservation->getUser()->getId(),
            'start_date' => $updatedReservation->getStartDate()->format('Y-m-d'),
            'end_date' => $updatedReservation->getEndDate()->format('Y-m-d'),
        ]], JsonResponse::HTTP_OK);
    }

    #[Route('/api/reservations/{id}', name: 'app_reservation_cancel', methods: ['DELETE'])]
    public function cancel(int $id): JsonResponse
    {
        $reservation = $this->managerRegistry->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->managerRegistry->getManager();
        $entityManager->remove($reservation);
        $entityManager->flush();

        return $this->json(['message' => 'Reservation canceled successfully'], JsonResponse::HTTP_OK);
    }
}
