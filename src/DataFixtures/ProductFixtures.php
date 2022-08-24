<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Faker;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductFixtures extends Fixture
{
    public function __construct(private SluggerInterface $slugger)
    {
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        for ($prod = 1; $prod <= 20; $prod++) {
            $produit = new Product();
            $produit->setName($faker->text(8));
            $produit->setDescription($faker->text());
            $produit->setSlug($this->slugger->slug($produit->getName())->lower());
            $produit->setPrice($faker->numberBetween(900, 150000));
            $produit->setStock($faker->numberBetween(0, 10));
            $categorie = $this->getReference('cat-' . rand(2, 5));
            $produit->setCategorie($categorie);
            $manager->persist($produit);
            $this->setReference('prod-' . $prod, $produit);
        }

        $manager->flush();
    }
}
