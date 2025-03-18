<?php
require 'database.php'; // Verbindt met de database

if ($pdo) {
    echo "✅ Databaseverbinding is succesvol!";
} else {
    echo "❌ Databaseverbinding is mislukt!";
}
?>
