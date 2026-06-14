<?php
require_once 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ─────────────────────────────────────────────────────────────────────────────
// 1. OBTENIR TOUTES LES LEÇONS (Pour l'Étudiant, l'Enseignant, le Promoteur)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'get_lessons') {
    try {
        $query = "SELECT l.id, l.titre, l.type_support, l.support_url, l.quiz_question, 
                         m.titre AS module_name, u.username AS enseignant_name 
                  FROM lessons l
                  JOIN modules m ON l.module_id = m.id
                  JOIN users u ON l.enseignant_id = u.id
                  ORDER BY l.created_at DESC";
        $stmt = $pdo->query($query);
        $lessons = $stmt->fetchAll();

        echo json_encode(["data" => $lessons]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la récupération des leçons."]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. AJOUTER UN MODULE (Action du Promoteur)
// ─────────────────────────────────────────────────────────────────────────────
elseif ($action === 'add_module' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->titre)) {
        try {
            $query = "INSERT INTO modules (titre, description) VALUES (:titre, :description)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':titre' => htmlspecialchars(strip_tags($data->titre)),
                ':description' => htmlspecialchars(strip_tags($data->description ?? ''))
            ]);
            echo json_encode(["message" => "Module créé avec succès."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Erreur lors de la création du module."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Le titre du module est requis."]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. RECUPERER LA LISTE DES MODULES (Pour les sélections de formulaires)
// ─────────────────────────────────────────────────────────────────────────────
elseif ($action === 'get_modules') {
    $stmt = $pdo->query("SELECT id, titre FROM modules ORDER BY titre ASC");
    echo json_encode(["data" => $stmt->fetchAll()]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. PUBLIER UNE LEÇON ET SON QUIZ (Action de l'Enseignant)
// ─────────────────────────────────────────────────────────────────────────────
elseif ($action === 'add_lesson' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->titre) && !empty($data->module_id) && !empty($data->enseignant_id) && !empty($data->type_support) && !empty($data->support_url) && !empty($data->quiz_question) && !empty($data->quiz_reponse)) {
        try {
            $query = "INSERT INTO lessons (module_id, enseignant_id, titre, type_support, support_url, quiz_question, quiz_reponse) 
                      VALUES (:module_id, :enseignant_id, :titre, :type_support, :support_url, :quiz_question, :quiz_reponse)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':module_id' => intval($data->module_id),
                ':enseignant_id' => intval($data->enseignant_id),
                ':titre' => htmlspecialchars(strip_tags($data->titre)),
                ':type_support' => $data->type_support,
                ':support_url' => htmlspecialchars(strip_tags($data->support_url)),
                ':quiz_question' => htmlspecialchars(strip_tags($data->quiz_question)),
                ':quiz_reponse' => htmlspecialchars(strip_tags($data->quiz_reponse))
            ]);
            echo json_encode(["message" => "Leçon et évaluation créées avec succès."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Erreur lors de l'enregistrement de la leçon."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Tous les champs obligatoires du cours et de l'évaluation doivent être remplis."]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. SOUMETTRE UN QUIZ DE FIN DE LEÇON (Action de l'Étudiant -> Calcule la progression %)
// ─────────────────────────────────────────────────────────────────────────────
elseif ($action === 'submit_quiz' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->etudiant_id) && !empty($data->lesson_id) && isset($data->reponse_etudiant)) {
        try {
            // Récupérer la bonne réponse en BDD
            $stmt_lesson = $pdo->prepare("SELECT quiz_reponse FROM lessons WHERE id = :id");
            $stmt_lesson->execute([':id' => intval($data->lesson_id)]);
            $lesson = $stmt_lesson->fetch();

            if (!$lesson) {
                http_response_code(404);
                echo json_encode(["error" => "Leçon introuvable."]);
                exit();
            }

            // Comparaison simple des réponses (insensible à la casse)
            $is_correct = (strtolower(trim($data->reponse_etudiant)) === strtolower(trim($lesson['quiz_reponse'])));
            $note = $is_correct ? 100 : 0; // 100% de progression si validé, 0% sinon

            // Insérer ou mettre à jour la note de progression
            $query = "INSERT INTO evaluations (etudiant_id, lesson_id, note_obtenue) 
                      VALUES (:etudiant_id, :lesson_id, :note)
                      ON DUPLICATE KEY UPDATE note_obtenue = :note";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':etudiant_id' => intval($data->etudiant_id),
                ':lesson_id' => intval($data->lesson_id),
                ':note' => $note
            ]);

            echo json_encode([
                "success" => true,
                "correct" => $is_correct,
                "note" => $note,
                "message" => $is_correct ? "Félicitations ! Évaluation validée (100%)." : "Dommage, mauvaise réponse (0%). Réessayez !"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Erreur de traitement du quiz."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Données du quiz incomplètes."]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. RECUPERER LA PROGRESSION GLOBALE DE L'ETUDIANT (%)
// ─────────────────────────────────────────────────────────────────────────────
elseif ($action === 'get_student_stats' && isset($_GET['etudiant_id'])) {
    try {
        $etudiant_id = intval($_GET['etudiant_id']);
        
        // Nombre total de leçons disponibles dans le LMS
        $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM lessons");
        $total_lessons = $total_stmt->fetch()['total'];

        // Nombre de leçons validées par l'étudiant avec 100%
        $valid_stmt = $pdo->prepare("SELECT COUNT(*) as valides FROM evaluations WHERE etudiant_id = :etudiant_id AND note_obtenue = 100");
        $valid_stmt->execute([':etudiant_id' => $etudiant_id]);
        $valides = $valid_stmt->fetch()['valides'];

        // Calcul du pourcentage global de progression
        $progression = ($total_lessons > 0) ? round(($valides / $total_lessons) * 100) : 0;

        echo json_encode([
            "progression" => $progression,
            "valides" => $valides,
            "total" => $total_lessons
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur de statistiques."]);
    }
}

else {
    http_response_code(404);
    echo json_encode(["error" => "Action non reconnue."]);
}
?>