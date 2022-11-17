<?php

namespace App\Controller;

use App\Model\ExerciseManager;
use App\Model\SubjectManager;
use App\Model\NotionManager;

class NotionController extends AbstractController
{
    /**
     * Display home page
     */
    public function show(string $notionId): string
    {
        if (!is_numeric($notionId)) {
            header("Location: /");
        }

        if (!isset($_SESSION['theme_id']) || !isset($_SESSION['theme_name'])) {
            // var_dump($_SESSION);
            // exit();
            return "Session variables undefined";
        }

        //Récuperer l'id du sujet de la notion
        $notionManager = new NotionManager();
        $subjectId = $notionManager->selectOneById((int)$notionId)['subject_id'];

        //récuperer toutes les notions du sujet
        $notions = $notionManager->selectAllBySubject($subjectId);

        //récuperer le sujet et tous les sujets à partir du theme
        $subjectManager = new SubjectManager();
        $subjects = $subjectManager->selectAllByTheme((int)$_SESSION['theme_id']);
        $subject = $subjectManager->selectOneById((int)$subjectId);

        // récupérer tous les exercices d'un notion
        $exerciseManager = new ExerciseManager();
        $exercises = $exerciseManager->selectAllByNotion($notionId);

        return $this->twig->render(
            'Notion/index.html.twig',
            [
                'headerTitle' => $_SESSION['theme_name'],
                'subjects' => $subjects,
                'subjectname' => $subject['name'],
                'notions' => $notions,
                'notion' => $notionManager->selectOneById((int)$notionId),
                'exercises' => $exercises,
                'subjectId' => $subjectId
            ]
        );
    }


    public function delete(string $notionId): void
    {

        if (!is_numeric($notionId)) {
            header("Location: /");
        }

        $notionManager = new NotionManager();
        $subjectId = $notionManager->selectOneById((int)$notionId)['subject_id'];

        $notionManager->delete((int)$notionId);

        header("Location: /subject/show?id=" . $subjectId);
    }

    public function add(string $subjectId): string
    {

        if (!is_numeric($subjectId)) {
            header("Location: /");
        }

        if (!isset($_SESSION['theme_id']) || !isset($_SESSION['theme_name'])) {
            return "Session variables undefined";
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if (isset($_POST['button']) && $_POST['button'] == "Valider") {

                $fileNameImg = "";

                //button Valider
                if (isset($_FILES['filename']) && $_FILES['filename']['name'] != "") {
                    $uploadDir = '../upload/';
                    $fileNameImg = $uploadDir . basename($_FILES['filename']['name']);
                    $extension = pathinfo($_FILES['filename']['name'], PATHINFO_EXTENSION);
                    $authorizedExtensions = ['jpg', 'jpeg', 'png'];
                    $maxFileSize = 1000000;
                    $errors = [];

                    if ((!in_array($extension, $authorizedExtensions))) {
                        $errors[] = 'Veuillez sélectionner une image de type Jpg ou Jpeg ou Png !';
                    }

                    if (file_exists($_FILES['filename']['tmp_name']) && filesize($_FILES['filename']['tmp_name']) > $maxFileSize) {
                        $errors[] = "Votre fichier doit faire moins de 1M !";
                    }

                    if (!empty($errors)) {
                        return $this->twig->render(
                            'Notion/add.html.twig',
                            [
                                'headerTitle' => $_SESSION['theme_name'],
                                'titleForm' => 'Ajouter une nouvelle notion',
                                'subjectId' => $subjectId,
                                'FileErrors' => $errors
                            ]
                        );
                    }
                }

                $notionName = trim($_POST['notion']);

                if ($notionName == "") {
                    $errors[] = "Veuillez saisir le champ";

                    return $this->twig->render(
                        'Notion/add.html.twig',
                        [
                            'headerTitle' => $_SESSION['theme_name'],
                            'titleForm' => 'Ajouter une nouvelle notion',
                            'subjectId' => $subjectId,
                            'NameErrors' => $errors
                        ]
                    );
                }

                $lesson = trim($_POST['lesson']);
                $sample = trim($_POST['sample']);

                $notionManager = new NotionManager();

                if (($notionManager->getName($notionName, $subjectId))) {
                    $errors[] = "Notion déjà existante";

                    return $this->twig->render(
                        'Notion/add.html.twig',
                        [
                            'headerTitle' => $_SESSION['theme_name'],
                            'titleForm' => 'Ajouter une nouvelle notion',
                            'subjectId' => $subjectId,
                            'NameErrors' => $errors
                        ]
                    );
                }

                $newNotionId = $notionManager->add((int)$subjectId, $notionName, $lesson, $sample, $fileNameImg);

                return $this->twig->render(
                    'Notion/add.html.twig',
                    [
                        'headerTitle' => $_SESSION['theme_name'],
                        'titleForm' => 'Ajouter une nouvelle notion',
                        'validationMessage' => 'Bravo ! la nouvelle notion ' . $notionName .  ' a bien été ajoutée.',
                        'notionId' => $newNotionId,
                        'subjectId' => $subjectId
                    ]
                );
            }
        }

        return $this->twig->render(
            'Notion/add.html.twig',
            [
                'headerTitle' => $_SESSION['theme_name'],
                'titleForm' => 'Ajouter une nouvelle notion',
                'subjectId' => $subjectId
            ]
        );
    }


