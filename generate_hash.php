<?php
// Password yang ingin Anda gunakan
$password_baru = 'passwordkuaman'; 

// Hasilkan hash untuk password baru tersebut
$hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

echo "<h2>Password Baru Anda: </h2>";
echo "Password Teks Biasa: **" . $password_baru . "**<br>";
echo "Hash Password (Copy Ini): <code style='user-select: all; background: #eee; padding: 5px; border: 1px solid #ccc;'>" . $hashed_password . "</code><br><br>";
echo "Simpan hash di atas ke database!";

// Hapus file ini setelah selesai!
?>