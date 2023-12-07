<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class ReservationService
{
    public function __construct(private UserRepository $userRepository)
    {
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
}
