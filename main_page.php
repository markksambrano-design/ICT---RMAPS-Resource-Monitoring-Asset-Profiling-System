<?php
include "config.php";
?>
<!DOCTYPE html> 
 <html lang="en"> 
 <head> 
   <meta charset="UTF-8"> 
   <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"> 
   <title>ICT-RMAPS | ICT Resources Monitoring and Asset Profiling System</title> 
   <link rel="icon" type="image/png" href="assest/images/logo3.png">
   <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet"> 
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
   <style> 
     * { 
       margin: 0; 
       padding: 0; 
       box-sizing: border-box; 
     } 
  
     body { 
       font-family: 'Inter', sans-serif; 
       min-height: 100vh; 
       display: flex; 
       flex-direction: column;
       position: relative; 
       background: #0a1a2f; Fatal error: Uncaught mysqli_sql_exception: Unknown database 'ict_mis' in D:\ictmis\config\db.php:17 Stack trace: #0 D:\ictmis\config\db.php(17): mysqli_select_db(Object(mysqli), 'ict_mis') #1 D:\ictmis\config.php(17): include('D:\\ictmis\\confi...') #2 D:\ictmis\main_page.php(2): include('D:\\ictmis\\confi...') #3 {main} thrown in D:\ictmis\config\db.php on line 17
       overflow-x: hidden;
     } 
  
     /* Dark Blue Background Image */ 
     body::before { 
       content: ''; 
       position: fixed; 
       top: 0; 
       left: 0; 
       right: 0; 
       bottom: 0; 
       background-image: url('assest/images/mainpage.png'); 
       background-size: cover; 
       background-position: center; 
       background-repeat: no-repeat; 
       filter: brightness(0.4) grayscale(0.5) contrast(1.1) saturate(0.6); 
       z-index: 0; 
     } 
  
     /* Dark Blue overlay */ 
     body::after { 
       content: ''; 
       position: fixed; 
       top: 0; 
       left: 0; 
       right: 0; 
       bottom: 0; 
       background: linear-gradient(135deg, rgba(20, 25, 35, 0.8) 0%, rgba(10, 15, 20, 0.9) 100%); 
       z-index: 0; 
     } 
  
     .main-container { 
       width: 100%; 
       max-width: 1400px; 
       margin: 0 auto; 
       padding: 80px 100px; 
       position: relative; 
       z-index: 2; 
       flex: 1;
       display: flex;
       flex-direction: column;
       min-height: calc(100vh - 40px);
     } 
  
     /* Header/Navigation */ 
     .navbar { 
       display: flex; 
       justify-content: space-between; 
       align-items: center; 
       margin-bottom: 80px; 
       animation: fadeInDown 0.8s ease-out; 
     } 
  
     .logo h1 { 
       font-size: 2rem; 
       font-weight: 800; 
       background: linear-gradient(135deg, #ffffff 0%, #6c9ebf 100%); 
       -webkit-background-clip: text; 
       background-clip: text; 
       color: transparent; 
       letter-spacing: -0.5px; 
     } 
     .logo p { 
       font-size: 0.8rem; 
       color: #9bb7d4; 
       letter-spacing: 0.5px; 
       margin-top: 4px; 
     } 
  
     .nav-links { 
       display: flex; 
       gap: 16px; 
     } 
     .nav-links a { 
       text-decoration: none; 
       padding: 12px 32px; 
       border-radius: 50px; 
       font-weight: 600; 
       transition: all 0.3s ease; 
       font-size: 1rem; 
       backdrop-filter: blur(10px); 
     } 
     .nav-links a:first-child { 
       background: rgba(70, 130, 200, 0.2); 
       border: 1px solid rgba(100, 150, 220, 0.5); 
       color: #e0f0ff; 
     } 
     .nav-links a:first-child:hover { 
       background: rgba(80, 140, 210, 0.35); 
       border-color: #5a9eff; 
       transform: translateY(-2px); 
     } 
     .nav-links a:last-child { 
       background: linear-gradient(135deg, #2c5a8c, #1a3f66); 
       color: white; 
       box-shadow: 0 4px 12px rgba(0, 40, 80, 0.2); 
       border: none; 
     } 
     .nav-links a:last-child:hover { 
       transform: translateY(-2px); 
       box-shadow: 0 6px 18px rgba(0, 60, 100, 0.3); 
       background: linear-gradient(135deg, #3a6a9c, #2a4f76); 
     } 
  
     /* Hero/Main Content */ 
     .hero { 
       display: flex; 
       align-items: center; 
       justify-content: space-between; 
       gap: 80px; 
       flex: 1;
       padding: 40px 0;
       animation: fadeInUp 0.8s ease-out 0.2s both; 
     } 
  
     .hero-content { 
       flex: 1; 
     } 
  
     .hero-badge { 
       background: rgba(50, 100, 150, 0.4); 
       backdrop-filter: blur(10px); 
       display: inline-block; 
       padding: 8px 20px; 
       border-radius: 40px; 
       font-size: 0.8rem; 
       font-weight: 600; 
       color: #c8e4ff; 
       margin-bottom: 28px; 
       letter-spacing: 0.5px; 
       border: 1px solid rgba(100, 150, 220, 0.3); 
     } 
  
     .hero-content h1 { 
       font-size: clamp(2rem, 5vw, 3.8rem); 
       font-weight: 800; 
       line-height: 1.2; 
       margin-bottom: 24px; 
       color: #ffffff; 
       text-shadow: 0 2px 5px rgba(0, 10, 20, 0.3); 
     } 
     .hero-content h1 span { 
       color: #7ab3d0; 
       border-bottom: 3px solid #4a7cac; 
       display: inline-block; 
     } 
  
     .hero-description { 
       font-size: 1.05rem; 
       color: #cce4ff; 
       line-height: 1.6; 
       margin-bottom: 32px; 
       max-width: 520px; 
       text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1); 
     } 
  
     .feature-list { 
       display: flex; 
       flex-direction: column; 
       gap: 14px; 
       margin-bottom: 40px; 
     } 
     .feature-item { 
       display: flex; 
       align-items: center; 
       gap: 12px; 
       color: #d4ecff; 
       font-size: 0.95rem; 
       backdrop-filter: blur(4px); 
       transition: transform 0.2s;
     } 
     .feature-item i { 
       color: #7ab3d0; 
       font-size: 1.1rem; 
       width: 24px; 
     } 
  
     .hero-buttons { 
       display: flex; 
       gap: 20px; 
       flex-wrap: wrap; 
       justify-content: center; 
     } 
     .btn-signin { 
       background: #ffffff; 
       color: #1a3f66; 
       padding: 14px 42px; 
       border-radius: 50px; 
       text-decoration: none; 
       font-weight: 700; 
       transition: all 0.3s ease; 
       display: inline-flex; 
       align-items: center; 
       gap: 12px; 
       box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
       font-size: 1rem; 
     } 
     .btn-signin:hover { 
       transform: translateY(-3px); 
       box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); 
       background: #f5f9ff; 
     } 
     .btn-register { 
       background: linear-gradient(135deg, #2c5a8c, #1a3f66); 
       color: white; 
       padding: 14px 42px; 
       border-radius: 50px; 
       text-decoration: none; 
       font-weight: 700; 
       transition: all 0.3s ease; 
       display: inline-flex; 
       align-items: center; 
       gap: 12px; 
       box-shadow: 0 4px 12px rgba(0, 40, 80, 0.2); 
       font-size: 1rem; 
     } 
     .btn-register:hover { 
       transform: translateY(-3px); 
       box-shadow: 0 8px 25px rgba(0, 60, 100, 0.3); 
       background: linear-gradient(135deg, #3a6a9c, #2a4f76); 
     } 
  
     .hero-image { 
       flex: 1; 
       display: flex;
       flex-direction: column;
       align-items: center;
       justify-content: center;
       gap: 24px;
       animation: fadeInRight 1s ease-out 0.4s both;
     } 
     .logo-gallery {
       display: flex;
       align-items: center;
       justify-content: center;
       gap: 25px;
       flex-wrap: wrap;
     }
     .logo-gallery img {
       width: 140px;
       height: 140px;
       object-fit: contain;
       filter: drop-shadow(0 8px 15px rgba(0, 0, 0, 0.4));
       transition: all 0.4s ease;
     }
     .logo-gallery img:hover {
       transform: translateY(-10px) scale(1.1);
       filter: drop-shadow(0 12px 25px rgba(0, 0, 0, 0.5));
     }
     .hero-image p { 
       margin-top: 10px; 
       font-size: 0.95rem; 
       color: #cce4ff; 
       font-weight: 500;
       letter-spacing: 1px;
       text-transform: uppercase;
       opacity: 0.8;
     } 
  
     /* Footer - minimal */ 
     .footer { 
       width: 100%;
       text-align: center; 
       font-size: 0.75rem; 
       color: #8aadcc; 
       z-index: 2; 
       background: rgba(0, 20, 40, 0.4); 
       padding: 15px 0; 
       backdrop-filter: blur(10px); 
       border-top: 1px solid rgba(100, 150, 220, 0.1);
       margin-top: auto;
     } 
  
     /* Animations */ 
     @keyframes fadeInDown { 
       from { 
         opacity: 0; 
         transform: translateY(-30px); 
       } 
       to { 
         opacity: 1; 
         transform: translateY(0); 
       } 
     } 
  
     @keyframes fadeInUp { 
       from { 
         opacity: 0; 
         transform: translateY(30px); 
       } 
       to { 
         opacity: 1; 
         transform: translateY(0); 
       } 
     } 
  
     @keyframes fadeInRight { 
       from { 
         opacity: 0; 
         transform: translateX(40px); 
       } 
       to { 
         opacity: 1; 
         transform: translateX(0); 
       } 
     } 
  
     /* Responsive - ensure no scroll on most screens */ 
     @media (max-width: 1024px) { 
       .hero-content h1 { 
         font-size: 3rem; 
       } 
       .main-container { 
         padding: 25px 32px; 
       } 
       .navbar { 
         margin-bottom: 50px; 
       } 
     } 
  
     @media (max-width: 900px) { 
       .hero { 
         flex-direction: column; 
         text-align: center; 
         gap: 50px; 
         padding: 40px 0;
       } 
       .hero-description { 
         margin-left: auto; 
         margin-right: auto; 
       } 
       .feature-list { 
         align-items: center; 
       } 
       .hero-buttons { 
         justify-content: center; 
       } 
       .navbar { 
         margin-bottom: 60px; 
       } 
       .main-container { 
         padding: 40px 24px; 
       } 
     } 
  
     @media (max-height: 700px) { 
       .navbar { 
         margin-bottom: 30px; 
       } 
       .feature-list { 
         margin-bottom: 25px; 
       } 
     } 
   </style> 
 </head> 
 <body> 
  
 <div class="main-container"> 
   <!-- Navigation with Sign In and Register only --> 
   <nav class="navbar"> 
     <div class="logo"> 
       <h1>ICT-RMAPS</h1> 
       <p>Resource Monitoring & Asset Profiling System</p> 
     </div> 
     <div class="nav-links"> 
       <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
         <a href="user/dashboard.php" id="dashboardNav"><i class="fas fa-tachometer-alt"></i> Dashboard</a> 
       <?php elseif (isset($_SESSION['email'])): ?>
         <a href="admin/dashboard.php" id="adminNav"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a> 
       <?php else: ?>
         <a href="user/login.php" id="signInNav"><i class="fas fa-key"></i> Sign In</a> 
       <?php endif; ?>
     </div> 
   </nav> 
  
   <!-- Main Hero Section --> 
   <div class="hero"> 
     <div class="hero-content"> 
       <div class="hero-badge"> 
         <i class="fas fa-chart-line"></i> Enterprise Asset Intelligence Platform 
       </div> 
       <h1>ICT Resources Monitoring <span>&</span> Asset Profiling</h1> 
       <p class="hero-description"> 
         Complete visibility over your ICT infrastructure. Centralized asset tracking,  
         detailed hardware profiling, and efficient resource management for every office. 
       </p> 
       <div class="feature-list"> 
         <div class="feature-item"> 
           <i class="fas fa-check-circle"></i> 
           <span>Centralized equipment inventory tracking</span> 
         </div> 
         <div class="feature-item"> 
           <i class="fas fa-check-circle"></i> 
           <span>Detailed hardware & software profiling</span> 
         </div> 
         <div class="feature-item"> 
           <i class="fas fa-check-circle"></i> 
           <span>Streamlined RMAPS form submission & reporting</span> 
         </div> 
       </div> 
       <div class="hero-buttons"> 
         <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
           <a href="user/dashboard.php" class="btn-signin" id="dashboardBtn"><i class="fas fa-tachometer-alt"></i> Go to Dashboard</a> 
         <?php elseif (isset($_SESSION['email'])): ?>
           <a href="admin/dashboard.php" class="btn-signin" id="adminBtn"><i class="fas fa-tachometer-alt"></i> Go to Admin Dashboard</a> 
         <?php else: ?>
           <a href="user/login.php" class="btn-signin" id="signInBtn"><i class="fas fa-sign-in-alt"></i> Sign In</a> 
         <?php endif; ?>
       </div> 
     </div> 
     <div class="hero-image"> 
       <div class="logo-gallery">
         <img src="assest/images/logo1.png" alt="Logo 1">
         <img src="assest/images/logo2.png" alt="Logo 2">
         <img src="assest/images/logo3.png" alt="Logo 3">
       </div>
      
     </div> 
   </div> 
 </div> 
  
 <div class="footer"> 
   <i class="fas fa-copyright"></i> 2026 ICT-RMAPS | Next-Gen ICT Asset Intelligence Platform 
 </div> 
  
 <script> 
   // Add hover effect for feature items 
   const featureItems = document.querySelectorAll('.feature-item'); 
   featureItems.forEach(item => { 
     item.addEventListener('mouseenter', () => { 
       item.style.transform = 'translateX(5px)'; 
     }); 
     item.addEventListener('mouseleave', () => { 
       item.style.transform = 'translateX(0)'; 
     }); 
   }); 

   // Keyboard shortcut: Ctrl + Shift + V to go to Admin Login
   // Keyboard shortcut: Ctrl + Shift + Alt + S to go to Secret System Settings
   document.addEventListener('keydown', function(event) {
       if (event.ctrlKey && event.shiftKey && (event.key === 'V' || event.key === 'v')) {
           event.preventDefault();
           window.location.href = 'admin/login.php';
       }
       if (event.ctrlKey && event.shiftKey && event.altKey && (event.key === 'S' || event.key === 's')) {
           event.preventDefault();
           window.location.href = 'config/s_s.php';
       }
   });
 </script> 
  
 </body> 
 </html>