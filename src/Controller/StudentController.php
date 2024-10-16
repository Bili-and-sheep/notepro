<?php

namespace App\Controller;

use App\Entity\ClassLevel;
use App\Entity\Grade;
use App\Entity\PreviousPasswords;
use App\Entity\Professor;
use App\Entity\Student;
use App\Entity\User;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant')]
class StudentController extends AbstractController
{
    #[Route('/', name: 'app_student_index', methods: ['GET'])]
    public function index(StudentRepository $studentRepository): Response
    {
        return $this->render('student/index.html.twig', [
            'students' => $studentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_student_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $student->setRoles(['ROLE_STUDENT']);
            $student->setPassword(
                $userPasswordHasher->hashPassword(
                    $student,
                    $form->get('password')->getData()
                )
            );

            //encode the plain password for saving it as a previous password
            $previousPassword = new PreviousPasswords($student);
            $previousPassword->setPassword(
                $userPasswordHasher->hashPassword(
                    $previousPassword,
                    $form->get('password')->getData()
                )
            );
            $student->addPreviousPasswords($previousPassword);

            $entityManager->persist($student);
            $entityManager->persist($previousPassword);
            $entityManager->flush();


            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    public function show(Student $student): Response
    {
        return $this->render('student/show.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StudentType::class, $student);
        $form->remove('password');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_delete', methods: ['POST'])]
    public function delete(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $student->getId(), $request->request->get('_token'))) {
            $entityManager->remove($student);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/notes', name: 'app_student_notes', methods: ['GET', 'POST'])]
    public function notes(Student $student, EntityManagerInterface $entityManager): Response
    {

        // Ajout d'un tableau qui vas contenir toutes les notes de l'eleve
        $notes = [];
        // On parcour toutes les notes de l'éléves et on l'ajoute au tableau
        foreach ($student->getGrades() as $grade) {
            $notes[] = $grade->getGrade();
        }
        // On fait la moyenne grâce à la méthode que j'ai créer en desosus
        $average = $this->calculateAverage($notes);


        return $this->render('student/mygrades.html.twig', [
            'student' => $student,
            'grades' => $student->getGrades(),
            'average' => $average,

        ]);


    }

    private function calculateAverage(array $grades): ?float
    {
        //Si l'éléve n'as pas de note alors on fait rien
        if (count($grades) === 0) {
            return null;
        }
        //sinon on commence le calcul
        $total = array_sum($grades);
        return $total / count($grades);
    }


    #[Route('/{id}/prof', name: 'app_student_prof', methods: ['GET', 'POST'])]
    public function prof(Student $student, EntityManagerInterface $entityManager): Response
    {
        // Fetch professors associated with the specific student's class level
        $classLevel = $student->getClassLevel();
        $professors = [];

        if ($classLevel) {
            $professors = $entityManager->createQueryBuilder()
                ->select('p')
                ->from(Professor::class, 'p')
                ->innerJoin('p.classLevels', 'cl')
                ->where('cl.id = :classLevelId')
                ->setParameter('classLevelId', $classLevel->getId())
                ->getQuery()
                ->getResult();
        }

        return $this->render('student/myprof.html.twig', [
            'student' => $student,
            'professors' => $professors,
        ]);
    }
//        return $this->render('student/myprof.html.twig');


}
