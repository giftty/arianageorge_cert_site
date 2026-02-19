<?php
session_start();
$cert_code = isset($_GET['code']) ? $_GET['code'] : '';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="shortcut icon" href="favicon.png">

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="fonts/icomoon/style.css">
  <link rel="stylesheet" href="fonts/feather/style.css">
  <link rel="stylesheet" href="css/aos.css">
  <link rel="stylesheet" href="css/style.css">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Material Design Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">

  <title>User Registration - Ariana George Group</title>
  <style>
    :root {
      --primary-color: #c5a059;
      --secondary-color: #a88849;
    }

    body {
      background: #f4f7f6;
      font-family: 'Montserrat', sans-serif;
    }

    .registration-section {
      padding: 80px 0;
    }

    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
    }

    .card-header {
      background: var(--primary-color);
      color: white;
      border-radius: 15px 15px 0 0 !important;
      padding: 25px;
      text-align: center;
    }

    .card-title {
      margin-bottom: 0;
      font-weight: 700;
      font-size: 24px;
    }

    .btn-primary {
      background: var(--primary-color);
      border-color: var(--primary-color);
      padding: 12px 25px;
      font-weight: 700;
      border-radius: 8px;
    }

    .btn-primary:hover {
      background: var(--secondary-color);
      border-color: var(--secondary-color);
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.25);
    }

    /* Image Upload Styles (from admin) */
    .image-upload-wrapper {
      width: 150px;
      height: 150px;
      margin: 0 auto 30px;
      position: relative;
    }

    .image-preview-container {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid var(--primary-color);
      background: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .image-preview-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
    }

    .image-preview-container .upload-placeholder {
      text-align: center;
      color: #6c757d;
    }

    .image-preview-container .upload-placeholder i {
      font-size: 40px;
      display: block;
    }

    .drag-drop-zone {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      cursor: pointer;
      z-index: 5;
      transition: background 0.3s;
    }

    .drag-drop-zone.dragover {
      background: rgba(197, 160, 89, 0.2);
      border: 2px dashed var(--primary-color);
    }

    .image-upload-wrapper.is-invalid .image-preview-container {
      border-color: #dc3545;
    }

    .image-upload-wrapper.is-invalid .invalid-feedback {
      display: block;
      text-align: center;
      margin-top: 10px;
    }

    #profile_image_input {
      display: none;
    }

    .mb-20 { margin-bottom: 20px; }
  </style>
</head>

