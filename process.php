<?php
// File paths for storing posts
$whitelistFile = "whitelist.txt";
$blacklistFile = "blacklist.txt";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["content"], $_POST["rating"])) {
    $content = trim($_POST["content"]);
    $rating = (int)$_POST["rating"];

    if (!empty($content)) {
        if ($rating >= 3) {
            file_put_contents($whitelistFile, $content . "\n", FILE_APPEND);
        } else {
            file_put_contents($blacklistFile, $content . "\n", FILE_APPEND);
        }
    }
}

// Redirect back to the main page
header("Location: index.html");
exit();
?>