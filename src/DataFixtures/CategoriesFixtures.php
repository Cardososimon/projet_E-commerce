<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoriesFixtures extends Fixture
{
    private $counter = 1;
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $parent = $this->createCategories('Informatique', null, 1, $manager);
        $this->createCategories('Ordinateur', $parent, 2, $manager);
        $this->createCategories('Ecran', $parent, 3, $manager);
        $this->createCategories('Clavier', $parent, 4, $manager);
        $this->createCategories('Souris', $parent, 5, $manager);


        $manager->flush();
    }
    public function createCategories(string $name, Categories $parent = null, int $ordre, ObjectManager $manager)
    {
        $categorie = new Categories();
        $categorie->setName($name);
        $categorie->setSlug($this->slugger->slug($categorie->getName())->lower());
        $categorie->setParent($parent);
        $categorie->setCategoriesOrder($ordre);
        $manager->persist($categorie);

        $this->addReference('cat-' . $this->counter, $categorie);
        $this->counter++;
        return $categorie;
    }
}
