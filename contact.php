
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/mailer.php';
require_login();

$errors = [];
$subject = '';
$message = '';

$propertyId = filter_input(
    INPUT_POST,
    'property_id',
    FILTER_VALIDATE_INT
);
if (is_post()) {
    verify_csrf();

    $message = trim((string)($_POST['message'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));

    // Validate message
    if (mb_strlen($message) < 10 || mb_strlen($message) > 1500) {
        $errors[] = 'Message must be between 10 and 1500 characters.';
    }

    // Property inquiry
    if ($propertyId) {
        if (!$errors) {
            $stmt = db()->prepare(
                "SELECT id, owner_id
                 FROM properties
                 WHERE id = ? AND status = 'approved'"
            );

            $stmt->execute([$propertyId]);
            $property = $stmt->fetch();

            if (
                !$property ||
                (int)$property['owner_id'] === (int)current_user()['id']
            ) {
                $errors[] = 'This property is not available for inquiry.';
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO inquiries (property_id, user_id, message)
                     VALUES (?, ?, ?)'
                );

                $stmt->execute([
                    $propertyId,
                    current_user()['id'],
                    $message
                ]);

                send_mail(current_user()['email'], current_user()['name'], 'Inquiry sent — EstateHub', '<p>Your inquiry has been sent to the property owner.</p>');

                flash(
                    'success',
                    'Your inquiry has been sent to the property owner.'
                );

                redirect('property-details.php?id=' . $propertyId);
            }
        }
    } else {
        // General contact message
        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
            $errors[] = 'Subject must be between 3 and 150 characters.';
        }

        if (!$errors) {
            $stmt = db()->prepare(
                'INSERT INTO contact_messages (user_id, subject, message)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([
                current_user()['id'],
                $subject,
                $message
            ]);

            send_mail(current_user()['email'], current_user()['name'], 'Message received — EstateHub', '<p>We received your message and will respond soon.</p>');

            flash('success', 'Your message has been sent.');
            redirect('contact.php');
        }
    }
}

$pageTitle = 'Contact Us | EstateHub';
require __DIR__ . '/includes/header.php';
?>

