<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Categories;
use App\Form\CategorieFormType;
use App\Form\CategorieParentFormType;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/categories', name: 'categories_')]
class CategoriesController extends AbstractController
{
    #[Route('/{slug}', name: 'list', methods: ['GET'])]
    public function list(Categories $categories): Response
    {
        $produit = $categories->getProducts();

        return $this->render('categories/liste.html.twig', [
            'categories' => $categories, 'produits' => $produit
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajout/parent', name: 'addParent', methods: ['GET', 'POST'])]
    public function addParent(Request $request, CategoriesRepository $categorieRep, SluggerInterface $slugger, EntityManagerInterface $em): Response
    {
        $categories = new Categories();
        $form = $this->createForm(CategorieParentFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $all = $categorieRep->findAll();
            $categoriesOrder = 0;
            foreach ($all as $categ) {
                if ($categ->getName() === $form->get('name')->getData()) {
                    $this->addFlash('danger', 'Cette catégorie est déjà utilisé');
                    return $this->render('categories/addParent.html.twig', ['addCategorieForm' => $form->createView()]);
                }
                $categoriesOrder = $categoriesOrder <= $categ->getCategoriesOrder() ?  $categ->getCategoriesOrder() + 1 : $categoriesOrder;
            }
            $categories->setParent(null)->setName($form->get('name')->getData())->setSlug($slugger->slug($categories->getName())->lower())->setCategoriesOrder($categoriesOrder);
            $em->persist($categories);
            $em->flush($categories);
            return $this->redirectToRoute('main');
        }
        return $this->render('categories/addParent.html.twig', ['addCategorieForm' => $form->createView()]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajout/{id}', name: 'add', methods: ['GET', 'POST'])]
    public function add(Categories $parent, Request $request, SluggerInterface $slugger, CategoriesRepository $categorieRep, EntityManagerInterface $em): Response
    {
        $categories = new Categories();
        $form = $this->createForm(CategorieFormType::class, null, array("valueParent" => $parent->getName()));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($parent->getName() != $form->get('parent')->getData()) {
                $parent = $categorieRep->findOneByName($form->get('parent')->getData());
            }
            $categoriesOrder = $parent->getCategoriesOrder() + 1;
            $categories->setParent($parent)->setName($form->get('name')->getData())->setSlug($slugger->slug($categories->getName())->lower())->setCategoriesOrder($categoriesOrder);
            $em->persist($categories);
            $em->flush($categories);
            $all = $categorieRep->findAll();
            foreach ($all as $categ) {
                $order = $categ->getCategoriesOrder();
                if ($order >= $categoriesOrder && $categ != $categories) {
                    $categ->setCategoriesOrder($order + 1);
                    $em->persist($categ);
                    $em->flush($categ);
                }
            }
            return $this->redirectToRoute('main');
        }
        return $this->render('categories/add.html.twig', ['addCategorieForm' => $form->createView()]);
    }
}