    public function edit(string $notionId): string
    {
        if (!is_numeric($notionId)) {
            header("Location: /");
        }

        if (!isset($_SESSION['theme_id']) || !isset($_SESSION['theme_name'])) {
            return "Session variables undefined";
        }

        $notionManager = new NotionManager();
        $subjectId = $notionManager->selectOneById((int)$notionId)['subject_id'];

        $validationMessage = "";

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['button'])) {
                if ($_POST['button'] == "Annuler") {
                    header("Location: /subject/show?id=" . $subjectId);
                    return "";
                }

                if ($_POST['button'] == "Valider") {
                    $notionName = trim($_POST['notion']);
                    $lesson = trim($_POST['lesson']);
                    $sample = trim($_POST['sample']);
                    $fileNameImg = "";

                    // var_dump($_FILES);
                    // exit();
                    if (isset($_FILES['filename']) && $_FILES['filename']['name'] != "") {
                        $uploadDir = '../upload/';
                        $fileNameImg = $uploadDir . basename($_FILES['filename']['name']);
                        $extension = pathinfo($_FILES['filename']['name'], PATHINFO_EXTENSION);
                        $authorizedExtensions = ['jpg', 'jpeg', 'png'];
                        $maxFileSize = 1000000;
                        $errors = [];

                        if ((!in_array($extension, $authorizedExtensions))) {
                            $errors[] = 'Veuillez sélectionner une image de type Jpg ou Jpeg ou Png !';
                        }

                        if (file_exists($_FILES['filename']['tmp_name']) && filesize($_FILES['filename']['tmp_name']) > $maxFileSize) {
                            $errors[] = "Votre fichier doit faire moins de 1M !";
                        }

                        if (!empty($errors)) {
                            return $this->twig->render(
                                'Notion/add.html.twig',
                                [
                                    'headerTitle' => $_SESSION['theme_name'],
                                    'titleForm' => 'Ajouter une nouvelle notion',
                                    'FileErrors' => $errors
                                ]
                            );
                        }
                    }

                    if ($notionName == "") {
                        $errors[] = "Veuillez compléter le champ";

                        return $this->twig->render(
                            'Notion/add.html.twig',
                            [
                                'headerTitle' => $_SESSION['theme_name'],
                                'titleForm' => 'Ajouter une nouvelle notion',
                                'NameErrors' => $errors
                            ]
                        );
                    }

                    $notionManager->update(
                        (int)$notionId,
                        (int)$subjectId,
                        $notionName,
                        $lesson,
                        $sample,
                        $fileNameImg
                    );

                    $validationMessage = 'Bravo ! la notion ' . $notionName .  ' a bien été modifiée.';
                }
            }
        }

        $notion = $notionManager->selectOneById((int) $notionId);
        $notionName = $notion['name'];
        $lesson = $notion['lesson'];
        $sample = $notion['sample'];

        return $this->twig->render(
            'Notion/edit.html.twig',
            [
                'headerTitle' => $_SESSION['theme_name'],
                'notionName' => $notionName,
                'lesson' => $lesson,
                'sample' => $sample,
                'titleForm' => 'Modifier cette notion',
                'validationMessage' => $validationMessage,
                'notionId' => $notionId,
                'subjectId' => $subjectId
            ]
        );
    }
}
