
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us | VYRO';

$errors = [];
$success = false;

$name = '';
$email = '';
$subject = '';
$message = '';

if (isPost()) {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $subject = cleanInput($_POST['subject'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');

    if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'يرجى إدخال اسم صحيح.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صحيح.';
    }

    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
        $errors[] = 'يرجى إدخال عنوان للرسالة.';
    }

    if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
        $errors[] = 'يجب أن تحتوي الرسالة على 10 إلى 3000 حرف.';
    }

    if (empty($errors)) {

        $messagesFile = VYRO_DATA . '/messages.json';

        $messages = readJsonFile($messagesFile, []);

        if (!is_array($messages)) {
            $messages = [];
        }

        $messages[] = [
            'id' => bin2hex(random_bytes(8)),
            'created_at' => date('Y-m-d H:i:s'),
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'status' => 'unread'
        ];

        if (writeJsonFile($messagesFile, $messages)) {

            $success = true;

            $name = '';
            $email = '';
            $subject = '';
            $message = '';

        } else {

            $errors[] = 'تعذر إرسال الرسالة حالياً. حاول مرة أخرى.';

        }

    }

}

require_once __DIR__ . '/includes/header.php';
?>

<section class="contact-page section-padding">

    <div class="container">

        <div class="shop-heading">

            <div>
                <span class="eyebrow">WE'RE HERE FOR YOU</span>
                <h1>تواصل معنا</h1>
                <p>لديك سؤال أو تحتاج إلى مساعدة؟ نحن هنا للاستماع إليك.</p>
            </div>

        </div>


        <div class="contact-layout">

            <div class="contact-info reveal">

                <span class="eyebrow">GET IN TOUCH</span>

                <h2>
                    لنجعل التواصل<br>
                    <span>أسهل.</span>
                </h2>

                <p>
                    سواء كان لديك استفسار عن منتج،
                    أو طلب، أو اقتراح، أرسل لنا رسالتك.
                </p>


                <div class="contact-info-item">

                    <span class="contact-info-icon">✉</span>

                    <div>
                        <strong>البريد الإلكتروني</strong>
                        <p>hello@vyro-store.com</p>
                    </div>

                </div>


                <div class="contact-info-item">

                    <span class="contact-info-icon">◷</span>

                    <div>
                        <strong>أوقات الرد</strong>
                        <p>من السبت إلى الخميس</p>
                    </div>

                </div>


                <div class="contact-social">

                    <span>FOLLOW VYRO</span>

                    <div>
                        <a href="#" aria-label="Instagram">IG</a>
                        <a href="#" aria-label="TikTok">TK</a>
                        <a href="#" aria-label="Facebook">FB</a>
                    </div>

                </div>

            </div>


            <div class="contact-form-wrapper reveal">

                <?php if ($success): ?>

                    <div class="contact-success">

                        <span>✓</span>

                        <h2>تم إرسال رسالتك بنجاح</h2>

                        <p>شكراً لتواصلك مع VYRO. سنراجع رسالتك قريباً.</p>

                    </div>

                <?php else: ?>

                    <?php if (!empty($errors)): ?>

                        <div class="checkout-errors">

                            <?php foreach ($errors as $error): ?>

                                <p><?= e($error) ?></p>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST" action="contact.php" class="contact-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <div class="form-group">

                            <label for="contact-name">الاسم الكامل</label>

                            <input
                                type="text"
                                id="contact-name"
                                name="name"
                                value="<?= e($name) ?>"
                                placeholder="اسمك"
                                minlength="3"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="contact-email">البريد الإلكتروني</label>

                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                value="<?= e($email) ?>"
                                placeholder="example@email.com"
                                maxlength="254"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="contact-subject">موضوع الرسالة</label>

                            <input
                                type="text"
                                id="contact-subject"
                                name="subject"
                                value="<?= e($subject) ?>"
                                placeholder="كيف يمكننا مساعدتك؟"
                                minlength="3"
                                maxlength="150"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="contact-message">رسالتك</label>

                            <textarea
                                id="contact-message"
                                name="message"
                                rows="6"
                                minlength="10"
                                maxlength="3000"
                                placeholder="اكتب رسالتك هنا..."
                                required
                            ><?= e($message) ?></textarea>

                        </div>


                        <button type="submit" class="btn btn-primary">
                            إرسال الرسالة <span>←</span>
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
