<?php
require_once 'config.php';
require_once 'helpers.php';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Kiedy</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="container glass-card enter-animation">
        <header class="text-center">
            <h1 class="gradient-text">Privacy Policy</h1>
            <p class="subtitle">Transparency regarding how we use cookies and data.</p>
        </header>

        <section class="privacy-content">
            <h3>1. Strictly Necessary Cookies</h3>
            <p>Our application uses a single cookie named <code>kiedy_user_id</code>. This cookie is <strong>strictly necessary</strong> for the core functionality of the service. It allows us to:</p>
            <ul>
                <li>Recall your chosen nickname on calendars.</li>
                <li>Link your availability votes to your session without requiring a traditional login.</li>
            </ul>
            <p>This cookie does not track your activity on other websites and does not contain personal information beyond a random identifier.</p>

            <h3 class="mt-4">2. Security & Bot Prevention</h3>
            <p>We use <strong>Cloudflare Turnstile</strong> to verify that interactions with our site are performed by humans, not bots. Turnstile may set essential cookies to ensure security and prevent abuse of our API endpoints.</p>

            <h3 class="mt-4">3. Data Retention</h3>
            <p>Calendar data, including nicknames and availability, are stored in our database. Since we don't handle sensitive personal data (like emails or passwords), we retain this information to keep the shared calendars functional for you and your group.</p>

            <div class="mt-4 text-center">
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script src="js/cookie-notice.js"></script>
</body>
</html>