<style>
.contact-page { padding:50px 0 70px; }
.contact-page .contact-hero {
    position:relative;
    isolation:isolate;
    display:flex;
    flex-direction:column;
    justify-content:center;
    min-height:440px;
    max-width:none;
    margin:0 calc((min(1160px,100% - 40px) - 100vw)/2) 32px;
    padding:70px max(24px,calc((100vw - 1060px)/2));
    overflow:hidden;
    background:linear-gradient(90deg,rgba(16,39,31,.91),rgba(17,48,38,.68) 58%,rgba(17,48,38,.27)),url("../images/contact-design.jpg") center 48% / cover no-repeat,#173b30;
    text-align:left;
}
.contact-page .contact-hero::after {
    position:absolute;
    z-index:-1;
    right:9%;
    bottom:-130px;
    width:340px;
    height:340px;
    border:1px solid rgba(226,201,137,.42);
    border-radius:50%;
    box-shadow:0 0 0 30px rgba(226,201,137,.08),0 0 0 60px rgba(226,201,137,.05);
    content:"";
}
.contact-page .contact-hero .eyebrow { color:#e2c989; font-size:.75rem; letter-spacing:2.2px; }
.contact-page .contact-hero h1 { max-width:720px; margin:12px 0 17px; color:#fff; font:500 clamp(2.8rem,6.2vw,5rem)/1.02 Georgia,'Times New Roman',serif; letter-spacing:-1.6px; }
.contact-page .contact-hero p { max-width:590px; color:rgba(255,255,255,.82); font-size:1.04rem; line-height:1.8; }
.contact-page #contact-form { scroll-margin-top: 100px; }
.contact-page .contact-story-image {
    position:relative;
    margin:-32px -32px 27px;
    overflow:hidden;
    border-radius:5px 5px 0 0;
    aspect-ratio: 16 / 9;
}
.contact-page .contact-story-image img { width:100%;height:100%;object-fit:cover; }
.contact-page .contact-story-image > span {
    position:absolute;
    left:14px;
    bottom:14px;
    padding:6px 10px;
    color:white;
    background:rgba(16,45,37,.82);
    border:1px solid rgba(255,255,255,.24);
    border-radius:3px;
    font-size:.72rem;
    letter-spacing:.04em;
}
.contact-page .contact-story-image > span a { color:#e2c989;text-decoration:underline;text-underline-offset:2px; }

.contact-info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap:16px;
    margin-bottom:32px;
}

.contact-info-card {
    background: #fff;
    border:1px solid #e5e1d7;
    border-radius:5px;
    padding:24px;
    box-shadow:0 8px 24px rgba(29,40,31,.045);
    transition:transform .2s ease,box-shadow .2s ease;
}

.contact-info-card:hover {
    transform: translateY(-4px);
    box-shadow:0 14px 32px rgba(29,40,31,.1);
}

.contact-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius:5px;
    background:#edf0e8;
    color:#a58343;
    font-size: 23px;
    margin-bottom: 18px;
}

.contact-info-card h3 {
    font-size: 18px;
    margin: 0 0 10px;
}

.contact-info-card p {
    color:#6f756c;
    line-height: 1.7;
    font-size: 14px;
    margin: 0 0 12px;
}

.contact-info-card a {
    color:#315340;
    font-weight: 600;
    text-decoration: none;
    overflow-wrap: anywhere;
}

.contact-info-card a:hover {
    text-decoration: underline;
}

.contact-main-grid {
    display: grid;
    grid-template-columns:minmax(0,.92fr) minmax(0,1.08fr);
    gap:22px;
    align-items: start;
}

.contact-intro {
    background:linear-gradient(145deg,#173b30,#102d25);
    border:1px solid #173b30;
    border-radius:5px;
    padding:32px;
    color:#fff;
    box-shadow:0 14px 36px rgba(16,45,37,.13);
}

.contact-intro h2,
.contact-form-card h2 {
    font:500 clamp(1.7rem,3vw,2.25rem)/1.15 Georgia,'Times New Roman',serif;
    margin:10px 0 14px;
}
.contact-page .contact-intro .eyebrow { color:#e2c989; }

.contact-intro > p {
    color:rgba(255,255,255,.72);
    line-height: 1.8;
    margin-bottom: 25px;
}

.contact-help-item {
    display: flex;
    gap: 14px;
    margin-top:21px;
    padding-top:17px;
    border-top:1px solid rgba(255,255,255,.13);
}

.contact-help-item .help-icon {
    flex-shrink: 0;
    font-size: 22px;
}

.contact-help-item h3 {
    font-size: 16px;
    margin: 0 0 6px;
}

.contact-help-item p {
    color:rgba(255,255,255,.68);
    font-size: 14px;
    line-height: 1.7;
    margin: 0;
}

.contact-form-card {
    background: #fff;
    border:1px solid #e5e1d7;
    border-radius:5px;
    padding:32px;
    box-shadow:0 14px 36px rgba(29,40,31,.07);
}

.contact-form-card .form-description {
    color:#747970;
    line-height: 1.7;
    font-size: 14px;
    margin-bottom: 24px;
}

.contact-form-card .form-stack label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 18px;
}

.contact-form-card input,
.contact-form-card textarea {
    display: block;
    width: 100%;
    box-sizing: border-box;
    margin-top: 8px;
    padding: 13px 14px;
    border:1px solid #dddcd2;
    border-radius:4px;
    font: inherit;
    font-size: 14px;
    background: #fff;
    color:#202820;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.contact-form-card input:focus,
.contact-form-card textarea:focus {
    outline: none;
    border-color:#a78648;
    box-shadow:0 0 0 3px rgba(180,147,80,.15);
}

.contact-form-card textarea {
    resize: vertical;
    min-height: 135px;
}

.contact-form-card .btn {
    width: 100%;
    border: none;
    cursor: pointer;
    padding: 14px 20px;
    font-size: 15px;
}

.contact-privacy-note {
    text-align:center;
    color:#747970;
    font-size: 12px;
    line-height: 1.6;
    margin-top: 15px;
}

.contact-bottom {
    margin-top: 40px;
    padding: 28px;
    text-align: center;
    border:1px solid #e8e3d8;
    border-radius:5px;
    background:linear-gradient(120deg,#f1f0e9,#e9efe9);
}

.contact-bottom h2 {
    margin: 0 0 10px;
    font-size: 22px;
}

.contact-bottom p {
    color:#626a60;
    line-height: 1.7;
    margin: 0;
}

.contact-bottom a {
    color:#315340;
    font-weight: 600;
    text-decoration: none;
}

@media (max-width: 850px) {
    .contact-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .contact-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .contact-page {
        padding: 30px 0 45px;
    }

    .contact-info-grid {
        grid-template-columns: 1fr;
    }

    .contact-intro,
    .contact-form-card {
        padding: 22px;
    }

    .contact-page .contact-story-image {
        margin: -22px -22px 24px;
    }
}
</style>

<section class="container contact-page">

    <!-- Hero Section -->
    <div class="contact-hero">
        <span class="eyebrow">We are here to help</span>

        <h1>A thoughtful conversation starts here.</h1>

        <p>
            Have a question about EstateHub or a property listing? Send a message through your account, or contact the listing owner from the property's details page.
        </p>
    </div>

    <!-- Contact Information Cards -->
    <div class="contact-info-grid">

        <div class="contact-info-card">
            <div class="contact-icon" aria-hidden="true">✉</div>

            <h3>Message the team</h3>

            <p>Questions about EstateHub or your account? Send the team a message through your account.</p>
            <a href="#contact-form">Write to the team ↗</a>
        </div>

        <div class="contact-info-card">
            <div class="contact-icon" aria-hidden="true">☎</div>

            <h3>Property enquiries</h3>

            <p>
                Ask about a particular home directly from its property details page.
            </p>
            <a href="<?= e(url('properties.php')) ?>">Explore listings ↗</a>
        </div>

        <div class="contact-info-card">
            <div class="contact-icon" aria-hidden="true">◷</div>

            <h3>Listing assistance</h3>

            <p>
                Need help with a listing or your EstateHub experience? Send us the details.
            </p>
            <a href="#contact-form">Send a message ↗</a>
        </div>

    </div>

    <!-- Main Contact Section -->
    <div class="contact-main-grid">

        <!-- Help and Information -->
        <div class="contact-intro">

            <div class="contact-story-image">
                <img src="<?= e(url('assets/images/contact-meeting.jpg')) ?>" alt="People planning their next home around a table" loading="lazy" decoding="async">
                <span>Illustrative imagery · <a href="https://www.pexels.com/license/" target="_blank" rel="noopener noreferrer">Pexels</a></span>
            </div>
            <span class="eyebrow">How can we help?</span>

            <h2>We're only a message away.</h2>

            <p>
                Share what you need, and we will keep your message with your EstateHub account.
            </p>

            <div class="contact-help-item">
                <div class="help-icon" aria-hidden="true">⌂</div>

                <div>
                    <h3>Buying a Property</h3>
                    <p>
                        Ask about approved listings, property details,
                        amenities, and contacting property owners.
                    </p>
                </div>
            </div>

            <div class="contact-help-item">
                <div class="help-icon" aria-hidden="true">▤</div>

                <div>
                    <h3>Selling or Listing</h3>
                    <p>
                        Get assistance with submitting a listing,
                        understanding the approval process, and managing
                        your property information.
                    </p>
                </div>
            </div>

            <div class="contact-help-item">
                <div class="help-icon" aria-hidden="true">◎</div>

                <div>
                    <h3>Account & Technical Support</h3>
                    <p>
                        Let us know if you experience login problems,
                        account issues, or difficulties using the website.
                    </p>
                </div>
            </div>

            <div class="contact-help-item">
                <div class="help-icon" aria-hidden="true">↗</div>

                <div>
                    <h3>General Feedback</h3>
                    <p>
                        Share your suggestions and feedback to help us
                        improve the EstateHub experience.
                    </p>
                </div>
            </div>

        </div>

        <!-- Contact Form -->
        <div class="contact-form-card" id="contact-form">

            <span class="eyebrow">Get in touch</span>

            <h2>Tell us what you need.</h2>

            <p class="form-description">
                Fill in the details below and tell us how we can assist you.
                Please provide enough information for us to understand
                your request.
            </p>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error">
                    <?= e($err) ?>
                </div>
            <?php endforeach; ?>

            <form method="post" class="form-stack">

                <?= csrf_field() ?>

                <label for="subject">
                    Subject

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="e.g. Property listing assistance"
                        value="<?= e($subject) ?>"
                        minlength="3"
                        maxlength="150"
                        required
                    >
                </label>

                <label for="message">
                    Your Message

                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        minlength="10"
                        maxlength="1500"
                        placeholder="Please describe your question or concern..."
                        required
                    ><?= e($message) ?></textarea>
                </label>

                <button class="btn" type="submit">
                    Send Message &rarr;
                </button>

                <p class="contact-privacy-note">
                    Your message will be submitted through EstateHub's
                    contact system. Please avoid including passwords
                    or other sensitive information.
                </p>

            </form>

        </div>

    </div>

    <!-- Closing Section -->
    <div class="contact-bottom">
        <h2>Your real estate journey matters to us.</h2>

        <p>
            Thank you for choosing EstateHub. We appreciate your interest
            and look forward to helping you. For questions about a specific
            property, please use the inquiry form on that property's
            details page.
        </p>
    </div>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
