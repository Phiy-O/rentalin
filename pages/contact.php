<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$old = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_old']);
?>

<section class="contact-hero">
    <div class="contact-hero-content">
        <h1>Contact Us</h1>
        <p>Hubungi tim Rentalin untuk pertanyaan rental, toko, atau bantuan penggunaan platform.</p>
    </div>
</section>

<main class="container contact-page">
    <?php show_flash(); ?>

    <section class="contact-grid">
        <div class="contact-info">
            <h1>Get In Touch</h1>
            <p class="contact-lead">Kami siap membantu penyewa dan pemilik toko agar pengalaman rental di Rentalin berjalan lebih mudah, aman, dan jelas.</p>

            <div class="contact-info-list">
                <div class="contact-item">
                    <span class="contact-item-icon"><?php render_icon('bell', '', '', 18); ?></span>
                    <div>
                        <p>Phone</p>
                        <strong>123 456 789</strong>
                    </div>
                </div>

                <div class="contact-item">
                    <span class="contact-item-icon"><?php render_icon('store', '', '', 18); ?></span>
                    <div>
                        <p>Email</p>
                        <strong>Rentalin@gmail.com</strong>
                    </div>
                </div>

                <div class="contact-item">
                    <span class="contact-item-icon"><?php render_icon('map-pin', '', '', 18); ?></span>
                    <div>
                        <p>Address</p>
                        <strong>Depok, Ambarketawang, Gamping, Sleman, Yogyakarta</strong>
                    </div>
                </div>

                <div class="contact-item">
                    <span class="contact-item-icon"><?php render_icon('instagram', '', '', 18); ?></span>
                    <div>
                        <p>Instagram</p>
                        <strong>@rentalin</strong>
                    </div>
                </div>
            </div>

            <div class="contact-social-block">
                <p>Social Media</p>
                <div class="contact-socials">
                    <a href="<?= route('contact'); ?>" aria-label="Instagram"><?php render_icon('instagram', '', '', 18); ?></a>
                    <a href="<?= route('contact'); ?>" aria-label="Twitter"><?php render_icon('twitter', '', '', 18); ?></a>
                    <a href="<?= route('contact'); ?>" aria-label="LinkedIn"><?php render_icon('linkedin-logo', '', '', 18); ?></a>
                </div>
            </div>
        </div>

        <form class="contact-form" action="<?= route('contact.submit'); ?>" method="POST">
            <?php csrf_field(); ?>
            <div class="contact-form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? ''); ?>" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? ''); ?>" placeholder="Name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? ''); ?>" placeholder="Phone">
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="10" placeholder="Message" required><?= htmlspecialchars($old['message'] ?? ''); ?></textarea>
            </div>

            <button class="contact-submit" type="submit">Send Message</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
