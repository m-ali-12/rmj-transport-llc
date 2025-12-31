<?php 
include("connection.php");
?>
<!doctype html>
<html class="no-js" lang="zxx">

<head>
   <meta charset="utf-8">
   <meta http-equiv="x-ua-compatible" content="ie=edge">
   <title>RMJ Transport LLC</title>
   <meta name="description" content="">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <!-- Place favicon.ico in the root directory -->
   <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
   <!-- CSS here -->
   <link rel="stylesheet" href="assets/css/preloader.css">
   <link rel="stylesheet" href="assets/css/bootstrap.min.css">
   <link rel="stylesheet" href="assets/css/meanmenu.css">
   <link rel="stylesheet" href="assets/css/animate.min.css">
   <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
   <link rel="stylesheet" href="assets/css/swiper-bundle.css">
   <link rel="stylesheet" href="assets/css/backToTop.css">
   <link rel="stylesheet" href="assets/css/magnific-popup.css">
   <link rel="stylesheet" href="assets/css/ui-range-slider.css">
   <link rel="stylesheet" href="assets/css/nice-select.css">
   <link rel="stylesheet" href="assets/css/fontAwesome5Pro.css">
   <link rel="stylesheet" href="assets/css/flaticon.css">
   <link rel="stylesheet" href="assets/css/default.css">
   <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
   <!--[if lte IE 9]>
      <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
      <![endif]-->
      
   <!-- Preloader start -->
 
   <!-- Preloader end -->

   <!-- header area start  -->
     <?php include("header.php");?>

   <!-- Add your site or application content here -->
   <main>
      <!-- banner area start  -->
      <?php include("home_banner.php");?>
      <!-- banner area end  -->

      <!-- Services Area Start Here  -->
     <?php include("why_choose_us.php");?>
      <!-- Services Area End Here  -->

      <!-- Help CTA Area Start Here  -->
      <section class="help__cta overlay bg-css overlay-red pt-50 pb-20" data-background="assets/img/cta/help-cta-bg.png">
         <div class="container">
            <div class="row align-items-center wow fadeInUp" data-wow-duration="1.5s"
            data-wow-delay=".3s">
               <div class="col-md-8">
                  <div class="help__cta-title mb-30">
                     <h2>New to RMJ Transport LLC service? need help?</h2>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="help__cta-btn text-lg-end mb-30">
                     <a class="skew-btn" href="team_page.php">Our Team</a>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- Help CTA Area End Here  -->



      <!-- Brand Area Start Here  -->
      <?php //include("home_brands_section.php");?>
      <!-- Brand Area End Here  -->

      <!-- Gallery Section Start -->
      <?php include("recent_work.php");?>
       <!-- Gallery Section End  -->

      <!-- Funfact area start  -->
       <?php include("funfact_area.php");?>
      <!-- Funfact area end  -->

      <!-- Accordion And Testimonial Area Start Here  -->
       <?php include("testimonals.php");?>
      <!-- Accordion And Testimonial Area End Here  -->

      <!-- Blog Area Start Here  -->
    
      <!-- Blog Area End Here  -->

   </main>
   <!-- footer area start  -->

   <?php include("footer.php");?>
   <!-- footer area end  -->

   <!-- back to top start -->
   <div class="progress-wrap">
      <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
         <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
      </svg>
   </div>
   <!-- back to top end -->
   <!-- JS here -->
   <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
   <script src="assets/js/vendor/waypoints.min.js"></script>
   <script src="assets/js/bootstrap.bundle.min.js"></script>
   <script src="assets/js/meanmenu.js"></script>
   <script src="assets/js/swiper-bundle.min.js"></script>
   <script src="assets/js/owl.carousel.min.js"></script>
   <script src="assets/js/magnific-popup.min.js"></script>
   <script src="assets/js/parallax.min.js"></script>
   <script src="assets/js/backToTop.js"></script>
   <script src="assets/js/jquery-ui-slider-range.js"></script>
   <script src="assets/js/nice-select.min.js"></script>
   <script src="assets/js/counterup.min.js"></script>
   <script src="assets/js/ajax-form.js"></script>
   <script src="assets/js/wow.min.js"></script>
   <script src="assets/js/isotope.pkgd.min.js"></script>
   <script src="assets/js/imagesloaded.pkgd.min.js"></script>
   <script src="assets/js/rangeslider-js.min.js"></script>
   <script src="assets/js/main.js"></script>
</body>

</html>