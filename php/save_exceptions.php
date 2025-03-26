<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newExceptions = $_POST['exceptions'] ?? [];

    // Read the existing settings
    $settingsFile = 'settings.json';
    $settings = json_decode(file_get_contents($settingsFile), true);

    // Update exceptions
    $settings['exceptions'] = array_values(array_filter($newExceptions)); // Remove empty values

    // Save back to JSON
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

    // Redirect back
    header('Location: ../index.php'); // Change index.php to your main file
    exit;
}