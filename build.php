<?php

/** SCRIPT INI DIBUAT DALAM SEHARI, WAJAR JIKA ACAK ACAKAN :P

Semua script berada di /var/www
dimana yang di expose oleh web server adalah folder /var/www/html
script ini ada di /var/www/html

JAVA_HOME=/var/www/java/
GRADLE_HOME=/var/www/gradle/
ANDROID_HOME=/var/www/android/

download Java JRE, Gradle dan Android SDK, lalu simpan dengan nama diatas 

**/

include "func.inc.php";


$app = $_GET['app'];
$GLOBALS['app'] = $app;
if(empty($app))
   $app = $argv[1];

if(!empty($_REQUEST['app'])) $app = $_REQUEST['app'];

// jika folder aplikasi tidak ada, maka akan berhenti
if(!empty($app) && !file_exists("./$app/")){
        die("[]");
}


// ini adalah kata sandi untuk signing key android
// harus disimpan di folder yang tidak dapat diakses oleh luar
// bersebelahan dengan kestore, namanya  harus sesuai dengan nama aplikasi
// /var/www/aplikasi.pass
// /var/www/aplikasi.keystore
// isinya
// --ks-key-alias ibnux --ks-pass pass:qweasdzxc --key-pass pass:qweasdzxc
if(!file_exists('/var/www/'.$app.'.pass')){
    die("No Pass for $app");
}

//
if(!empty($argv[2])) $branch = $argv[2];
if(!empty($_REQUEST['branch'])) $branch = $_REQUEST['branch'];

//  siapkan environtment
//pastikan java untuk linux ada di folder ini
putenv('JAVA_HOME=/var/www/java/');
putenv('GRADLE_HOME=/var/www/gradle/');
putenv('ANDROID_HOME=/var/www/android/');

//ambil pull.json untuk ambil isi commits
if(file_exists("$app/pull.json")){
    $json = json_decode(file_get_contents("$app/pull.json"),true);
    $username = $json['user_name'];
    //$git = $json['project']['git_ssh_url'];
    $message = "";
    $commits = array_reverse($json['commits']);
    $nn = 1;
    for($n=0;$n<$json['total_commits_count'];$n++){
        if(!empty($commits[$n]['message'])){
            $message .= $nn.". ".$commits[$n]['message']."\r\n";
            $nn++;
        }
    }
    $message .= "\r\n";
    //$message = $json['commits'][$json['total_commits_count']-1]['message'];
}

//isi email
$email = "Dear Devs,
Aplikasi $app pada Branch $branch
Menerima permintaan Build oleh $username
Pesan:\r\n<i>$message</i>
\r\n\r\n";

