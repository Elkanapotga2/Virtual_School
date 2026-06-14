<?php
// ✅ FIX : chemin absolu pour éviter les erreurs avec le router
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->username) && !empty($data->password) && !empty($data->role)) {
        
        $username = htmlspecialchars(strip_tags($data->username));
        $role = $data->role;

        // ✅ FIX : 'promoteur' retiré — le compte promoteur est créé via lms_db.sql uniquement
        if (!in_array($role, ['etudiant', 'enseignant'])) {
            http_response_code(400);
            echo json_encode(["error" => "Rôle non valide. Seuls 'etudiant' et 'enseignant' sont autorisés à l'inscription."]);
            exit();
        }

        $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

        $check_query = "SELECT id FROM users WHERE username = :username";
        $stmt_check = $pdo->prepare($check_query);
        $stmt_check->execute([':username' => $username]);

        if ($stmt_check->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Ce nom d'utilisateur est déjà pris."]);
        } else {
            $query = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
            $stmt = $pdo->prepare($query);

            if ($stmt->execute([':username' => $username, ':password' => $password_hash, ':role' => $role])) {
                http_response_code(201);
                echo json_encode(["message" => "Utilisateur créé avec succès."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Impossible de créer le compte."]);
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Données incomplètes."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée."]);
}
?>
