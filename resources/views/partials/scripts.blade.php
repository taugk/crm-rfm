<script src="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('assets/vendors/apexcharts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Global Alert & Confirmation Logic --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Notifikasi Sukses (Session Flash)
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });
            @endif

            // 2. Notifikasi Error (Session Flash)
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#435ebe'
                });
            @endif

            // 3. Notifikasi Error Validasi ($errors)
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Input',
                    text: 'Mohon periksa kembali field yang wajib diisi.',
                    confirmButtonColor: '#435ebe'
                });
            @endif

            // 4. Konfirmasi Hapus Global
            // Cukup tambahkan class="delete-confirm" pada tombol hapus dalam form
            document.body.addEventListener('click', function (e) {
                const target = e.target.closest('.delete-confirm');
                if (target) {
                    e.preventDefault();
                    const form = target.closest('form');
                    
                    Swal.fire({
                        title: 'Konfirmasi Hapus',
                        text: "Data yang dihapus mungkin tidak dapat dikembalikan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            });

            document.addEventListener('click', function(e){
                const target = e.target.closest('.edit-confirm');
                if(target){
                    e.preventDefault();
                    const form = target.closest('form');

                    Swal.fire({
                        title: 'Konfirmasi Edit',
                        text: "Apakah Anda yakin ingin mengubah data ini?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Ubah!',
                        cancelButtonText: 'Batal',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            })
        });
    </script>

    

{{-- Stack khusus untuk script tambahan di halaman tertentu --}}
@stack('scripts')