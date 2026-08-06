<?php

require_once __DIR__ . "/config/database.php";

$sql = "SELECT team_id, team_name, region FROM teams";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blacktop Database Test</title>
</head>
<body>
    <h1>Blacktop database connection successful</h1>

    <?php while ($team = $result->fetch_assoc()): ?>
        <article>
            <h2><?= htmlspecialchars($team["team_name"]) ?></h2>
            <p><?= htmlspecialchars($team["region"]) ?></p>
        </article>
    <?php endwhile; ?>
</body>
</html>

<?php
$conn->close();
?>