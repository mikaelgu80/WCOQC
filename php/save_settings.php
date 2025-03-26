<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsFile = 'settings.json';
    $settings = json_decode(file_get_contents($settingsFile), true);

    // Update settings from form
    if (isset($_POST['libcolumns'])) {
        $settings['libcolumns'] = $_POST['libcolumns'];
    }
    if (isset($_POST['oiscolumns'])) {
        $settings['oiscolumns'] = $_POST['oiscolumns'];
    }

    // Save back to JSON
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

    // Redirect back
    header('Location: ../index.php'); // Change index.php to your main file
    exit;
}