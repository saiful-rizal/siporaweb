<?php
// index.php - Landing Page SIPORA dengan Tema Gradian
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIPORA - Sistem Informasi Polije Repository Assets</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
      overflow-x: hidden;
    }
    
    html {
      scroll-behavior: smooth;
    }
    
    /* --- Custom Gradients --- */
    :root {
      --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --gradient-accent: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --gradient-dark: linear-gradient(135deg, #434343 0%, #000000 100%);
      --gradient-blue: linear-gradient(135deg, #0066FF 0%, #0080FF 100%);
      --gradient-text: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Custom animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fadeInUp {
      animation: fadeInUp 0.8s ease-out;
    }

    /* Glass morphism effect */
    .glass {
      background: rgba(255, 255, 255, 0.25);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.18);
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #667eea; border-radius: 4px; }
    
    /* Card hover effects */
    .card-hover {
      transition: all 0.3s ease;
    }
    
    .card-hover:hover {
      transform: translateY(-8px);
    }
    
    /* Button hover effects */
    .btn-hover {
      transition: all 0.3s ease;
      background-size: 200% auto;
    }
    
    .btn-hover:hover {
      background-position: right center;
    }
    
    /* Gradient text */
    .gradient-text {
      background: var(--gradient-text);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Back to top button */
    .back-to-top {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 45px;
      height: 45px;
      background: var(--gradient-primary);
      color: white;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 1000;
    }
    
    .back-to-top.show {
      opacity: 1;
      visibility: visible;
    }
    
    .back-to-top:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
    }
    
    /* Dark mode toggle */
    .dark-mode-toggle {
      position: relative;
      width: 50px;
      height: 26px;
      background: #cbd5e1;
      border-radius: 13px;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .dark-mode-toggle.active {
      background: var(--gradient-primary);
    }
    
    .dark-mode-toggle-circle {
      position: absolute;
      top: 3px;
      left: 3px;
      width: 20px;
      height: 20px;
      background: white;
      border-radius: 50%;
      transition: transform 0.3s;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .dark-mode-toggle.active .dark-mode-toggle-circle {
      transform: translateX(24px);
    }
  </style>
</head>
<body class="text-gray-800">

  <!-- Preloader -->
  <div class="preloader fixed inset-0 bg-white flex justify-center items-center z-50" id="preloader">
    <div class="w-12 h-12 border-4 border-gray-200 border-t-purple-600 rounded-full animate-spin"></div>
  </div>

  <!-- Back to Top Button -->
  <div class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
  </div>

  <!-- NAVBAR -->
  <header class="glass fixed top-0 left-0 w-full z-50 transition-all duration-300" id="navbar">
    <div class="max-w-7xl mx-auto flex justify-between items-center py-3 px-6">
      <div class="flex items-center">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3 text-white" style="background: var(--gradient-primary);">
          <i class="fas fa-database"></i>
        </div>
        <h1 class="text-2xl font-black gradient-text">SIPORA</h1>
      </div>
      
      <nav class="space-x-8 hidden md:flex">
        <a href="#home" class="nav-link font-semibold text-purple-700 hover:text-purple-900 transition">Beranda</a>
        <a href="#fitur" class="nav-link text-gray-600 hover:text-gray-900 transition">Fitur</a>
        <a href="#statistik" class="nav-link text-gray-600 hover:text-gray-900 transition">Statistik</a>
        <a href="#testimoni" class="nav-link text-gray-600 hover:text-gray-900 transition">Testimoni</a>
        <a href="#tentang" class="nav-link text-gray-600 hover:text-gray-900 transition">Tentang</a>
        <a href="#kontak" class="nav-link text-gray-600 hover:text-gray-900 transition">Kontak</a>
      </nav>
      
      <div class="flex items-center space-x-4">
        <div class="dark-mode-toggle" id="darkModeToggle">
          <div class="dark-mode-toggle-circle"></div>
        </div>
        <a href="#" class="text-white px-6 py-2 rounded-full font-medium shadow-lg hover:shadow-xl transition-all btn-hover" style="background: var(--gradient-primary);">
          Masuk
        </a>
        <button class="md:hidden text-gray-800" id="mobileMenuToggle">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>
    
    <!-- Mobile Menu -->
    <div class="hidden md:hidden bg-white/95 backdrop-blur shadow-lg" id="mobileMenu">
      <div class="px-6 py-4 space-y-3">
        <a href="#home" class="block font-semibold text-purple-700 hover:text-purple-900 transition">Beranda</a>
        <a href="#fitur" class="block text-gray-600 hover:text-gray-900 transition">Fitur</a>
        <a href="#statistik" class="block text-gray-600 hover:text-gray-900 transition">Statistik</a>
        <a href="#testimoni" class="block text-gray-600 hover:text-gray-900 transition">Testimoni</a>
        <a href="#tentang" class="block text-gray-600 hover:text-gray-900 transition">Tentang</a>
        <a href="#kontak" class="block text-gray-600 hover:text-gray-900 transition">Kontak</a>
      </div>
    </div>
  </header>

  <!-- HERO SECTION (COMPACT WITH GRADIENT) -->
  <section id="home" class="pt-24 pb-16" style="background: var(--gradient-primary);">
    <div class="max-w-5xl mx-auto px-6 text-center text-white">
      <div class="animate-fadeInUp">
        <h1 class="text-5xl md:text-6xl font-black mb-4">
          SIPORA
        </h1>
        <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto opacity-90">
          Sistem Informasi Polije Repository Assets. Temukan dan kelola aset digital akademik dengan mudah.
        </p>
      </div>

      <!-- Search Widget -->
      <div class="glass rounded-2xl shadow-2xl p-6 md:p-8 animate-fadeInUp" style="animation-delay: 0.2s;">
        <div class="flex justify-center mb-6 bg-white/20 rounded-xl p-1">
          <button class="tab-btn px-6 py-2 font-semibold rounded-lg text-white bg-white/20 focus:outline-none transition-all" data-tab="mahasiswa">Portal Mahasiswa</button>
          <button class="tab-btn px-6 py-2 font-semibold text-white/70 hover:text-white rounded-lg focus:outline-none transition-all" data-tab="dosen">Portal Dosen</button>
          <button class="tab-btn px-6 py-2 font-semibold text-white/70 hover:text-white rounded-lg focus:outline-none transition-all" data-tab="umum">Layanan Umum</button>
        </div>

        <div id="mahasiswa" class="tab-content">
          <form class="flex flex-col md:flex-row gap-3">
            <input type="text" placeholder="Cari layanan (SIAKAD, E-Learning, ...)" class="search-input flex-1 px-5 py-3 bg-white/80 border border-white/30 rounded-xl focus:outline-none focus:bg-white transition text-gray-800 placeholder-gray-500">
            <button type="submit" class="text-white font-semibold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all btn-hover" style="background: var(--gradient-accent);">
              <i class="fas fa-search mr-2"></i> Cari
            </button>
          </form>
        </div>

        <div id="dosen" class="tab-content hidden">
          <form class="flex flex-col md:flex-row gap-3">
            <input type="text" placeholder="Cari layanan (Penilaian, Jadwal, ...)" class="search-input flex-1 px-5 py-3 bg-white/80 border border-white/30 rounded-xl focus:outline-none focus:bg-white transition text-gray-800 placeholder-gray-500">
            <button type="submit" class="text-white font-semibold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all btn-hover" style="background: var(--gradient-accent);">
              <i class="fas fa-search mr-2"></i> Cari
            </button>
          </form>
        </div>

        <div id="umum" class="tab-content hidden">
          <form class="flex flex-col md:flex-row gap-3">
            <input type="text" placeholder="Cari layanan (Pendaftaran, Alumni, ...)" class="search-input flex-1 px-5 py-3 bg-white/80 border border-white/30 rounded-xl focus:outline-none focus:bg-white transition text-gray-800 placeholder-gray-500">
            <button type="submit" class="text-white font-semibold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all btn-hover" style="background: var(--gradient-accent);">
              <i class="fas fa-search mr-2"></i> Cari
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- STATISTIK SECTION -->
  <section id="statistik" class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold gradient-text mb-4">Statistik Kami</h2>
        <p class="text-gray-500">Angka-angka yang menunjukkan pertumbuhan dan keberhasilan sistem kami</p>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="rounded-2xl shadow-lg p-6 card-hover text-center text-white" style="background: var(--gradient-primary);">
          <i class="fas fa-user-graduate text-3xl mb-3"></i>
          <div class="text-4xl font-black mb-1">15K+</div>
          <div class="font-medium">Mahasiswa Aktif</div>
        </div>
        <div class="rounded-2xl shadow-lg p-6 card-hover text-center text-white" style="background: var(--gradient-accent);">
          <i class="fas fa-chalkboard-teacher text-3xl mb-3"></i>
          <div class="text-4xl font-black mb-1">500+</div>
          <div class="font-medium">Dosen & Staff</div>
        </div>
        <div class="rounded-2xl shadow-lg p-6 card-hover text-center text-white" style="background: var(--gradient-blue);">
          <i class="fas fa-book text-3xl mb-3"></i>
          <div class="text-4xl font-black mb-1">50+</div>
          <div class="font-medium">Program Studi</div>
        </div>
        <div class="rounded-2xl shadow-lg p-6 card-hover text-center text-white" style="background: var(--gradient-dark);">
          <i class="fas fa-server text-3xl mb-3"></i>
          <div class="text-4xl font-black mb-1">99.9%</div>
          <div class="font-medium">Uptime Server</div>
        </div>
      </div>
    </div>
  </section>

  <!-- FITUR UNGGULAN -->
  <section id="fitur" class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto text-center px-6">
      <h2 class="text-3xl font-bold gradient-text mb-4">Fitur Unggulan</h2>
      <p class="text-gray-500 mb-12">Nikmati berbagai layanan digital terintegrasi untuk meningkatkan efisiensi akademik.</p>
      
      <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
          <div class="h-40 relative" style="background: var(--gradient-primary);">
            <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Manajemen Akademik" class="w-full h-full object-cover opacity-30 mix-blend-overlay">
            <i class="fas fa-graduation-cap text-white text-4xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
          </div>
          <div class="p-6 text-left">
            <h3 class="font-bold text-xl mb-2">Manajemen Akademik</h3>
            <p class="text-gray-600">Pengelolaan data mahasiswa, dosen, jadwal, dan nilai secara efisien.</p>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
          <div class="h-40 relative" style="background: var(--gradient-accent);">
            <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Layanan Digital" class="w-full h-full object-cover opacity-30 mix-blend-overlay">
            <i class="fas fa-laptop-house text-white text-4xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
          </div>
          <div class="p-6 text-left">
            <h3 class="font-bold text-xl mb-2">Layanan Digital</h3>
            <p class="text-gray-600">Mendukung berbagai layanan administratif secara online.</p>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
          <div class="h-40 relative" style="background: var(--gradient-blue);">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Integrasi Data" class="w-full h-full object-cover opacity-30 mix-blend-overlay">
            <i class="fas fa-network-wired text-white text-4xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
          </div>
          <div class="p-6 text-left">
            <h3 class="font-bold text-xl mb-2">Integrasi Data</h3>
            <p class="text-gray-600">Seluruh sistem saling terhubung untuk akses informasi mudah.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONI SECTION -->
  <section id="testimoni" class="py-16" style="background: var(--gradient-primary);">
    <div class="max-w-4xl mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold text-white mb-12">Apa Kata Mereka</h2>
      
      <div class="testimonial-container">
        <div class="testimonial-track" id="testimonialTrack">
          <div class="testimonial-slide">
            <div class="bg-white/20 backdrop-blur rounded-2xl p-8 text-white">
              <i class="fas fa-quote-left text-3xl mb-4 opacity-50"></i>
              <p class="text-lg italic mb-6">"SIPORA telah memudahkan saya dalam mengelola data mahasiswa dan nilai. Interface yang intuitif dan fitur yang lengkap."</p>
              <div class="flex items-center justify-center">
                <img src="https://picsum.photos/seed/user1/60/60.jpg" alt="User" class="w-12 h-12 rounded-full mr-3 border-2 border-white">
                <div class="text-left">
                  <h4 class="font-bold">Dr. Budi Santoso, M.Kom</h4>
                  <p class="text-sm opacity-80">Dosen Teknik Informatika</p>
                </div>
              </div>
            </div>
          </div>
          
          <div class="testimonial-slide">
            <div class="bg-white/20 backdrop-blur rounded-2xl p-8 text-white">
              <i class="fas fa-quote-left text-3xl mb-4 opacity-50"></i>
              <p class="text-lg italic mb-6">"Sebagai mahasiswa, saya sangat terbantu dengan adanya portal mahasiswa yang mudah diakses."</p>
              <div class="flex items-center justify-center">
                <img src="https://picsum.photos/seed/user2/60/60.jpg" alt="User" class="w-12 h-12 rounded-full mr-3 border-2 border-white">
                <div class="text-left">
                  <h4 class="font-bold">Siti Nurhaliza</h4>
                  <p class="text-sm opacity-80">Mahasiswa Semester 6</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex justify-center mt-6 space-x-2">
          <button class="w-2 h-2 rounded-full bg-white" id="testimonialDot0"></button>
          <button class="w-2 h-2 rounded-full bg-white/50" id="testimonialDot1"></button>
        </div>
      </div>
    </div>
  </section>

  <!-- KONTAK -->
  <section id="kontak" class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold gradient-text mb-4">Hubungi Kami</h2>
      <p class="text-gray-500 mb-8">Ada pertanyaan? Kami siap membantu Anda kapan saja.</p>
      
      <div class="flex flex-col md:flex-row gap-4 justify-center">
        <a href="mailto:info@polije.ac.id" class="flex items-center justify-center text-white px-8 py-3 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all btn-hover" style="background: var(--gradient-primary);">
          <i class="fas fa-envelope mr-2"></i> info@polije.ac.id
        </a>
        <a href="tel:(0331)123456" class="flex items-center justify-center text-white px-8 py-3 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all btn-hover" style="background: var(--gradient-accent);">
          <i class="fas fa-phone mr-2"></i> (0331) 123456
        </a>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-8 text-white" style="background: var(--gradient-dark);">
    <div class="max-w-6xl mx-auto px-6 text-center">
      <div class="flex items-center justify-center mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background: var(--gradient-primary);">
          <i class="fas fa-database text-white"></i>
        </div>
        <h3 class="text-2xl font-black">SIPORA</h3>
      </div>
      <p class="text-gray-400">&copy; <?php echo date('Y'); ?> Politeknik Negeri Jember. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Preloader
    window.addEventListener('load', () => {
      setTimeout(() => document.getElementById('preloader').style.display = 'none', 500);
    });

    // Back to top button
    const backToTopButton = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
      backToTopButton.classList.toggle('show', window.pageYOffset > 300);
    });
    backToTopButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenuToggle.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));

    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.classList.remove('bg-white/20'));
        tabContents.forEach(c => c.classList.add('hidden'));
        btn.classList.add('bg-white/20');
        document.getElementById(btn.dataset.tab).classList.remove('hidden');
      });
    });

    // Testimonial carousel
    const testimonialTrack = document.getElementById('testimonialTrack');
    const testimonialDots = [document.getElementById('testimonialDot0'), document.getElementById('testimonialDot1')];
    let currentTestimonial = 0;
    function showTestimonial(index) {
      testimonialTrack.style.transform = `translateX(-${index * 100}%)`;
      testimonialDots.forEach((dot, i) => dot.style.opacity = i === index ? '1' : '0.5');
    }
    testimonialDots.forEach((dot, index) => dot.addEventListener('click', () => { currentTestimonial = index; showTestimonial(currentTestimonial); }));
    setInterval(() => { currentTestimonial = (currentTestimonial + 1) % 2; showTestimonial(currentTestimonial); }, 5000);
  </script>
</body>
</html>