<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Form\ConfigProfType;
use App\Form\SelectSubjectType;
use App\Form\SubjectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigProfController extends AbstractController
{
    #[Route('/configprof/{id}', name: 'app_config_prof')]
    public function index(Request $request, EntityManagerInterface $entityManager, Professor $professor): Response
    {
        $formProfs = $this->createForm(ConfigProfType::class, $professor);
        $formProfs->handleRequest($request);

        if ($formProfs->isSubmitted() && $formProfs->isValid()) {
            $entityManager->persist($professor);
            $entityManager->flush();
        }

        return $this->render('config_prof/index.html.twig', [
            'formProfs' => $formProfs->createView(),
        ]);
    }
    #[Route('/configprof/', name: 'app_config_prof_subject')]
    public function configProfSubject(Request $request, EntityManagerInterface $entityManager, ?Professor $professor): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès interdit pour les non-administrateurs.');

        $formProfs = $this->createForm(ConfigProfType::class, $professor);
        $formProfs->remove('subjects');
        $formProfs->remove('classes');

        $formProfs->handleRequest($request);

        if ($formProfs->isSubmitted() && $formProfs->isValid()) {
            $selectedProf = $formProfs->get('professor')->getData();
            return $this->redirectToRoute('app_config_prof', ['id' => $selectedProf->getId()]);
        }

        return $this->render('config_prof/index.html.twig', [
            'formProfs' => $formProfs->createView(),
        ]);
    }
}
