<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\Grade;
use App\Entity\Student;
use App\Form\EvaluationType;
use App\Form\SetGradeType;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/evaluation')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'app_evaluation_index', methods: ['GET'])]
    //#[IsGranted('ROLE_ADMIN')]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        $evaluations = $evaluationRepository->findBy(['professor' => $this->getUser()]);

        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluations,
        ]);
    }

    #[Route('/new', name: 'app_evaluation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evaluation = new Evaluation();
        $evaluation->setProfessor($this->getUser());

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evaluation);
            $entityManager->flush();

            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evaluation_delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/notes', name: 'app_evaluation_set_grades', methods: ['GET', 'POST'])]
    public function notes(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SetGradeType::class,null,['students' => $evaluation->getClassLevel()->getStudents(), 'evaluation' => $evaluation]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->getData() as $key => $value){
                $gradeOrPresence = explode('_', $key);
                if ($gradeOrPresence[0] == 'grade'){
                    $student = $entityManager->getRepository(Student::class)->find($gradeOrPresence[1]);
                    $grade = $entityManager->getRepository(Grade::class)->findOneBy(['student' => $student, 'evaluation' => $evaluation]);
                    if($grade == null){
                        $grade = new Grade();
                        $grade->setEvaluation($evaluation);
                        $grade->setStudent($student);
                        $evaluation->addGrade($grade);
                    }
                    $grade->setGrade($value);
                } else {
                    $student = $entityManager->getRepository(Student::class)->find($gradeOrPresence[1]);
                    $grade = $evaluation->getGradeByStudent($student);
                    $grade->setPresent(!$value);

                    $entityManager->persist($grade);
                }
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        // Je récupère les notes des évaluations
        $grades = [];
        // On parcour toutes les notes de l'évaluation
        foreach ($evaluation->getGrades() as $grade) {
            $grades[] = $grade->getGrade();
        }
        // On fait la moyenne grâce à la méthode que j'ai créer en desosus
        $average = $this->calculateAverage($grades);

        return $this->render('evaluation/myprof.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
            'average' => $average,
        ]);
    }

    private function calculateAverage(array $grades): ?float
    {
        if (count($grades) === 0) {
            return null;
        }
        $total = array_sum($grades);
        return $total / count($grades);
    }
}
