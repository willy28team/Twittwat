<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$postsFile = 'posts.json';
$relationsFile = 'relations.json';

$posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
$relations = file_exists($relationsFile) ? json_decode(file_get_contents($relationsFile), true) : [];

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$blacklist = isset($relations[$user_id]['blacklist']) ? $relations[$user_id]['blacklist'] : [];
$whitelist = isset($relations[$user_id]['whitelist']) ? $relations[$user_id]['whitelist'] : [];
// NEW: Load the content-based blacklist array (post IDs the user wants hidden)
$content_blacklist = isset($relations[$user_id]['content_blacklist']) ? $relations[$user_id]['content_blacklist'] : [];

// NEW: Get the sort filter from GET parameters (default to 'newest')
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// NEW: Sort posts based on the chosen filter
if ($sort === 'newest') {
    // Sort by timestamp descending (newest first)
    usort($posts, function ($a, $b) {
        return strtotime($b['timestamp'] ?? '0') <=> strtotime($a['timestamp'] ?? '0');
    });
} elseif ($sort === 'highest') {
    // Sort by rating descending (highest-rated first)
    usort($posts, function ($a, $b) {
        return $b['rating'] <=> $a['rating'];
    });
} elseif ($sort === 'lowest') {
    // Sort by rating ascending (lowest-rated first)
    usort($posts, function ($a, $b) {
        return $a['rating'] <=> $b['rating'];
    });
}

