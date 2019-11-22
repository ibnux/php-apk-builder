<?php
/**
 * Gunakan parameter ?app=aplikasi dimana aplikasi adalah folder yang ada konfigurasinya
 * Script ini hanya untuk gitlab, jika menggunakan github atau bitbucket, silahkan buat lagi script yang bisa ambil parameter branch
 * tidak mendukung branch dengan spesial karakter
**/

$app = $_GET['app'];
$data = file_get_contents('php://input');
$json = json_decode($data, true);

//Ambil nama branch
$branch = str_replace("refs/heads/","",$json["ref"]);

// Jika kosong maka ambil master
if(empty($branch)) $branch = "master";

// ini untuk simpan parameter yang dikirim dari gitlab, buat diambil isi commit developer
if(!empty("$app/pull.json")) unlink("$app/pull.json");
file_put_contents("$app/pull.json",$data);

// Jalankan script pull + build dengan Thread terpisah
shell_exec("/usr/bin/nohup /usr/bin/php -f /var/www/html/build.php $app $branch > /dev/null &");

//beritahu gitlab sudah diterima
echo "RECEIVED";
