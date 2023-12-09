<?php

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\User;
use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->loadCars($manager);
        $this->loadUsers($manager);
        $this->loadReservations($manager);
    }

    protected function loadCars(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 5; $i++) {
            $car = new Car();
            $car->setBrand($faker->word);
            $car->setModel($faker->word);
            $car->setYear($faker->year);
            $car->setPrice($faker->randomFloat(2, 10000, 50000));

            $manager->persist($car);
        }

        $manager->flush();
    }

    protected function loadUsers(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));

            $manager->persist($user);
        }

        $manager->flush();
    }

    protected function loadReservations(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $cars = $manager->getRepository(Car::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        for ($i = 0; $i < 5; $i++) {
            $reservation = new Reservation();
            $reservation->setUser($faker->randomElement($users));
            $reservation->setCar($faker->randomElement($cars));
            $reservation->setStartDate($faker->dateTimeThisMonth);
            $reservation->setEndDate($faker->dateTimeThisMonth);

            $manager->persist($reservation);
        }

        $manager->flush();
    }
}
