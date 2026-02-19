<?php
session_start();

const ENC_KEY = 'AGISL_CERT_SECRET_KEY';
const ENC_METHOD = 'AES-128-ECB';

function encryptCode($code)
{
    return urlencode(base64_encode(openssl_encrypt($code, ENC_METHOD, ENC_KEY)));
}

function decryptCode($encrypted)
{
    $decoded = openssl_decrypt(base64_decode(urldecode($encrypted)), ENC_METHOD, ENC_KEY);
    return $decoded !== false ? $decoded : $encrypted;
}

// Prioritize GET parameter over Session
$cert_code = null;

// Check for session expiration (40 minutes)
if (isset($_SESSION['view_cert_timestamp']) && (time() - $_SESSION['view_cert_timestamp'] > 40 * 60)) {
    unset($_SESSION['view_cert_code']);
    unset($_SESSION['view_cert_timestamp']);
}

if (isset($_GET['code'])) {
    // Came via encrypted URL (e.g., from QR code or redirect after registration)
    $cert_code = decryptCode($_GET['code']);
    // Refresh session timestamp on valid URL access
    if ($cert_code) {
        $_SESSION['view_cert_code'] = $cert_code;
        $_SESSION['view_cert_timestamp'] = time();
    }
}
else {
    // Direct access without a code — always clear session and prompt
    unset($_SESSION['view_cert_code']);
    unset($_SESSION['view_cert_timestamp']);
    $cert_code = null;
}

$dbFile = __DIR__ . '/data.db';
$show_prompt = false;

// Initialize defaults
$name = "---";
$issued = "---";
$validity_period = "---";
$certificate_code = "---";
$expiration_date = "---";
$bg_image = "certificate-bg.jpg";
$qr_code = "";
$user_phone = null;
$user_address = null;
$user_profile_image = null;
$cert_type = '';

