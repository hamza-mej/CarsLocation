<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Car;
use Doctrine\Persistence\ManagerRegistry;

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

        return $reservations->toArray();
    }

    public function findCarAndUser(int $carId, int $userId): array
    {
        return [
            $this->managerRegistry->getRepository(Car::class)->find($carId),
            $this->userRepository->find($userId),
        ];
    }

    public function checkAvailabilityAndDates(Car $car, User $user, string $startDate, string $endDate): ?Response
    {
        if (!$car) {
            return new Response("Car not found", Response::HTTP_NOT_FOUND);
        }

        if (!$user) {
            return new Response("User not found", Response::HTTP_NOT_FOUND);
        }

        if (!$startDate || !$endDate) {
            return new Response("StartDate or endDate not found", Response::HTTP_NOT_FOUND);
        }

        $availabilityCheck = $this->carService->isCarAvailable($car, new \DateTime($startDate), new \DateTime($endDate));
        $dateValidation = $this->carService->validateReservationDates(new \DateTime($startDate), new \DateTime($endDate));

        if (!$availabilityCheck) {
            return new Response("Car not available for the specified dates.", Response::HTTP_BAD_REQUEST);
        }

        if (!$dateValidation) {
            return new Response("Invalid reservation dates", Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
