<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Cegah akses tanpa login
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../frontend/auth.php");
  exit;
}

include 'header.php';
include 'sidebar.php';

// Ambil data user berdasarkan session login
 $id_user = $_SESSION['user_id'];

 $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
 $stmt->execute([$id_user]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data foto profil dari tabel user_profile
 $stmt = $pdo->prepare("SELECT * FROM user_profile WHERE id_user = ?");
 $stmt->execute([$id_user]);
 $profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika belum ada record di user_profile, buat dulu
if (!$profile) {
  $pdo->prepare("INSERT INTO user_profile (id_user) VALUES (?)")->execute([$id_user]);
  $profile = ['foto_profil' => null];
}

// Variabel untuk menyimpan pesan notifikasi
 $notification_message = '';
 $notification_type = '';

// Proses upload foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_foto'])) {
  if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['foto_profil']['tmp_name'];
    $file_name = basename($_FILES['foto_profil']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_ext, $allowed)) {
      $notification_message = 'Format file tidak diizinkan! Gunakan JPG/PNG.';
      $notification_type = 'danger';
    } else {
      $new_name = 'profile_' . $id_user . '.' . $file_ext;
      $upload_dir = __DIR__ . '/uploads/profil/';
      $upload_path = $upload_dir . $new_name;

      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      // Hapus foto lama jika ada
      if (!empty($profile['foto_profil']) && file_exists($upload_dir . $profile['foto_profil'])) {
        unlink($upload_dir . $profile['foto_profil']);
      }

      if (move_uploaded_file($file_tmp, $upload_path)) {
        $stmt = $pdo->prepare("UPDATE user_profile SET foto_profil = ? WHERE id_user = ?");
        $stmt->execute([$new_name, $id_user]);
        $notification_message = 'Foto profil berhasil diperbarui!';
        $notification_type = 'success';
      } else {
        $notification_message = 'Gagal mengunggah file.';
        $notification_type = 'danger';
      }
    }
  }
}