if (!$cert_code) {
    $show_prompt = true;
}
else {
    try {
        $pdo = new PDO("sqlite:" . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch certificate
        $stmt = $pdo->prepare("SELECT * FROM certificates WHERE cert_code = :code");
        $stmt->execute([':code' => $cert_code]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            $show_prompt = true;
            $error_message = "Certificate not found.";
        }
        else {
            // Fetch user linked to this cert
            $stmt = $pdo->prepare("SELECT * FROM users WHERE cert_code = :code");
            $stmt->execute([':code' => $cert_code]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $name = $user ? ($user['firstname'] . ' ' . $user['lastname']) : $cert['owner'];
            $user_phone = $user['phone'] ?? null;
            $user_address = $user['address'] ?? null;
            $user_profile_image = $user['profile_image'] ?? null;

            $issued = $cert['date_issued'] ?: 'N/A';

            $validity_period = '2 years';
            if ($cert['date_issued'] && $cert['expiration_date']) {
                $date1 = new DateTime($cert['date_issued']);
                $date2 = new DateTime($cert['expiration_date']);
                $interval = $date1->diff($date2);
                $parts = [];
                if ($interval->y > 0)
                    $parts[] = $interval->y . ($interval->y > 1 ? ' Years' : ' Year');
                if ($interval->m > 0)
                    $parts[] = $interval->m . ($interval->m > 1 ? ' Months' : ' Month');
                if ($interval->d > 0)
                    $parts[] = $interval->d . ($interval->d > 1 ? ' Days' : ' Day');
                if (!empty($parts))
                    $validity_period = implode(', ', $parts);
            }

            $certificate_code = $cert['cert_code'];
            $expiration_date = $cert['expiration_date'] ?: '2 years';
            $cert_type = strtolower($cert['cert_type'] ?? '');

            $bg_image_path = "cert_images/" . $cert_type . ".png";
            $bg_image_missing = false;
            if (file_exists(__DIR__ . '/' . $bg_image_path)) {
                $bg_image = $bg_image_path;
            }
            else {
                $bg_image = "certificate-bg.jpg";
                $bg_image_missing = true;
            }

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $current_url = "$protocol://$host" . $_SERVER['PHP_SELF'];
            $verification_link = "$current_url?code=" . encryptCode($certificate_code);
            $qr_code = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($verification_link);
        }
    }
    catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
        $show_prompt = true;
    }
}

// Resolve profile image URL
$profile_img_src = '/admin/assets/images/faces-clipart/pic-4.png'; // default
if ($user_profile_image && file_exists(dirname(__DIR__) . '/' . ltrim($user_profile_image, '/'))) {
    $profile_img_src = '/' . ltrim($user_profile_image, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificate – <?php echo htmlspecialchars($certificate_code); ?></title>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<!-- Material Design Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7/css/materialdesignicons.min.css">

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', sans-serif;
    background: #0f0f0f;
    min-height: 100vh;
    color: #e0e0e0;
  }

  /* ─── Layout ─────────────────────────────────── */
  .page-wrapper {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 20px 60px;
  }

  /* ─── Profile Card ────────────────────────────── */
  .profile-card {
    background: linear-gradient(135deg, #1a1a1a 0%, #252525 100%);
    border: 1px solid rgba(197,160,89,0.25);
    border-radius: 20px;
    padding: 36px 40px;
    display: flex;
    align-items: center;
    gap: 36px;
    margin-bottom: 40px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.5);
    position: relative;
    overflow: hidden;
  }

  .profile-card::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(197,160,89,0.12) 0%, transparent 70%);
    pointer-events: none;
  }

  .profile-avatar-wrap {
    flex-shrink: 0;
    position: relative;
  }

  .profile-avatar {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #c5a059;
    box-shadow: 0 0 0 6px rgba(197,160,89,0.15);
  }

  .profile-info {
    flex: 1;
  }

  .profile-name {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
  }

  .profile-badge {
    display: inline-block;
    background: linear-gradient(90deg, #c5a059, #e8c97e);
    color: #111;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    padding: 3px 12px;
    border-radius: 20px;
    margin-bottom: 18px;
  }

  .profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
  }

  .profile-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #aaa;
  }

  .profile-meta-item i {
    font-size: 1rem;
    color: #c5a059;
  }

  /* ─── Section Heading ─────────────────────────── */
  .section-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #c5a059;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(197,160,89,0.2);
  }

  /* ─── Certificate Section ─────────────────────── */
  .cert-section {
    margin-bottom: 36px;
  }

  .cert-container {
    width: 100%;
    overflow-x: auto;
    border-radius: 16px;
    box-shadow: 0 12px 60px rgba(0,0,0,0.6);
    border: 1px solid rgba(197,160,89,0.2);
  }

  .certificate {
    width: 1400px;
    height: 990px;
    position: relative;
    background: url('<?php echo $bg_image; ?>') no-repeat center;
    background-size: cover;
    font-family: Arial, sans-serif;
    <?php if ($show_prompt): ?>display:none;<?php
endif; ?>
  }

  .name {
    position: absolute;
    top: 440px;
    left: 0;
    width: 100%;
    text-align: center;
    font-size: 60px;
    font-weight: bold;
    color: #000;
  }

  .details {
    position: absolute;
    bottom: 200px;
    left: 150px;
    font-size: 20px;
    color: #333;
    line-height: 1.6;
  }

  .cert-code-box {
    position: absolute;
    bottom: 0.1px;
    left: 100px;
    font-size: 18px;
    color: #555;
    width: 1000px;
  }

  .validity-period {
    position: absolute;
    bottom: 27px;
    left: 70px;
    width: 1000px;
    font-size: 18px;
  }

  .issue-date {
    position: absolute;
    bottom: 55px;
    left: 40px;
    width: 1000px;
    font-size: 18px;
  }

  .expiration-date {
    position: absolute;
    left: 50px;
    width: 1000px;
    font-size: 18px;
    margin-top: 3px;
  }

  .qr {
    position: absolute;
    top: 808px;
    right: 270px;
    width: 120px;
    height: 120px;
  }

  .qr img { width: 100%; height: 100%; }

  /* ─── Download Button ─────────────────────────── */
  .download-section {
    text-align: center;
    margin-top: 10px;
  }

  .download-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #c5a059, #e8c97e);
    color: #111;
    border: none;
    padding: 14px 40px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    border-radius: 50px;
    box-shadow: 0 6px 24px rgba(197,160,89,0.35);
    transition: all 0.25s ease;
    letter-spacing: 0.5px;
  }

  .download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(197,160,89,0.5);
  }

  .download-btn-hidden { display: none !important; }

  /* ─── Responsive ──────────────────────────────── */
  @media (max-width: 640px) {
    .profile-card { flex-direction: column; text-align: center; }
    .profile-meta { justify-content: center; }
    .profile-name { font-size: 1.5rem; }
  }
</style>
</head>
<body>

