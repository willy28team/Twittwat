<?php
// File paths for storing posts
$whitelistFile = "whitelist.txt";
$blacklistFile = "blacklist.txt";

// Function to read posts from a file
function readPosts($file) {
    return file_exists($file) ? array_filter(file($file, FILE_IGNORE_NEW_LINES)) : [];
}

// Load existing posts
$whitelistPosts = readPosts($whitelistFile);
$blacklistPosts = readPosts($blacklistFile);
?>

<h2>Whitelisted Posts (Rating ≥ 3)</h2>
<ul>
    <?php foreach ($whitelistPosts as $post): ?>
        <li class="box whitelist"><?php echo htmlspecialchars($post); ?></li>
    <?php endforeach; ?>
</ul>

<h2>Blacklisted Posts (Rating < 3)</h2>
<ul>
    <?php foreach ($blacklistPosts as $post): ?>
        <li class="box blacklist"><?php echo htmlspecialchars($post); ?></li>
    <?php endforeach; ?>
</ul>
