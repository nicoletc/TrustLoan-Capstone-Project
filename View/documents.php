<?php
$siteName = 'TrustLoan';
$pageTitle = 'Documents';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
require_once __DIR__ . '/../Classes/Application.php';
$userId = $_SESSION['user_id'] ?? null;
$app = $userId ? Application::getLatestByUser($userId) : null;
$documentsSubmitted = $app && (trim((string)($app['business_type'] ?? '')) !== '' || trim((string)($app['business_duration'] ?? '')) !== '' || trim((string)($app['business_location'] ?? '')) !== '');
$ghanaFrontEmpty = !$app || trim((string)($app['ghana_card_front_path'] ?? '')) === '';
$ghanaBackEmpty = !$app || trim((string)($app['ghana_card_back_path'] ?? '')) === '';
$imagesMissing = $documentsSubmitted && ($ghanaFrontEmpty || $ghanaBackEmpty);
$showForm = !$documentsSubmitted || $imagesMissing;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> – <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/base.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/signin.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/documents.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="documents-page">
        <div class="documents-card">
            <?php if ($documentsSubmitted && !$imagesMissing): ?>
            <h1 class="signin-title">Documents submitted</h1>
            <p class="signin-desc">Your documents have been submitted, and we will contact you if needed.</p>
            <p><a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="btn btn-primary">Back to dashboard</a></p>
            <?php else: ?>
            <?php
            $documentsError = isset($_SESSION['documents_error']) ? (string) $_SESSION['documents_error'] : '';
            if ($documentsError !== '') {
                unset($_SESSION['documents_error']);
            }
            ?>
            <h1 class="signin-title"><?php echo $imagesMissing ? 'Re-upload your documents' : 'Your documents'; ?></h1>
            <p class="signin-desc"><?php
                if ($imagesMissing) {
                    echo 'Your Ghana Card images were not saved. Please upload them again below. You can leave business details as they are.';
                } else {
                    echo "Submit your Ghana Card, business details, and photos. We'll guide you step by step.";
                }
            ?></p>
            <?php if ($documentsError !== ''): ?>
            <p class="form-error form-error-swal" style="margin-bottom:1rem;color:#c00;font-size:0.95rem;" data-message="<?php echo htmlspecialchars($documentsError); ?>"><?php echo htmlspecialchars($documentsError); ?></p>
            <?php endif; ?>

            <form class="signin-form" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_documents">
                <?php if ($imagesMissing): ?><input type="hidden" name="reupload" value="1"><?php endif; ?>

                <div class="doc-section">
                    <h2>Ghana Card</h2>
                    <div class="ghana-card-row">
                        <div class="ghana-card-box">
                            <label for="ghana_card_front">Front</label>
                            <input type="file" id="ghana_card_front" name="ghana_card_front" accept="image/*">
                        </div>
                        <div class="ghana-card-box">
                            <label for="ghana_card_back">Back</label>
                            <input type="file" id="ghana_card_back" name="ghana_card_back" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="doc-section">
                    <h2>Business details</h2>
                    <div class="form-group">
                        <label for="business_type">Type of business</label>
                        <input type="text" id="business_type" name="business_type" value="<?php echo $app ? htmlspecialchars((string)($app['business_type'] ?? '')) : ''; ?>" placeholder="e.g. Market stall, tailoring" autocomplete="organization">
                    </div>
                    <div class="form-group">
                        <label for="business_duration">How long have you run it?</label>
                        <input type="text" id="business_duration" name="business_duration" value="<?php echo $app ? htmlspecialchars((string)($app['business_duration'] ?? '')) : ''; ?>" placeholder="e.g. 2 years">
                    </div>
                    <div class="form-group">
                        <label for="business_location">Business location</label>
                        <input type="text" id="business_location" name="business_location" value="<?php echo $app ? htmlspecialchars((string)($app['business_location'] ?? '')) : ''; ?>" placeholder="e.g. Adenta Market">
                    </div>
                </div>

                <div class="doc-section">
                    <h2>Photos (2–3)</h2>
                    <p class="form-hint" style="margin-bottom: 1rem;">Stall, goods, or you at work.</p>
                    <div class="photo-upload-row">
                        <div class="photo-upload-box">
                            <label for="photo_1">Photo 1</label>
                            <input type="file" id="photo_1" name="photo_1[]" accept="image/*">
                        </div>
                        <div class="photo-upload-box">
                            <label for="photo_2">Photo 2</label>
                            <input type="file" id="photo_2" name="photo_2[]" accept="image/*">
                        </div>
                    </div>
                    <div class="photo-upload-box" style="margin-top: 1rem;">
                        <label for="photo_3">Photo 3 (optional)</label>
                        <input type="file" id="photo_3" name="photo_3[]" accept="image/*">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="documents-submit-btn"><?php echo $imagesMissing ? 'Save images' : 'Continue'; ?></button>
            </form>
            <?php endif; ?>
        </div>
    </main>
    <script>
    (function() {
        var form = document.querySelector('.signin-form');
        var isReupload = <?php echo $imagesMissing ? 'true' : 'false'; ?>;
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('documents-submit-btn');
                var submitForm = function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: isReupload ? 'Saving…' : 'Submitting…', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                    }
                    form.submit();
                };
                if (typeof Swal !== 'undefined' && isReupload) {
                    Swal.fire({
                        icon: 'question',
                        title: 'Save images?',
                        text: 'Your Ghana Card and photos will be saved. Continue?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save',
                        cancelButtonText: 'Cancel'
                    }).then(function(r) {
                        if (r.isConfirmed) submitForm();
                    });
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'question',
                        title: 'Submit documents?',
                        text: 'Your documents will be submitted. Continue?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, continue',
                        cancelButtonText: 'Cancel'
                    }).then(function(r) {
                        if (r.isConfirmed) submitForm();
                    });
                } else {
                    submitForm();
                }
            });
        }
        var errEl = document.querySelector('.form-error-swal');
        if (errEl && typeof Swal !== 'undefined') {
            var msg = errEl.getAttribute('data-message') || errEl.textContent;
            errEl.style.display = 'none';
            Swal.fire({ icon: 'error', title: 'Documents', text: msg });
        }
    })();
    </script>
</body>
</html>