// NEW: Reorder posts to place whitelisted posts at the top based on user's preference
if (!empty($whitelist)) {
    $whitelistedPosts = [];
    $otherPosts = [];
    foreach ($posts as $post) {
        if (in_array($post['user_id'], $whitelist)) {
            $whitelistedPosts[] = $post;
        } else {
            $otherPosts[] = $post;
        }
    }
    // Merge arrays so that whitelisted posts appear first, preserving each group's sorted order
    $posts = array_merge($whitelistedPosts, $otherPosts);
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/jpg" href="cn.jpg">
    <title>Community Notes</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #333333;
            --border-color: #dee2e6;
        }
        .rating-dropdown {
            width: 150px;
            padding: 8px;
            border: 2px solid rgb(0, 0, 0);
            opacity: 0.5;
            border-radius: 5px;
            background-color: #fff;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease-in-out;
        }
        .rating-dropdown:hover {
            border-color: #0056b3;
        }
        .rating-dropdown:focus {
            border-color: #0056b3;
            box-shadow: 0px 0px 5px rgba(0, 91, 187, 0.5);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            max-width: 800px;
            margin: 0 auto;
        }
        h2, h3 {
            color: var(--text-color);
            margin: 20px 0;
            text-align: center;
        }
        .post {
            background: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        /* NEW: Darker style for blacklisted posts */
        .post.blacklisted-user,
        .post.blacklisted-content {
            background: #e9e9e9;
        }
        .post.blacklisted-user p,
        .post.blacklisted-content p {
            color: #555;
        }
        .post strong {
            color: var(--primary-color);
            font-size: 1.1em;
        }
        .post img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            margin: 0 5px;
        }
        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        input[type="number"] {
            padding: 6px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 60px;
        }
        #logoutBtn {
            background-color: var(--danger-color);
            color: white;
            position: fixed;
            top: 20px;
            right: 20px;
        }
        /* Dark Button Style for Blacklist Buttons */
        .dark-button {
            background-color: #343a40;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 20px 0;
        }
        .dark-button:hover {
            background-color: #23272b;
        }
        #blacklistList {
            list-style: none;
            background: var(--card-background);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        #blacklistList li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .rating-info {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .post-header strong {
            color: var(--primary-color);
            font-size: 1.1em;
        }
        .timestamp {
            color: #666;
            font-size: 0.85em;
        }
        /* Comments Section Styles */
        .comments-section {
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }
        .toggle-comments {
            background-color: #f0f0f0;
            color: #333;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .toggle-comments:hover {
            background-color: #e0e0e0;
        }
        .comments {
            margin-top: 15px;
        }
        .comment {
            background-color: #f8f9fa;
            border-left: 3px solid var(--primary-color);
            margin: 10px 0;
            padding: 12px;
            border-radius: 0 4px 4px 0;
        }
        .comment strong {
            color: var(--primary-color);
            font-size: 0.95em;
        }
        .comment p {
            margin: 8px 0;
            font-size: 0.95em;
        }
        .comment small {
            color: #666;
            font-size: 0.85em;
            display: block;
            margin-top: 5px;
        }
        .comment form {
            margin-top: 8px;
        }
        .comment button[type="submit"] {
            background-color: var(--danger-color);
            color: white;
            padding: 4px 8px;
            font-size: 0.85em;
        }
        .comments form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 10px 0;
            min-height: 60px;
            font-family: inherit;
        }
        .comments form button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
        }
        .comments form button[type="submit"]:hover {
            background-color: #45a049;
        }
        .actions button:hover {
            opacity: 0.9;
        }
        /* Navigation button for new post */
        .nav-button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }
    </style>
    <script src="js/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

        <!-- Navigation Buttons -->
        <div style="margin-bottom: 20px;">
            <!-- NEW: "New Post" button directs to upload.php -->
            <button onclick="window.location.href='upload.php'" class="nav-button">New Post</button>
            <button id="logoutBtn">Logout</button>
        </div>

        <!-- Existing Blacklist Buttons -->
        <button id="showBlacklistBtn" class="dark-button">Show Blacklisted Users</button>
        <ul id="blacklistList" style="display: none;"></ul>
        <button onclick="window.location.href='blacklisted_contents.php'" class="dark-button">Show Blacklisted Content</button>
        
        <!-- NEW: Filter Form -->
        <form method="GET" action="community.php" style="margin-bottom: 20px; text-align: center;">
            <label for="sortFilter">Filter Posts: </label>
            <select name="sort" id="sortFilter">
                <option value="newest" <?php if($sort == 'newest') echo 'selected'; ?>>Newest Content</option>
                <option value="highest" <?php if($sort == 'highest') echo 'selected'; ?>>Highest-Rated Content</option>
                <option value="lowest" <?php if($sort == 'lowest') echo 'selected'; ?>>Lowest-Rated Content</option>
            </select>
            <button type="submit">Apply</button>
        </form>

        <h3>Community Posts</h3>
        <?php foreach ($posts as $post): ?>
            <?php 
                // Determine if the post's user or content is blacklisted
                $isBlacklistedUser = in_array($post['user_id'], $blacklist);
                $isBlacklistedContent = in_array($post['id'], $content_blacklist);

                // For the "newest" filter, keep the old behavior and skip blacklisted posts
                if ($sort === 'newest' && ($isBlacklistedUser || $isBlacklistedContent)) {
                    continue;
                }

                // For the "highest" or "lowest" filters, show all posts but mark those that are blacklisted
                $extraClass = "";
                $blacklistNote = "";
                if ($sort !== 'newest') {
                    if ($isBlacklistedUser) {
                        $extraClass .= " blacklisted-user";
                        $blacklistNote .= "<p style='color: #555; font-size: 0.9em;'>You have blacklisted this user.</p>";
                    }
                    if ($isBlacklistedContent) {
                        $extraClass .= " blacklisted-content";
                        $blacklistNote .= "<p style='color: #555; font-size: 0.9em;'>You have blacklisted this content.</p>";
                    }
                }
            ?>
            <div class="post<?php echo $extraClass; ?>">
                <?php
                    // Display the blacklist note if applicable
                    if (!empty($blacklistNote)) {
                        echo $blacklistNote;
                    }
                ?>
                <div class="post-header">
                    <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                    <small class="timestamp">Posted on: <?php echo date("F j, Y, g:i A", strtotime($post['timestamp'] ?? '')); ?></small>
                </div>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if ($post['media']): ?>
                    <img src="<?php echo htmlspecialchars($post['media']); ?>" alt="Post media">
                <?php endif; ?>
                <div class="rating-info">
                     Rating: <?php echo number_format($post['rating'], 2); ?> (<?php echo $post['rating_count']; ?> votes)
                </div>
                <div class="actions">
                    <!-- DELETE BUTTON (Only for Post Owner) -->
                    <?php if ($post['user_id'] === $user_id): ?>
                        <form action="delete_post.php" method="post" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this post?')" style="background-color: red">Delete Post</button>
                        </form>
                    <?php else: ?>
                        <form action="rate.php" method="post" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <select name="rating" class="rating-dropdown" required>
                                <option value="" disabled selected>Give rating</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2 - Fair</option>
                                <option value="3">3 - Good</option>
                                <option value="4">4 - Very Good</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                            <button type="submit">Rate</button>
                        </form>

                        <form action="relation.php" method="post" style="display:inline;">
                            <input type="hidden" name="target_user_id" value="<?php echo $post['user_id']; ?>">
                            <?php if (in_array($post['user_id'], $whitelist)): ?>
                                <button type="submit" name="action" value="remove_whitelist">Remove from Whitelist</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="whitelist">Whitelist</button>
                            <?php endif; ?>
                            <?php if (in_array($post['user_id'], $blacklist)): ?>
                                <button type="submit" name="action" value="remove_blacklist">Remove user blacklist</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="blacklist">Blacklist User</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                    <!-- NEW: Option to blacklist this post only -->
                    <?php if ($post['user_id'] !== $user_id): ?>
                        <form action="relation.php" method="post" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <?php if (in_array($post['id'], $content_blacklist)): ?>
                                <button type="submit" name="action" value="remove_content_blacklist">Remove Content Blacklist</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="blacklist_content">Blacklist Content</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <button class="toggle-comments" onclick="toggleComments(this)">Show Comments</button>
                        <div class="comments" style="display: none;">
                            <br>
                            <h4>Comments</h4>
                            <br>
                            <?php if (!empty($post['comments'])): ?>
                                <?php foreach ($post['comments'] as $comment): ?>
                                    <div class="comment">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        <small>Posted on: <?php echo date("F j, Y, g:i A", strtotime($comment['timestamp'])); ?></small>
                                        <?php if ($comment['user_id'] === $_SESSION['user_id']): ?>
                                            <form action="delete_comment.php" method="post" style="display:inline;">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <input type="hidden" name="comment_index" value="<?php echo array_search($comment, $post['comments']); ?>">
                                                <button type="submit" onclick="return confirm('Are you sure you want to delete this comment?')" style="background-color: red">Delete Comment</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No comments yet.</p>
                            <?php endif; ?>
                            <form action="comment.php" method="post">
                                <br>
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <textarea name="content" required placeholder="Write a comment..." rows="2"></textarea>
                                <button type="submit">Post Comment</button>
                            </form>
                        </div>
                    </div>
                    <script>
                        function toggleComments(button) {
                            let commentsDiv = button.nextElementSibling;
                            if (commentsDiv.style.display === "none") {
                                commentsDiv.style.display = "block";
                                button.textContent = "Hide Comments";
                            } else {
                                commentsDiv.style.display = "none";
                                button.textContent = "Show Comments";
                            }
                        }
                    </script>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.getElementById("logoutBtn").addEventListener("click", function() {
            fetch("logout.php")
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.href = "login.html";
            })
            .catch(error => console.error("Error:", error));
        });

        document.getElementById("showBlacklistBtn").addEventListener("click", function() {
            let blacklistList = document.getElementById("blacklistList");

            if (blacklistList.style.display === "none") {
                fetch("relation.php")
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        blacklistList.innerHTML = "";
                        if (data.blacklisted_users.length === 0) {
                            blacklistList.innerHTML = "<li>No blacklisted users.</li>";
                        } else {
                            data.blacklisted_users.forEach(user => {
                                let listItem = document.createElement("li");
                                listItem.textContent = user.username;

                                let removeBtn = document.createElement("button");
                                removeBtn.textContent = "Remove";
                                removeBtn.onclick = function() {
                                    removeBlacklist(user.id);
                                };

                                listItem.appendChild(removeBtn);
                                blacklistList.appendChild(listItem);
                            });
                        }
                        blacklistList.style.display = "block";
                    }
                })
                .catch(error => console.error("Error fetching blacklist:", error));
            } else {
                blacklistList.style.display = "none";
            }
        });

        function removeBlacklist(userId) {
            fetch("relation.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `target_user_id=${userId}&action=blacklist`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload();
            })
            .catch(error => console.error("Error removing user from blacklist:", error));
        }
    </script>
</body>
</html>