if (!$user) {
  die("User tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Profil</title>
  <link rel="stylesheet" href="assets/css/sipora-admin.css">
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    .invalid-feedback {
      display: none;
      width: 100%;
      margin-top: 0.25rem;
      font-size: 0.875em;
      color: #dc3545;
    }
    
    .form-control.is-invalid {
      border-color: #dc3545;
    }
    
    /* Custom SweetAlert2 Blue White Theme */
    .swal2-popup {
      background-color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 123, 0.15);
    }
    
    .swal2-title {
      color: #0056b3;
      font-weight: 600;
    }
    
    .swal2-html-container {
      color: #495057;
    }
    
    .swal2-confirm {
      background-color: #0056b3 !important;
      border-radius: 4px;
    }
    
    .swal2-confirm:hover {
      background-color: #004085 !important;
    }
    
    .swal2-cancel {
      background-color: #6c757d !important;
      border-radius: 4px;
    }
    
    .swal2-cancel:hover {
      background-color: #5a6268 !important;
    }
    
    .swal2-icon.swal2-success {
      border-color: #28a745;
      color: #28a745;
    }
    
    .swal2-icon.swal2-error {
      border-color: #dc3545;
      color: #dc3545;
    }
    
    .swal2-icon.swal2-warning {
      border-color: #1657ef;
      color: #1657ef;
    }
    
    .swal2-icon.swal2-info {
      border-color: #1657ef;
      color: #1657ef;
    }
  </style>
</head>

<body>
<div class="main-panel">
  <div class="content-wrapper">

    <!-- CARD YANG SUDAH DIGANTI CLASS -->
    <div class="card shadow-sm profile-card">
      <div class="card-body">

        <h4 class="profile-header">
            <i class="mdi mdi-account-circle"></i> Profil Saya
        </h4>

        <div class="row">
          <div class="col-md-4 text-center">

            <?php
              $foto_path = !empty($profile['foto_profil']) && file_exists(__DIR__ . '/uploads/profil/' . $profile['foto_profil'])
                ? 'uploads/profil/' . $profile['foto_profil']
                : 'assets/images/profile.png';
            ?>

            <img src="<?= htmlspecialchars($foto_path); ?>" 
                 class="img-fluid rounded-circle mb-3"
                 style="width:150px; height:150px; object-fit:cover;">

            <form method="POST" enctype="multipart/form-data">
              <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png" class="form-control mb-2" required>
              <button type="submit" name="ganti_foto" class="btn btn-sm btn-secondary">
                <i class="mdi mdi-upload"></i> Ganti Foto
              </button>
            </form>

            <h5 class="mt-3"><?= htmlspecialchars($user['nama_lengkap']); ?></h5>
            <p class="text-muted">
              <?= ucfirst(htmlspecialchars($user['role'])); ?> 
              (<?= htmlspecialchars($user['status']); ?>)
            </p>

          </div>

          <div class="col-md-8">

            <table class="profile-table th">
              <tr>
                <th width="200">Nama Lengkap</th>
                <td>: <?= htmlspecialchars($user['nama_lengkap']); ?></td>
              </tr>
              <tr>
                <th>Email</th>
                <td>: <?= htmlspecialchars($user['email']); ?></td>
              </tr>
              <tr>
                <th>Username</th>
                <td>: <?= htmlspecialchars($user['username']); ?></td>
              </tr>
              <tr>
                <th>Nomor Induk</th>
                <td>: <?= htmlspecialchars($user['nim'] ?? '-'); ?></td>
              </tr>
              <tr>
                <th>Tanggal Bergabung</th>
                <td>: <?= date('d M Y', strtotime($user['created_at'])); ?></td>
              </tr>
            </table>

            <div class="mt-4">
              <button class="btn-gradient" data-bs-toggle="modal" data-bs-target="#editProfilModal">
                <i class="mdi mdi-pencil"></i> Edit Profil
              </button>
              <button class="btn-gradient2" data-bs-toggle="modal" data-bs-target="#ubahPasswordModal">
                <i class="mdi mdi-lock"></i> Ubah Password
              </button>
            </div>

          </div>
        </div>

      </div>
    </div>

  </div>
  <?php include 'footer.php'; ?>
</div>

<!-- Modal Edit Profil -->
<div class="modal fade" id="editProfilModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="update_profil.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Edit Profil</h5>
          <button type="buttonFpro" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_user" value="<?= $user['id_user']; ?>">

          <div class="mb-3">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
          </div>

          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
          </div>

          <div class="mb-3">
            <label>Nomor Induk</label>
            <input type="text" name="nim" class="form-control" value="<?= htmlspecialchars($user['nim'] ?? ''); ?>">
          </div>

          <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ubah Password -->
<div class="modal fade" id="ubahPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="ubahPasswordForm" action="ubah_password.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title">Ubah Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_user" value="<?= $user['id_user']; ?>">
          
          <div id="general-error" class="alert alert-danger d-none"></div>

          <div class="mb-3">
            <label>Password Lama</label>
            <input type="password" name="password_lama" class="form-control" required>
            <div class="invalid-feedback"></div>
          </div>

          <div class="mb-3">
            <label>Password Baru</label>
            <input type="password" name="password_baru" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="konfirmasi_password" class="form-control" required>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Ubah Password</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Set default SweetAlert2 configuration with blue white theme
  Swal.mixin({
    customClass: {
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false
  });
  
  // Tampilkan notifikasi SweetAlert2 jika ada pesan
  document.addEventListener('DOMContentLoaded', function() {
    // Cek parameter URL untuk pesan
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    
    if (msg) {
      let title = '';
      let text = '';
      let icon = '';
      
      switch(msg) {
        case 'password_updated':
          title = 'Berhasil!';
          text = 'Password berhasil diperbarui.';
          icon = 'success';
          break;
        case 'password_cancelled':
          title = 'Dibatalkan';
          text = 'Perubahan password dibatalkan.';
          icon = 'info';
          break;
        case 'wrong_old_password':
          title = 'Error!';
          text = 'Password lama yang Anda masukkan tidak sesuai. Silakan coba lagi.';
          icon = 'error';
          break;
        case 'confirm_failed':
          title = 'Error!';
          text = 'Password baru dan konfirmasi password tidak cocok!';
          icon = 'error';
          break;
        case 'user_not_found':
          title = 'Error!';
          text = 'User tidak ditemukan!';
          icon = 'error';
          break;
      }
      
      if (title) {
        Swal.fire({
          title: title,
          text: text,
          icon: icon,
          confirmButtonText: 'OK',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        }).then(() => {
          // Hapus parameter URL setelah notifikasi ditutup
          const newUrl = window.location.pathname;
          window.history.replaceState({}, document.title, newUrl);
        });
      }
    }
    
    // Tampilkan notifikasi untuk upload foto
    <?php if ($notification_message): ?>
    Swal.fire({
      title: "<?= $notification_type === 'success' ? 'Berhasil!' : 'Error!' ?>",
      text: "<?= $notification_message ?>",
      icon: "<?= $notification_type === 'success' ? 'success' : 'error' ?>",
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    }).then(() => {
      <?php if ($notification_type === 'success'): ?>
      // Refresh halaman setelah notifikasi sukses ditutup
      window.location.href = 'profil.php';
      <?php endif; ?>
    });
    <?php endif; ?>
    
    // Handle form ubah password
    const ubahPasswordForm = document.getElementById('ubahPasswordForm');
    if (ubahPasswordForm) {
      ubahPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset error messages
        document.querySelectorAll('.invalid-feedback').forEach(el => {
          el.textContent = '';
          el.style.display = 'none';
        });
        document.querySelectorAll('.form-control').forEach(el => {
          el.classList.remove('is-invalid');
        });
        document.getElementById('general-error').classList.add('d-none');
        
        // Kirim form dengan AJAX
        const formData = new FormData(this);
        
        fetch(this.action, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.message === 'confirm') {
              // Tampilkan konfirmasi SweetAlert
              Swal.fire({
                title: "Yakin ingin mengubah password?",
                text: "Password baru akan langsung menggantikan password lama.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, ubah!",
                cancelButtonText: "Batal",
                customClass: {
                  confirmButton: 'btn btn-primary',
                  cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
              }).then((result) => {
                if (result.isConfirmed) {
                  // Kirim konfirmasi
                  const confirmData = new FormData();
                  confirmData.append('confirm', 'yes');
                  confirmData.append('id_user', data.data.id_user);
                  confirmData.append('password_baru', data.data.password_baru);
                  
                  fetch('ubah_password.php', {
                    method: 'POST',
                    body: confirmData
                  })
                  .then(response => response.json())
                  .then(result => {
                    if (result.success) {
                      // Tutup modal
                      const modal = bootstrap.Modal.getInstance(document.getElementById('ubahPasswordModal'));
                      modal.hide();
                      
                      // Tampilkan notifikasi sukses
                      Swal.fire({
                        title: 'Berhasil!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                          confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                      });
                      
                      // Reset form
                      ubahPasswordForm.reset();
                    } else {
                      // Tampilkan error umum
                      document.getElementById('general-error').textContent = result.message;
                      document.getElementById('general-error').classList.remove('d-none');
                    }
                  })
                  .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('general-error').textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                    document.getElementById('general-error').classList.remove('d-none');
                  });
                }
              });
            } else {
              // Tutup modal
              const modal = bootstrap.Modal.getInstance(document.getElementById('ubahPasswordModal'));
              modal.hide();
              
              // Tampilkan notifikasi sukses
              Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK',
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
              
              // Reset form
              ubahPasswordForm.reset();
            }
          } else {
            // Tampilkan error di bawah field yang sesuai
            if (data.errors) {
              if (data.errors.general) {
                document.getElementById('general-error').textContent = data.errors.general;
                document.getElementById('general-error').classList.remove('d-none');
              }
              
              if (data.errors.password_lama) {
                const passwordLamaField = document.querySelector('input[name="password_lama"]');
                passwordLamaField.classList.add('is-invalid');
                passwordLamaField.nextElementSibling.textContent = data.errors.password_lama;
                passwordLamaField.nextElementSibling.style.display = 'block';
              }
              
              if (data.errors.konfirmasi_password) {
                const konfirmasiField = document.querySelector('input[name="konfirmasi_password"]');
                konfirmasiField.classList.add('is-invalid');
                konfirmasiField.nextElementSibling.textContent = data.errors.konfirmasi_password;
                konfirmasiField.nextElementSibling.style.display = 'block';
              }
            }
          }
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('general-error').textContent = 'Kata sandi lama yang Anda masukkan tidak sesuai';
          document.getElementById('general-error').classList.remove('d-none');
        });
      });
    }
  });
</script>
</body>
</html>