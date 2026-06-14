<?php
// Obtenir le chemin de la requête
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si le fichier ou dossier physique existe à la racine, on le laisse s'afficher normalement
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Si l'URL commence par /api/ et que le fichier PHP existe, on l'exécute explicitement
if (preg_match('/^\/api\/(.+)$/', $uri, $matches)) {
    $phpFile = __DIR__ . '/api/' . $matches[1];
    if (file_exists($phpFile)) {
        include $phpFile;
        exit;
    }
}

// Par défaut, si on arrive sur la racine /, on sert index.html ou home.html
if ($uri === '/') {
    if (file_exists(__DIR__ . '/index.html')) {
        include __DIR__ . '/index.html';
        exit;
    }
}

// Si rien ne correspond, on retourne une erreur 404
http_response_code(404);
echo json_encode(["error" => "Ressource introuvable sur Virtual School"]);
exit;
