<?php

namespace App\Controller;

use App\Entity\Coupon;
use App\Form\CouponType;
use App\Repository\CouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


class CouponController extends AbstractController
{
    #[Route('/coupon', name: 'app_coupon_index')]
    public function index(CouponRepository $couponRepository): Response
    {
        return $this->render('coupon/index.html.twig', [
            'coupons' => $couponRepository->findAll(),
        ]);
    }

    #[Route('/coupon/new', name: 'app_coupon_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $coupon = new Coupon();
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($coupon);
            $em->flush();

            return $this->redirectToRoute('app_coupon_index');
        }

        return $this->render('coupon/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/coupon/{id}/edit', name: 'app_coupon_edit')]
    public function edit(Request $request, Coupon $coupon, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_coupon_index');
        }

        return $this->render('coupon/edit.html.twig', [
            'form' => $form->createView(),
            'coupon' => $coupon,
        ]);
    }

    #[Route('/coupon/{id}/delete', name: 'app_coupon_delete')]
    public function delete(Coupon $coupon, EntityManagerInterface $em): Response
    {
        $em->remove($coupon);
        $em->flush();

        return $this->redirectToRoute('app_coupon_index');
    }

    #[Route('/api/coupon/{code}', name: 'api_coupon', methods: ['GET'])]
public function getCouponByCode(string $code, CouponRepository $couponRepo): JsonResponse
{
    $coupon = $couponRepo->findOneBy(['code' => $code]);

    if ($coupon) {
        return $this->json([
            'valid' => true,
            'remise' => $coupon->getRemise()
        ]);
    }

    return $this->json(['valid' => false]);
}

}
