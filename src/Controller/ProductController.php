<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Categories;
use App\Entity\Image;
use App\Entity\User;
use App\Form\AddImageFormType;
use App\Form\ProductFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/produit', name: 'product_')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
        ]);
    }

    #[Route('/{slug}', name: 'details', methods: ['GET'])]
    public function details(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            if (in_array("ROLE_ADMIN", $this->getUser()->getRoles(), true)) {
                $form = $this->createForm(AddImageFormType::class);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $image = new Image();
                    $image->setName("image" . $product->getSlug('name'));
                    $image->setImageName("image" . $product->getSlug('name'));
                    $image->setImageFile($form->get('imageFile')->getData());
                    $image->setProduct($product);
                    $em->persist($image);
                    $em->flush($image);
                    return $this->redirectToRoute('product_details', ["slug" => $product->getSlug()]);
                }
                return $this->render('product/details.html.twig', [
                    'product' => $product, 'addImageForm' => $form->createView()
                ]);
            }
        }
        return $this->render('product/details.html.twig', [
            'product' => $product
        ]);
    }
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('ajout/{id}', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request, Categories $categories, SluggerInterface $slugger, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit = new Product();
            $produit->setName($form->get('name')->getData())
                ->setDescription($form->get('description')->getData())
                ->setPrice($form->get('price')->getData())
                ->setStock($form->get('stock')->getData())
                ->setCategorie($categories)
                ->setSlug($slugger->slug($form->get('name')->getData())->lower());
            $em->persist($produit);
            $em->flush($produit);
            foreach ($form->get('imageFile')->getData() as $imageFile) {
                $image = new Image();
                $image->setName("image" . $form->get('name')->getData());
                $image->setImageName("image" . $form->get('name')->getData());
                $image->setImageFile($imageFile);
                $image->setProduct($produit);
                $em->persist($image);
                $em->flush($image);
            }
            return $this->redirectToRoute('product_details', ["slug" => $produit->getSlug()]);
        }

        return $this->render('product/add.html.twig', ['addProductForm' => $form->createView(), 'categorie' => $categories]);
    }
}
