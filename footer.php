<?php
// footer.php
?>
    <?php if (isLoggedIn()): ?>
            </main>
        </div>
    </div>
    <?php else: ?>
    </main>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript untuk konfirmasi hapus
        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus data ini?');
        }

        // Auto-hide alert setelah 5 detik
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                try {
                    if (alert.classList.contains('alert-dismissible')) {
                        var bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                } catch (e) {
                    // Fallback jika Bootstrap Alert tidak tersedia
                    alert.style.display = 'none';
                }
            });
        }, 5000);

        // Fungsi untuk format input number sebagai Rupiah
        function formatRupiah(angka) {
            if (!angka) return '';
            var number_string = angka.toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                var separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return 'Rp ' + rupiah;
        }

        // Fungsi untuk validasi form
        function validateForm() {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }

        // Panggil validasi form saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            validateForm();
            
            // Auto-focus pada input pertama di modal
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
                    const input = this.querySelector('input[type="text"], input[type="email"], input[type="number"], select');
                    if (input) {
                        input.focus();
                    }
                });
            });
        });

        // Fungsi untuk menampilkan loading
        function showLoading() {
            const loading = document.createElement('div');
            loading.className = 'loading-overlay';
            loading.innerHTML = `
                <div class="d-flex justify-content-center align-items-center" style="height: 100vh; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; z-index: 9999;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2 text-white">Loading...</span>
                </div>
            `;
            document.body.appendChild(loading);
        }

        // Fungsi untuk menyembunyikan loading
        function hideLoading() {
            const loading = document.querySelector('.loading-overlay');
            if (loading) {
                loading.remove();
            }
        }

        // Event listener untuk form submit
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.classList.contains('show-loading')) {
                showLoading();
            }
        });

        // Fungsi untuk copy text
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <i class="bi bi-check-circle"></i> Berhasil disalin!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            });
        }
    </script>

    <?php if (isLoggedIn()): ?>
    <script>
        // Auto-refresh notifikasi setiap 30 detik
        setInterval(function() {
            // Anda bisa menambahkan AJAX call di sini untuk update notifikasi real-time
            // Contoh: updateBadgeCount();
        }, 30000);

        // Fungsi untuk update badge count (contoh)
        function updateBadgeCount() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.badge-notification');
                    if (badge && data.pending_count > 0) {
                        badge.textContent = data.pending_count;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
    <?php endif; ?>
</body>
</html>