<div class="page-wrapper">

  <?php if (!$show_prompt): ?>

  <!-- ── Profile Card ──────────────────────────── -->
  <div class="section-label">Certificate Holder</div>
  <div class="profile-card">
    <div class="profile-avatar-wrap">
      <img class="profile-avatar"
           src="<?php echo htmlspecialchars($profile_img_src); ?>"
           alt="<?php echo htmlspecialchars($name); ?>">
    </div>
    <div class="profile-info">
      <div class="profile-name"><?php echo htmlspecialchars($name); ?></div>
      <div class="profile-badge">Certificate Holder</div>
      <div class="profile-meta">
        <?php if ($user_phone): ?>
        <div class="profile-meta-item">
          <i class="mdi mdi-phone"></i>
          <span><?php echo htmlspecialchars($user_phone); ?></span>
        </div>
        <?php
    endif; ?>
        <?php if ($user_address): ?>
        <div class="profile-meta-item">
          <i class="mdi mdi-map-marker"></i>
          <span><?php echo htmlspecialchars($user_address); ?></span>
        </div>
        <?php
    endif; ?>
        <div class="profile-meta-item">
          <i class="mdi mdi-calendar-check"></i>
          <span>Issued: <?php echo htmlspecialchars($issued); ?></span>
        </div>
        <div class="profile-meta-item">
          <i class="mdi mdi-certificate"></i>
          <span><?php echo htmlspecialchars($certificate_code); ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Certificate ───────────────────────────── -->
  <div class="cert-section">
    <div class="section-label">Certificate</div>
    <div class="cert-container">
      <div class="certificate">
        <div class="name"><?php echo strtoupper($name); ?></div>
        <div class="details">
          <div class="issue-date"><strong><?php echo $issued; ?></strong></div>
          <div class="validity-period"><strong><?php echo $validity_period; ?></strong></div>
          <div class="cert-code-box"><strong><?php echo $certificate_code; ?></strong></div>
          <div class="expiration-date"><strong><?php echo $expiration_date; ?></strong></div>
        </div>
        <div class="qr" <?php if (!$qr_code): ?>style="display:none;"<?php
    endif; ?>>
          <img src="<?php echo $qr_code; ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── Download Button ───────────────────────── -->
  <?php if (!isset($_GET['download']) || $_GET['download'] !== '1'): ?>
  <div class="download-section">
    <button class="download-btn" id="downloadBtn" onclick="downloadPDF()">
      <i class="mdi mdi-download"></i> Download as PDF
    </button>
  </div>
  <?php
    endif; ?>

  <?php
endif; // end if not show_prompt ?>

</div><!-- end page-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
  <?php if ($show_prompt): ?>
  Swal.fire({
    title: 'Enter Certificate Code',
    text: 'Please enter the certificate code to view the certificate.',
    input: 'text',
    inputPlaceholder: 'e.g. AGISL/EHS/...',
    showCancelButton: false,
    confirmButtonText: 'View Certificate',
    confirmButtonColor: '#c5a059',
    allowOutsideClick: false,
    allowEscapeKey: false,
    inputValidator: (value) => {
      if (!value) return 'You need to enter a certificate code!'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const code = result.value;
      fetch('/backend/backend.php?action=set_cert_session&code=' + encodeURIComponent(code))
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            window.location.href = '?code=' + data.encrypted_code;
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Invalid certificate code.',
              confirmButtonColor: '#dc3545'
            }).then(() => { window.location.reload(); });
          }
        })
        .catch(() => { window.location.reload(); });
    }
  });
  <?php
endif; ?>

  <?php if (isset($bg_image_missing) && $bg_image_missing): ?>
  Swal.fire({
    icon: 'warning',
    title: 'Certificate Type Not Found',
    text: 'The background image for "<?php echo $cert_type; ?>" was not found.',
    confirmButtonColor: '#c5a059'
  }).then(() => { window.history.back(); });
  <?php
endif; ?>

  // Auto-trigger download
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('download') === '1') {
    setTimeout(downloadPDF, 1500);
  }
});

function downloadPDF() {
  const element = document.querySelector('.certificate');
  const downloadBtn = document.getElementById('downloadBtn');
  if (downloadBtn) downloadBtn.classList.add('download-btn-hidden');

  const opt = {
    margin: 0,
    filename: 'Certificate_<?php echo addslashes($name); ?>_<?php echo $certificate_code; ?>.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, logging: false, letterRendering: true },
    jsPDF: { unit: 'px', format: [1400, 990], orientation: 'landscape' }
  };

  html2pdf().set(opt).from(element).save()
    .then(() => { if (downloadBtn) downloadBtn.classList.remove('download-btn-hidden'); })
    .catch(err => {
      console.error('PDF Error:', err);
      if (downloadBtn) downloadBtn.classList.remove('download-btn-hidden');
    });
}
</script>
</body>
</html>
