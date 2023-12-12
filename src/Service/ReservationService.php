<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Car;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Collection;

class ReservationService
{
    public function __construct(
        private UserRepository $userRepository,
        private ReservationRepository $reservationRepository,
        private CarService $carService, 
        private ManagerRegistry $managerRegistry
    ){
    }

    public function getUserReservations(int $userId): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $reservations = $user->getReservations();

        $reservationData = [];

        foreach ($reservations as $reservation) {
            $reservationData[$reservation->getId()] = [
                'reservation_id' => $reservation->getId(),
                'user' => $reservation->getUser(),
            ];
        }
        
        return $reservationData;
    }

    public function findCarAndUser(int $carId, int $userId): array
    {
        return [
            $this->managerRegistry->getRepository(Car::class)->find($carId),
            $this->userRepository->find($userId),
        ];
    }

    public function checkAvailabilityAndDates(Car $car, User $user, string $startDate, string $endDate): ?JsonResponse
    {
        if (!$car) {
            return new JsonResponse("Car not found", JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$user) {
            return new JsonResponse("User not found", JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$startDate || !$endDate) {
            return new JsonResponse("StartDate or endDate not found", JsonResponse::HTTP_NOT_FOUND);
        }

        $availabilityCheck = $this->carService->isCarAvailable($car, new \DateTime($startDate), new \DateTime($endDate));
        $dateValidation = $this->carService->validateReservationDates(new \DateTime($startDate), new \DateTime($endDate));

        // dump($availabilityCheck);
        if ($availabilityCheck == false) {
            return new JsonResponse("Car not available for the specified dates.", JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$dateValidation) {
            return new JsonResponse("Invalid reservation dates", JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse("Reservation is valid", JsonResponse::HTTP_OK);
    }
}
