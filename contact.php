<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Get in touch — ' . SITE_NAME;
$metaDescription = 'Contact ' . SITE_NAME . ' for wedding film editing.';
$bodyClass = 'page-contact';

$serviceTopics = [
    'teasers' => 'Teasers',
    'highlights' => 'Highlights',
    'reels' => 'Reels',
    'traditional' => 'Traditional videos',
    'cinematic' => 'Cinematic films',
    'documentary' => 'Documentary films',
];
$topicKey = strtolower(trim((string) ($_GET['topic'] ?? '')));
$topicLabel = $serviceTopics[$topicKey] ?? null;
$waPrefill = $topicLabel !== null
    ? 'Hi Akhurath Studio — I’m interested in ' . $topicLabel . '.'
    : 'Hi Akhurath Studio — I’d like to chat about a project.';

$errors = [];
$sent = false;

/** One .txt file per submission (created on first use). */
$enquiriesDir = AKH_ROOT . '/data/contact-enquiries';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hp = trim((string) ($_POST['website'] ?? ''));
    if ($hp !== '') {
        $errors[] = 'Spam detected.';
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $company = trim((string) ($_POST['company'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $project = trim((string) ($_POST['project'] ?? ''));

    $postTopicKey = strtolower(trim((string) ($_POST['topic'] ?? '')));
    $postTopicLabel = $serviceTopics[$postTopicKey] ?? null;

    if ($name === '' || mb_strlen($name) > 200) {
        $errors[] = 'Please enter your name (max 200 characters).';
    }
    if ($company === '' || mb_strlen($company) > 200) {
        $errors[] = 'Please enter your company (max 200 characters).';
    }
    if ($phone === '' || mb_strlen($phone) > 40) {
        $errors[] = 'Please enter your phone number (max 40 characters).';
    } elseif (!preg_match('/^[0-9+\s().-]{7,40}$/u', $phone)) {
        $errors[] = 'Please enter a valid phone number (digits, spaces, +, brackets, or dashes).';
    }
    if ($project === '' || mb_strlen($project) > 8000) {
        $errors[] = 'Please describe your project (max 8000 characters).';
    }

    if ($errors === []) {
        $submittedAt = gmdate('c');
        $topicLine = $postTopicLabel !== null && $postTopicLabel !== '' ? $postTopicLabel : '—';

        $block = str_repeat('=', 72) . "\n"
            . 'Submitted (UTC): ' . $submittedAt . "\n"
            . 'Name: ' . $name . "\n"
            . 'Company: ' . $company . "\n"
            . 'Phone: ' . $phone . "\n"
            . 'Service topic: ' . $topicLine . "\n"
            . "Project details:\n" . $project . "\n";

        $dataDir = AKH_ROOT . '/data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        if (!is_dir($enquiriesDir)) {
            @mkdir($enquiriesDir, 0755, true);
        }

        $written = false;
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $suffix = bin2hex(random_bytes(4));
            $fileBase = gmdate('Y-m-d_His') . '_' . $suffix;
            $filePath = $enquiriesDir . '/' . $fileBase . '.txt';
            if (is_file($filePath)) {
                continue;
            }
            $written = @file_put_contents($filePath, $block, LOCK_EX) !== false;
            if ($written) {
                break;
            }
        }

        if (!$written) {
            $errors[] = 'We could not save your enquiry. Please try again or use WhatsApp below.';
        } else {
            $sent = true;
        }
    }
}

require_once AKH_ROOT . '/includes/header.php';
?>

  <main id="main" class="contact-main">
    <div class="contact-shell">
      <h1 class="contact-title">Tell us what you need</h1>
      <p class="contact-lead">Fields marked <span class="req">*</span> are required. Each submission is saved on this server as its own text file in a private folder for the studio to read.</p>

      <div class="contact-connect">
        <a class="btn btn--whatsapp btn--whatsapp-lg" href="<?php echo h(whatsapp_chat_url($waPrefill)); ?>" target="_blank" rel="noopener noreferrer">Message us on WhatsApp</a>
        <p class="contact-connect__hint">Opens the WhatsApp app or web chat — no number shown here; your thread stays private.</p>
      </div>

      <?php if ($topicLabel !== null): ?>
        <p class="banner banner--topic" role="status">You chose <strong><?php echo h($topicLabel); ?></strong> from our services — add dates and details below.</p>
      <?php endif; ?>

      <?php if ($sent): ?>
        <p class="banner banner--ok" role="status">Thank you — your enquiry was saved. We’ll get back to you soon.</p>
        <p><a class="text-link" href="<?php echo h(base_path('index.php')); ?>">← Back to home</a></p>
      <?php else: ?>
        <?php if ($errors !== []): ?>
          <ul class="banner banner--err" role="alert">
            <?php foreach ($errors as $e): ?>
              <li><?php echo h($e); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form class="contact-form" method="post" action="" novalidate>
          <p class="honeypot" aria-hidden="true">
            <label>Leave blank <input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
          </p>
          <?php if ($topicKey !== ''): ?>
            <input type="hidden" name="topic" value="<?php echo h($topicKey); ?>" />
          <?php endif; ?>
          <label class="field">
            <span>Name <span class="req">*</span></span>
            <input type="text" name="name" required maxlength="200" autocomplete="name" value="<?php echo h($_POST['name'] ?? ''); ?>" />
          </label>
          <label class="field">
            <span>Company <span class="req">*</span></span>
            <input type="text" name="company" required maxlength="200" autocomplete="organization" value="<?php echo h($_POST['company'] ?? ''); ?>" />
          </label>
          <label class="field">
            <span>Phone number <span class="req">*</span></span>
            <input type="tel" name="phone" required maxlength="40" autocomplete="tel" inputmode="tel" placeholder="+91 …" value="<?php echo h($_POST['phone'] ?? ''); ?>" />
          </label>
          <label class="field">
            <span>Project details <span class="req">*</span></span>
            <textarea name="project" rows="8" required maxlength="8000" placeholder="Timeline, deliverables, references, dates…"><?php echo h($_POST['project'] ?? ''); ?></textarea>
          </label>
          <button type="submit" class="btn btn--primary">Send message</button>
        </form>

        <p class="contact-alt">Prefer email? <a class="text-link" href="mailto:<?php echo h(LEADS_EMAIL); ?>"><?php echo h(LEADS_EMAIL); ?></a></p>
      <?php endif; ?>
    </div>
  </main>

<?php require_once AKH_ROOT . '/includes/footer.php'; ?>
