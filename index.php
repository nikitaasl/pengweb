<?php
// ===== PHP SESSION UNTUK LOGIN =====
session_start();
date_default_timezone_set("Asia/Jakarta");

// ---- Logout ----
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: kuliahub.php");
    exit();
}

// ---- Proses Login ----
$loginError = "";
if (isset($_POST['username']) && isset($_POST['password'])) {
    $u = trim($_POST['username']);
    $p = trim($_POST['password']);
    if ($u === "778899" && $p === "Ub123") {
        $_SESSION['logged_in'] = true;
        header("Location: kuliahub.php"); // refresh agar tidak repost
        exit();
    } else {
        $loginError = "Username atau Password salah!";
    }
}
$isLogin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>KuliahUB - Agenda Kuliah & Tugas</title>
<style>
  body {font-family: Arial, sans-serif;margin:0;background:#f9fbff;}
  header {background:#004aad;color:white;padding:15px;display:flex;justify-content:space-between;align-items:center;}
  header h1 {margin:0;}
  nav a {color:white;margin:0 10px;text-decoration:none;font-weight:bold;}
  .container {padding:20px;}
  .card {background:white;padding:15px;margin-bottom:15px;border-radius:10px;
         box-shadow:0 2px 5px rgba(0,0,0,0.2);}
  .alert {color:red;font-weight:bold;}
  .login-box {width:320px;padding:25px;background:white;border-radius:10px;
             box-shadow:0 4px 10px rgba(0,0,0,0.3);text-align:center;margin:80px auto;}
  input, button, select {padding:10px;border-radius:5px;border:1px solid #aaa;box-sizing:border-box;}
  button {background:#004aad;color:white;cursor:pointer;border:none;font-size:16px;font-weight:bold;}
  .btn-small {padding:6px 12px;border-radius:5px;font-size:14px;}
  table {width:100%;border-collapse:collapse;background:white;margin-bottom:15px;}
  th, td {border:1px solid #ccc;padding:8px;text-align:left;}
  thead {background:#004aad;color:white;}
</style>
</head>
<body>
<?php if (!$isLogin): ?>
<!-- ===== LOGIN FORM (PHP SESSION) ===== -->
<div class="login-box">
  <h2>Login KuliahUB</h2>
  <form method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" style="width:90%;">Masuk</button>
  </form>
  <p style="color:red;"><?= htmlspecialchars($loginError) ?></p>
</div>

<?php else: ?>
<!-- ===== MAIN PAGE ===== -->
<header>
  <h1>KuliahUB</h1>
  <nav>
    <a href="#" onclick="showPage('jadwal')">Jadwal Kuliah</a>
    <a href="#" onclick="showPage('tugas')">Tugas</a>
    <a href="#" onclick="showPage('dashboard')">Dashboard</a>
    <a href="?logout=1">Logout</a>
  </nav>
</header>

<!-- DASHBOARD -->
<div class="container" id="dashboardPage" style="display:none;">
  <h2>Dashboard</h2>
  <div class="card">
    <h3>Jadwal Hari Ini</h3>
    <ul id="jadwalHariIni"></ul>
  </div>
  <div class="card">
    <h3>Tugas Belum Selesai</h3>
    <ul id="tugasBelum"></ul>
  </div>
  <div class="card">
    <h3>Total SKS</h3>
    <p id="totalSks"></p>
  </div>
</div>

<!-- JADWAL KULIAH -->
<div class="container" id="jadwalPage">
  <h2>Pilih Dari Tabel Referensi</h2>
  <div class="card">
    <table>
      <thead>
        <tr><th>Mata Kuliah</th><th>Hari</th><th>Jam</th><th>Ruangan</th><th>Dosen</th><th>SKS</th><th>Aksi</th></tr>
      </thead>
      <tbody id="referensiTable"></tbody>
    </table>
  </div>

  <h2>Input Jadwal Kuliah</h2>
  <div class="card">
    <input type="text" id="mk" placeholder="Nama Mata Kuliah">
    <select id="hari">
      <option value="">--Pilih Hari--</option>
      <option>Senin</option><option>Selasa</option><option>Rabu</option>
      <option>Kamis</option><option>Jumat</option>
    </select>
    <input type="time" id="jam">
    <input type="text" id="ruang" placeholder="Ruangan">
    <input type="text" id="dosen" placeholder="Dosen Pengampu">
    <input type="number" id="sks" placeholder="SKS" min="1" max="6">
    <button onclick="tambahJadwal()">Tambah Jadwal</button>
    <p id="msgJadwal" style="color:red;"></p>
  </div>

  <h2>Daftar Jadwal Saya</h2>
  <table>
    <thead>
      <tr><th>Mata Kuliah</th><th>Hari</th><th>Jam</th><th>Ruangan</th><th>Dosen</th><th>SKS</th></tr>
    </thead>
    <tbody id="jadwalTable"></tbody>
  </table>
</div>

<!-- TUGAS -->
<div class="container" id="tugasPage" style="display:none;">
  <h2>Daftar Tugas Kuliah</h2>
  <ul id="daftarTugas"></ul>
</div>

<!-- Popup Upload -->
<div id="uploadBox" class="container" style="display:none;">
  <div class="login-box" style="width:400px;">
    <h3>Upload Tugas</h3>
    <p id="uploadTaskName"></p>
    <input type="file" id="fileUpload" accept=".pdf,.doc,.docx,.jpg,.jpeg">
    <button onclick="submitTugas()">Kumpulkan</button>
    <button style="background:gray;" onclick="cancelUpload()">Batal</button>
    <p id="uploadMsg" style="color:green;"></p>
  </div>
</div>

<script>
// ======= DATA =======
let jadwalKuliah=[];
let referensiKuliah=[
  {mk:"Pemrograman Lanjut",hari:"Senin",jam:"07:00",ruang:"Lab 1",dosen:"Pak Budi",sks:3},
  {mk:"Pemrograman Lanjut",hari:"Kamis",jam:"10:00",ruang:"Lab 2",dosen:"Pak Budi",sks:3},
  {mk:"Jaringan Komputer",hari:"Selasa",jam:"09:00",ruang:"R301",dosen:"Bu Sari",sks:3},
  {mk:"Jaringan Komputer",hari:"Jumat",jam:"13:00",ruang:"R302",dosen:"Bu Sari",sks:3},
  {mk:"Pengembangan Web",hari:"Rabu",jam:"08:00",ruang:"Lab 3",dosen:"Pak Rian",sks:2},
  {mk:"Pengembangan Web",hari:"Kamis",jam:"14:00",ruang:"Lab 3",dosen:"Pak Rian",sks:2},
];
let tugasKuliah=[
  {nama:"Laporan Praktikum Topologi Jaringan",deadline:"2025-09-21T00:00",matkul:"Jaringan Komputer",status:false},
  {nama:"Tugas 2 Modul 2 Pemrograman",deadline:"2025-09-23T23:59",matkul:"Pemrograman Lanjut",status:false},
  {nama:"Membuat diagram dan user story",deadline:"2025-09-24T00:00",matkul:"Pengembangan Aplikasi Web",status:false},
];
let currentUploadIndex=null;

function showPage(p){
  ["dashboardPage","jadwalPage","tugasPage"].forEach(id=>document.getElementById(id).style.display="none");
  if(p=="dashboard")document.getElementById("dashboardPage").style.display="block";
  if(p=="jadwal")document.getElementById("jadwalPage").style.display="block";
  if(p=="tugas")document.getElementById("tugasPage").style.display="block";
}
function loadReferensi(){
  let tb=document.getElementById("referensiTable");tb.innerHTML="";
  referensiKuliah.forEach((r,i)=>{
    tb.innerHTML+=`<tr>
      <td>${r.mk}</td><td>${r.hari}</td><td>${r.jam}</td><td>${r.ruang}</td><td>${r.dosen}</td><td>${r.sks}</td>
      <td><button class="btn-small" onclick="isiForm(${i})">Pilih</button></td>
    </tr>`;
  });
}
function isiForm(i){
  let r=referensiKuliah[i];
  mk.value=r.mk;hari.value=r.hari;jam.value=r.jam;ruang.value=r.ruang;
  dosen.value=r.dosen;sks.value=r.sks;
}
function tambahJadwal(){
  if(!mk.value||!hari.value||!jam.value||!ruang.value||!dosen.value||!sks.value){
    msgJadwal.innerText="Semua field harus diisi!";return;
  }
  jadwalKuliah.push([mk.value,hari.value,jam.value,ruang.value,dosen.value,parseInt(sks.value)]);
  msgJadwal.innerText="";
  ["mk","hari","jam","ruang","dosen","sks"].forEach(id=>document.getElementById(id).value="");
  loadDashboard();
}
function hitungTotalSKS(){return jadwalKuliah.reduce((a,b)=>a+b[5],0);}
function isNearDeadline(dl){let now=new Date();return (new Date(dl)-now)/(1000*60*60*24)<=2;}
function loadDashboard(){
  let today=new Date().toLocaleDateString('id-ID',{weekday:'long'});
  jadwalHariIni.innerHTML="";
  jadwalKuliah.forEach(j=>{if(j[1]==today){jadwalHariIni.innerHTML+=`<li>${j[0]} - ${j[2]} - ${j[3]}</li>`;}});
  tugasBelum.innerHTML="";
  tugasKuliah.forEach((t,i)=>{
    if(!t.status){
      let d=new Date(t.deadline);
      let str=d.toLocaleDateString('id-ID',{weekday:'long',day:'2-digit',month:'long',year:'numeric'})+" "+
              d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
      let alert=isNearDeadline(t.deadline)?" <span class='alert'>(Deadline Mendekat!)</span>":"";
      tugasBelum.innerHTML+=`<li>${t.nama} - ${t.matkul}<br>Deadline: ${str}${alert}
      <button class="btn-small" onclick="openUpload(${i})">Kumpulkan</button></li><br>`;
    }
  });
  totalSks.innerText=hitungTotalSKS()+" SKS";
  jadwalTable.innerHTML="";
  jadwalKuliah.forEach(j=>{
    jadwalTable.innerHTML+=`<tr><td>${j[0]}</td><td>${j[1]}</td><td>${j[2]}</td><td>${j[3]}</td><td>${j[4]}</td><td>${j[5]}</td></tr>`;
  });
  daftarTugas.innerHTML="";
  tugasKuliah.forEach(t=>{
    let d=new Date(t.deadline);
    let str=d.toLocaleDateString('id-ID',{weekday:'long',day:'2-digit',month:'long',year:'numeric'})+" "+
            d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
    let st=t.status?"<span style='color:green'>[Sudah]</span>":"<span style='color:red'>[Belum]</span>";
    daftarTugas.innerHTML+=`<li>${t.nama} - ${t.matkul}<br>Deadline: ${str} ${st}</li><br>`;
  });
}
function openUpload(i){
  currentUploadIndex=i;
  uploadTaskName.innerText=tugasKuliah[i].nama;
  uploadBox.style.display="block";
  document.querySelector("header").style.display="none";
}
function cancelUpload(){
  uploadBox.style.display="none";
  document.querySelector("header").style.display="flex";
}
function submitTugas(){
  let file=fileUpload.files[0];
  if(file){
    tugasKuliah[currentUploadIndex].status=true;
    uploadMsg.innerText="Tugas selesai dikumpulkan!";
    setTimeout(()=>{
      uploadBox.style.display="none";
      document.querySelector("header").style.display="flex";
      loadDashboard();
    },1500);
  } else {
    uploadMsg.innerText="Harap pilih file sebelum submit!";
  }
}

// --- Init ---
loadReferensi();
showPage('jadwal');
loadDashboard();
</script>
<?php endif; ?>
</body>
</html>