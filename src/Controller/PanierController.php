<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Product;
use App\Entity\OrderDetail;
use App\Repository\ProductRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/panier', name: 'panier_')]
class PanierController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(SessionInterface $session, ProductRepository $productRep): Response
    {
        $panier = $session->get("panier", []);
        $dataPanier = [];
        $total = 0;
        foreach ($panier as $id => $quantite) {
            $product = $productRep->find($id);
            $dataPanier[] = ["produit" => $product, "quantite" => $quantite];
            $total += $product->getPrice() * $quantite;
        }
        return $this->render(
            'panier/index.html.twig',
            [
                "dataPanier" => $dataPanier, "total" => $total
            ]
        );
    }
    #[Route('/add/{slug}', name: 'add', methods: ['GET'])]
    public function add(Product $product, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        $panier[$product->getId()] = (empty($panier[$product->getId()])) ?  1 : $panier[$product->getId()] += 1;
        $panier[$product->getId()] = ($panier[$product->getId()] <= $product->getStock()) ? $panier[$product->getId()] :  $product->getStock();
        $session->set("panier", $panier);
        return $this->redirectToRoute("main");
    }

    #[Route('/add_id/{id}', name: 'addById', methods: ['GET'])]
    public function addById(Product $product, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        $panier[$product->getId()] = (empty($panier[$product->getId()])) ?  1 : $panier[$product->getId()] += 1;
        $panier[$product->getId()] = ($panier[$product->getId()] <= $product->getStock()) ? $panier[$product->getId()] :  $product->getStock();

        $session->set("panier", $panier);
        return $this->redirectToRoute("panier_index");
    }

    #[Route('/remove_id/{id}', name: 'removeById', methods: ['GET'])]
    public function removeById(Product $product, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        if (!empty($panier[$product->getId()])) {
            if ($panier[$product->getId()] > 1) $panier[$product->getId()]--;
            else unset($panier[$product->getId()]);
        }
        $session->set("panier", $panier);
        return $this->redirectToRoute("panier_index");
    }

    #[Route('/dell_id/{id}', name: 'dellById', methods: ['GET'])]
    public function dellById(Product $product, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        if (!empty($panier[$product->getId()])) unset($panier[$product->getId()]);
        $session->set("panier", $panier);
        return $this->redirectToRoute("panier_index");
    }

    #[Route('/dell', name: 'dell', methods: ['GET'])]
    public function dell(SessionInterface $session): Response
    {
        $session->set("panier", []);
        return $this->redirectToRoute("panier_index");
    }
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/regler', name: 'buy', methods: ['GET'])]
    public function buy(SessionInterface $session, Request $request, UserInterface $user, ProductRepository $productRep, EntityManagerInterface $em, SendMailService $mail): Response
    {
        $letters = 'abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $ref = substr(str_shuffle($letters), 0, 20);
        $orders = new Orders();
        $orders->setUser($user);
        $orders->setReference($ref);
        $em->persist($orders);
        $em->flush($orders);
        $panier = $session->get("panier", []);
        $total = 0;
        $recap = [];
        foreach ($panier as $id => $quantite) {
            $product = $productRep->find($id);
            $totalProduit = $product->getPrice() * $quantite;
            $recap[$product->getName()]['prix'] = $totalProduit;
            $recap[$product->getName()]['quantite'] = $quantite;
            $orderDetail = new OrderDetail();
            $orderDetail->setProduct($product);
            $orderDetail->setOrders($orders);
            $orderDetail->setPrice($totalProduit);
            $orderDetail->setQuantity($quantite);
            $em->persist($orderDetail);
            $em->flush($orderDetail);
            $total += $totalProduit;
            $product->setStock($product->getStock() - $quantite);
            $em->persist($product);
            $em->flush($product);
        }
        $date = getdate();
        $context = compact("recap", "ref", "total", "date");
        $mail->send(
            'no-replay@e-commerce.fr',
            $user->getEmail(),
            'reçu de caisse',
            'buy',
            $context
        );
        $session->set("panier", []);
        $this->addFlash('success', 'Votre commande a bien été validé. Un mail vous a été envoyé');
        return $this->redirectToRoute("main");
    }
}