<body>

  <div class="container registration-section">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="text-center mb-5">
          <a href="index.html">
            <img src="images/ariana_icon.png" alt="Logo" style="width:200px; height:auto;">
          </a>
        </div>
        
        <div class="card" data-aos="fade-up">
          <div class="card-header">
            <h4 class="card-title">Registration Form</h4>
            <p class="mb-0 text-white-50">Please fill in your details to process your certificate</p>
          </div>
          <div class="card-body p-5">
            <form id="registrationForm" enctype="multipart/form-data" novalidate>
              
              <div class="image-upload-wrapper" id="profileImageWrapper">
                <div class="image-preview-container" id="imagePreviewContainer">
                  <div class="upload-placeholder" id="uploadPlaceholder">
                    <i class="mdi mdi-camera"></i>
                    <span>Upload Photo</span>
                  </div>
                  <img id="imagePreview" src="" alt="Preview">
                  <div class="drag-drop-zone" id="dragDropZone"></div>
                </div>
                <input type="file" id="profile_image_input" name="profile_image" accept="image/*">
                <div class="invalid-feedback">Profile image is required.</div>
              </div>

              <div class="row mb-20">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Enter First Name" required>
                    <div class="invalid-feedback">First name is required.</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Enter Last Name" required>
                    <div class="invalid-feedback">Last name is required.</div>
                  </div>
                </div>
              </div>

              <div class="row mb-20">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email">
                    <div class="invalid-feedback">Please enter a valid email.</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter Phone Number">
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </div>

              <div class="form-group mb-20">
                <label for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter Address"></textarea>
                <div class="invalid-feedback"></div>
              </div>

              <div class="row mb-20">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" class="form-control" id="course" name="course" placeholder="Enter Course Name">
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="duration_of_course">Duration of Course</label>
                    <input type="text" class="form-control" id="duration_of_course" name="duration_of_course" placeholder="e.g. 6 Months">
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </div>

              <div class="row mb-20">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="resumption_date">Resumption Date</label>
                    <input type="date" class="form-control" id="resumption_date" name="resumption_date">
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="cert_code">Certificate Code</label>
                    <input type="text" class="form-control" id="cert_code" name="cert_code" value="<?php echo htmlspecialchars($cert_code); ?>" readonly>
                  </div>
                </div>
              </div>

              <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-block w-100 py-3" id="submitBtn">
                  Submit Registration
                </button>
              </div>

            </form>
          </div>
        </div>
        
        <div class="text-center mt-4">
          <p class="text-muted">&copy; <?php echo date('Y'); ?> Ariana George Group. All Rights Reserved.</p>
        </div>
      </div>
    </div>
  </div>

  <script src="js/jquery-3.5.1.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/aos.js"></script>

  <script>
    AOS.init();

    const dragDropZone = document.getElementById('dragDropZone');
    const fileInput = document.getElementById('profile_image_input');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    // Handle drag and drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dragDropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
      }, false);
    });

    dragDropZone.addEventListener('dragover', () => dragDropZone.classList.add('dragover'));
    ['dragleave', 'drop'].forEach(eventName => {
      dragDropZone.addEventListener(eventName, () => dragDropZone.classList.remove('dragover'));
    });

    dragDropZone.addEventListener('drop', e => {
      const dt = e.dataTransfer;
      const files = dt.files;
      handleFiles(files);
    });

    dragDropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function () {
      handleFiles(this.files);
    });

    function handleFiles(files) {
      if (files.length > 0) {
        const file = files[0];
        if (!file.type.startsWith('image/')) {
          Swal.fire('Error', 'Please upload an image file.', 'error');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          Swal.fire('Error', 'Image size must be less than 2MB.', 'error');
          return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        const reader = new FileReader();
        reader.onload = e => {
          imagePreview.src = e.target.result;
          imagePreview.style.display = 'block';
          uploadPlaceholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
        
        // Clear error state if any
        document.getElementById('profileImageWrapper').classList.remove('is-invalid');
      }
    }

    document.getElementById('registrationForm').addEventListener('submit', function (e) {
      e.preventDefault();
      
      // Reset validation states
      this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
      this.querySelectorAll('.invalid-feedback').forEach(el => {
        if (!el.closest('.image-upload-wrapper')) el.innerText = '';
      });

      let isValid = true;

      // Client-side validation
      const firstname = document.getElementById('firstname');
      const lastname = document.getElementById('lastname');
      const email = document.getElementById('email');
      const profileImage = document.getElementById('profile_image_input');

      if (!firstname.value.trim()) {
        firstname.classList.add('is-invalid');
        isValid = false;
      }
      if (!lastname.value.trim()) {
        lastname.classList.add('is-invalid');
        isValid = false;
      }
      if (email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        email.classList.add('is-invalid');
        isValid = false;
      }
      if (profileImage.files.length === 0) {
        document.getElementById('profileImageWrapper').classList.add('is-invalid');
        isValid = false;
      }

      if (!isValid) {
        const firstError = this.querySelector('.is-invalid');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      const submitBtn = document.getElementById('submitBtn');
      const originalBtnText = submitBtn.innerHTML;

      // Loading state
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Processing...';

      const formData = new FormData(this);

      fetch('/backend/backend.php?action=add_user&ajax=1', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          // Encrypt cert code for redirect
          fetch('/backend/backend.php?action=set_cert_session&code=' + encodeURIComponent(formData.get('cert_code')))
            .then(res => res.json())
            .then(sessionData => {
              Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                text: 'Your registration has been processed successfully.',
                confirmButtonColor: '#c5a059'
              }).then(() => {
                if (sessionData.status === 'success') {
                  window.location.href = '/backend/certpdf.php?code=' + sessionData.encrypted_code;
                } else {
                  window.location.href = '/backend/certpdf.php';
                }
              });
            });
        } else {
          if (data.errors) {
            // Apply server-side errors
            Object.keys(data.errors).forEach(key => {
              const field = document.getElementById(key);
              if (field) {
                field.classList.add('is-invalid');
                const feedback = field.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.innerText = data.errors[key];
              } else if (key === 'profile_image') {
                document.getElementById('profileImageWrapper').classList.add('is-invalid');
                document.getElementById('profileImageWrapper').querySelector('.invalid-feedback').innerText = data.errors[key];
              }
            });

            // Scroll to first error
            const firstError = this.querySelector('.is-invalid');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }

          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: data.message,
            confirmButtonColor: '#dc3545'
          });
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred. Please try again.',
          confirmButtonColor: '#dc3545'
        });
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      });
    });
  </script>
</body>

</html>
