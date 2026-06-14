<?php
// ✅ FIX : chemin absolu pour éviter les erreurs avec le router
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->username) && !empty($data->password)) {
        $username = htmlspecialchars(strip_tags($data->username));

        $query = "SELECT id, username, password, role FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':username' => $username]);

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if (password_verify($data->password, $row['password'])) {
                http_response_code(200);
                echo json_encode([
                    "message"  => "Connexion réussie.",
                    "user_id"  => $row['id'],
                    "username" => $row['username'],
                    "role"     => $row['role']
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["error" => "Mot de passe incorrect."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Utilisateur introuvable."]);
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
