<?php

namespace App\Service;

use App\Entity\Car;
use App\Repository\ReservationRepository;

class CarService
{
    public function __construct(private ReservationRepository $reservationRepository)
    {
    }

    public function isCarAvailable(Car $car, \DateTimeInterface $startDate, \DateTimeInterface $endDate): bool
    {
        $existingReservations = $this->reservationRepository->findReservationsForCarBetweenDates($car, $startDate, $endDate);

        return empty($existingReservations);
    }

    public function validateReservationDates(\DateTime $startDate, \DateTime $endDate): bool
    {
        return $startDate <= $endDate;
    }
}