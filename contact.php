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
   <?php include("header2.php");?>
   <!-- Add your site or application content here -->
   <main>
      <!-- page title area  -->
      <section class="page-title-area breadcrumb-spacing" data-background="assets/img/breadcrumb/breadcrumb-bg.png">
         <div class="container">
            <div class="row justify-content-center">
               <div class="col-xxl-9">
                  <div class="page-title-wrapper text-center">
                     <h3 class="page-title mb-25">Contact</h3>
                     <div class="breadcrumb-menu">
                        <nav aria-label="Breadcrumbs" class="breadcrumb-trail breadcrumbs">
                           <ul class="trail-items">
                              <li class="trail-item trail-begin"><a href="index.html"><span>Home</span></a></li>
                              <li class="trail-item trail-end"><span>Contact</span></li>
                           </ul>
                        </nav>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- page title area end -->
      <!-- contact area  -->
      <section class="contact-area contact--area pt-120 pb-110 wow fadeInUp" data-wow-duration="1.5s"
         data-wow-delay=".3s">
         <div class="container">
            <div class="row">




               <div class="col-xxl-5 col-xl-6 col-lg-5">
                  <div class="contact--wrapper mb-60">
                     <div class="section__title mb-45">
                        <span class="sub-title">contact with us</span>
                        <h2 class="title">We will be in touch shortly</h2>
                     </div>
                     <div class="contact-info mr-20">
                        <div class="single-contact-info d-flex align-items-center">
                           <div class="contact-info-icon">
                              <a href="#"><i class="flaticon-telephone-call"></i></a>
                           </div>
                           <div class="contact-info-text">
                              <span>Call us now</span>
                              <h5><a href="tel:+1(303) 879 2122">+1(303) 879 2122</a></h5>
                           </div>
                        </div>
                        <div class="single-contact-info d-flex align-items-center">
                           <div class="contact-info-icon">
                              <a href="#"><i class="flaticon-envelope"></i></a>
                           </div>
                           <div class="contact-info-text">
                              <span>send email</span>
                              <h5><a href="mailto:info@rmjtransportllc.moversloader.com">info@rmjtransportllc.com</a> </h5>
                           </div>
                        </div>
                        <div class="single-contact-info d-flex align-items-center">
                           <div class="contact-info-icon">
                              <a href="#"><i class="flaticon-pin"></i></a>
                           </div>
                           <div class="contact-info-text">
                              <span>visit office</span>
                              <h5><a
                                    href="#">S100W31420 COUNTY HWY LO
Mukwonago, WI 53149</a></h5>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>




<!--contact us query-->
    <?php

    if(isset($_REQUEST['submit']))
    {
        $fname      = $_REQUEST['f_name'];
        $lname      = $_REQUEST['l_name'];
        $email      = $_REQUEST['email'];
        $mob_no     = $_REQUEST['mob_no'];
        $mesg       = $_REQUEST['msg'];
        $status = 0;
        
        $qry = "INSERT INTO contact(id,f_name,l_name,email,mob_no,msg,status)VALUES
        (NULL,'$fname','$lname','$email','$mob_no','$mesg','$status')";
        mysqli_query($conn, $qry);

        ?>
        <script type="text/javascript">
        window.location.assign("contact.php?msg3");
        </script>
        <?php       
    }
    
?>
               <div class="col-xxl-7 col-xl-6 col-lg-7">
                      <?php 
            if(isset($_GET['msg3']))
            {   
            ?>
            <div class=" col-md-12 align-items-center " >
            <p style="color:#03228f;">
               Your message has been submitted succesfully!
            </p>
             </div>
              <?php 
            }
            ?>
                  <div class="contact-form mb-60">
                     <form method="post" name="n1">
                        <div class="row">
                           <div class="col-xxl-6 col-xl-6 col-lg-6">
                              <div class="single-input-field">
                                 <input name="f_name" type="text" placeholder="Enter Your First Name" required>
                                 <i class="fas fa-user"></i>
                              </div>
                           </div>
                            <div class="col-xxl-6 col-xl-6 col-lg-6">
                              <div class="single-input-field">
                                 <input name="l_name" type="text" placeholder="Enter Your Last Name" required>
                                 <i class="fas fa-user"></i>
                              </div>
                           </div>
                           <div class="col-xxl-6 col-xl-6 col-lg-6">
                              <div class="single-input-field">
                                 <input name="email" type="email" placeholder="Enter Your Email Adress" required>
                                 <i class="fas fa-envelope"></i>
                              </div>
                           </div>
                           <div class="col-xxl-6 col-xl-6 col-lg-6">
                              <div class="single-input-field">
                                 <input name="mob_no" type="text" placeholder="Enter Your Mobile No" required>
                                 <i class="fas fa-phone-alt"></i>
                              </div>
                           </div>
                           <div class="col-xxl-12 col-xl-12 col-lg-12">
                              <div class="single-input-field textarea">
                                 <textarea rows="10" cols="10" placeholder="Write Your Massage Here.."
                                  name="msg" required>   
                                 </textarea>
                                 <i class="fas fa-edit"></i>
                              </div>
                           </div>

                           <div class="col-xxl-12 col-xl-12">
                              <button type="submit" class="fill-btn clip-btn" name="submit">Send a message</button>
                           </div>

                        </div>
                     </form>
                     
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- contact area end -->
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


<!-- Mirrored from codeskdhaka.com/html/delport-prv/delport/contact.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 20 Nov 2023 22:19:45 GMT -->
</html>