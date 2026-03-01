<?php
/**
 * Fix database schema:
 * 1. Create reclamation table first (needed for FK constraint on reponse table)
 * 2. Insert placeholder reclamation records for existing reponse foreign keys
 * 3. Then run doctrine:schema:update --force
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=hospismart', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "=== Step 1: Create reclamation table ===\n";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS reclamation (
        id INT AUTO_INCREMENT NOT NULL,
        titre VARCHAR(255) NOT NULL,
        description LONGTEXT NOT NULL,
        date_creation DATETIME NOT NULL,
        email VARCHAR(255) NOT NULL,
        nom_patient VARCHAR(255) NOT NULL,
        statut VARCHAR(50) NOT NULL,
        categorie VARCHAR(100) NOT NULL,
        priorite VARCHAR(50) NOT NULL,
        PRIMARY KEY (id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");
echo "OK: reclamation table created\n";

echo "=== Step 2: Insert placeholder reclamations for existing reponse FK references ===\n";
// Find which reclamation_ids are referenced by reponse
$stmt = $pdo->query("SELECT DISTINCT reclamation_id FROM reponse WHERE reclamation_id IS NOT NULL");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM reclamation WHERE id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO reclamation (id, titre, description, date_creation, email, nom_patient, statut, categorie, priorite) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
        $insert->execute([$id, 'Réclamation #' . $id, 'Placeholder', 'system@hospismart.tn', 'System', 'en_attente', 'autre', 'normale']);
        echo "Inserted placeholder reclamation ID=$id\n";
    }
    else {
        echo "Reclamation ID=$id already exists\n";
    }
}

echo "\n=== Step 3: Hash plaintext passwords ===\n";
$stmt = $pdo->query("SELECT id, password FROM user");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
foreach ($users as $user) {
    // If password is not a bcrypt/argon2 hash (doesn't start with $), hash it
    if (!str_starts_with($user['password'], '$')) {
        $hashed = password_hash($user['password'], PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
        $update->execute([$hashed, $user['id']]);
        $count++;
    }
}
echo "Hashed $count plaintext passwords\n";

echo "\n=== Done! Now run: php bin/console doctrine:schema:update --force ===\n";