// Kirim pesan ke Telegram
kirimPesan("

( ͡° ͜ʖ ( ͡° ͜ʖ ͡°) ͜ʖ ͡°)


Wahai Para Pejuang Kode,
Aplikasi $app telah mendapatkan wangsit dari Cabang $branch
Oleh $username
Pesan: $message");

if(file_exists("$app/build.log")){
    unlink("$app/build.log");
}

//parameter git ada disini
//digunakan untuk clone branch langsung
if(!file_exists("/var/www/html/".$app."/git.txt")){
    die("GIT Not Exists");
}

//jika folder branch belum ada, maka akan di clone
if(!file_exists($app."/branch-".bersihin($branch))){
    mkdir($app."/branch-".bersihin($branch));
    if(empty($git))
        $git = file_get_contents("/var/www/html/".$app."/git.txt");
    echo $app."/git.txt\n";
    echo 'git clone -b '.$branch.' '.trim($git).' '.$app.'/branch-'.bersihin($branch)."\n";
    $hasil =  shell_exec('git clone -b '.$branch.' '.trim($git).' '.$app.'/branch-'.bersihin($branch));
    file_put_contents("$app/build.log",$hasil."\n\n",FILE_APPEND);
    echo $hasil;
}else{
    //jika branch sudah ada, maka akan di pull
    $hasil =  shell_exec('cd '.$app.'/branch-'.bersihin($branch).' && git fetch --all && git reset --hard origin/'.$branch);
    file_put_contents("$app/build.log",$hasil."\n\n",FILE_APPEND);
}

//tambah androidhome di properties
file_put_contents($app.'/branch-'.bersihin($branch).'/local.properties','sdk.dir=/var/www/android/');

//ganti versi aplikasi sesuai isi build.txt
if(!file_exists($app."/build.txt")){
    file_put_contents($app."/build.txt","0");
}
$build = file_get_contents($app."/build.txt")*1;
$build +=1;
file_put_contents($app."/build.txt",$build);


$gradle = $app.'/branch-'.bersihin($branch).'/app/build.gradle';
$isiGradle = file_get_contents($gradle);
$explode = explode("\n",$isiGradle);
$jml = count($explode);
for($n=0;$n<$jml;$n++){
    if(strpos($explode[$n],"versionName")!==false){
        $kutip = explode('"',$explode[$n]);
        $versi = trim($kutip[1].".$build");
        file_put_contents($gradle,str_replace('versionName "'.$kutip[1].'"','versionName "'.$versi.'"',$isiGradle));
        kirimPesan("Aplikasi telah mendapatkan nomor $versi");
        break;
    }
}

$hasil = shell_exec('cd '.$app.'/branch-'.bersihin($branch).'/ && chmod +x gradlew && ./gradlew assembleRelease && echo "assembleRelease done"');
file_put_contents("$app/build.log",$hasil."\r\n\r\n",FILE_APPEND);

if(strpos($hasil,'BUILD SUCCESSFUL')>-1){
    $email .= "Build Sukses dengan Versi $versi\r\n";
    kirimPesan("

 ♪＼(*＾▽＾*)／＼(*＾▽＾*)／
                               
Aplikasi $app Branch $branch Berhasil di build!!");
    if(!file_exists($app.'/align/')) mkdir($app.'/align/');
    $passkeystore = trim(preg_replace('/\s\s+/', '',file_get_contents('/var/www/'.$app.'.pass')));
    $apk = $app.'_'.bersihin($branch).'_'.$versi.'.apk';
    $hasil = shell_exec('/var/www/android/build-tools/28.0.1/zipalign -v 4 /var/www/html/'.$app.'/branch-'.bersihin($branch).'/app/build/outputs/apk/release/app-release-unsigned.apk /var/www/html/'.$app.'/align/'.$apk);
    file_put_contents("$app/build.log",$hasil."\r\n\r\n",FILE_APPEND);

    $signing = '/var/www/android/build-tools/28.0.1/apksigner sign --ks /var/www/'.
        $app.'.keystore '.$passkeystore.' --v2-signing-enabled true --v1-signing-enabled true --in /var/www/html/'.
        $app.'/align/'.$apk.' '.
        '--out /var/www/html/'.$app.'/result/'.$apk;

    file_put_contents("$app/build.log",$signing."\r\n\r\n",FILE_APPEND);

    $hasil = shell_exec($signing);
    file_put_contents("$app/build.log",$hasil."\r\n\r\n",FILE_APPEND);

    if(file_exists('/var/www/html/'.$app.'/result/'.$apk)){
        kirimPesan("

(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧

Unduh di https://build.ibnux.net/".$app.'/result/'.$apk);
$kamut = getKamut();
$email .= "Unduh di <a href=\"https://build.ibnux.net/".$app.'/result/'.$app.'_'.bersihin($branch).'_'.$versi.".apk\">https://build.ibnux.net/".$app.'/result/'.$app.'_'.bersihin($branch).'_'.$versi.".apk</a>\r\n\r\n\r\nSalam Hormat\r\n\r\n.:Build Server.:\r\n\r\n\r\n".$kamut['text']."\n~ ".$kamut['author'];
kirimEmail("Aplikasi $app Branch $branch",$email);


kirimPesan(".\n \n( ͡° ͜ʖ ͡°)\n \n".$kamut['text']."\n\n~ ".$kamut['author']."\n\n");

    }else{
        kirimPesan("
       
 ┐(‘～`；)┌
    
Waduh, Aplikasi $app untuk Branch $branch Gagal ditandatangani...
Segera di cek para pendekar!!
щ(ಠ益ಠщ)");
}
}else{
    kirimPesan("

(✖﹏✖)
    
Waduh, Aplikasi $app untuk Branch $branch Gagal di bikin.
Segera di cek para pendekar!!
Pastikan semua file sudah di push!!

(┛◉Д◉)┛彡┻━┻");
}

//Jika Java tidak mati, bisa di killall, jadinya ngga bisa paralel build
//shell_exec("killall java");

readfile("$app/build.log");
 
