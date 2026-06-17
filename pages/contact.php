<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="contact-hero"></section>

<main class="container contact-page">
    <section class="contact-grid">
        <div class="contact-info">
            <h1>Get In Touch</h1>

            <div class="contact-item">
                <p>Email</p>
                <strong>Rentalin@gmail.com</strong>
            </div>

            <div class="contact-item">
                <p>Phone</p>
                <strong>123 456 789</strong>
            </div>

            <div class="contact-item">
                <p>Address</p>
                <strong>
                    58W7+V7F, Jl. Nasional III, Depok, Ambarketawang,
                    Kec. Gamping, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55294
                </strong>
            </div>

            <div class="contact-item">
                <p>Social Media</p>
                <div class="contact-socials">
                    <a href="<?= route('contact'); ?>" aria-label="Instagram"></a>
                    <a href="<?= route('contact'); ?>" aria-label="Facebook"></a>
                    <a href="<?= route('contact'); ?>" aria-label="Twitter"></a>
                    <a href="<?= route('contact'); ?>" aria-label="LinkedIn"></a>
                </div>
            </div>
        </div>

        <form class="contact-form" action="<?= route('contact.submit'); ?>" method="POST">
            <div class="contact-form-row">
                <div class="form-group">
                    <label for="name">Yourname</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="10" required></textarea>
            </div>

            <button class="contact-submit" type="submit">Send Message</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
