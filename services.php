<?php 
include("connection.php");
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
   <meta charset="utf-8">
   <meta http-equiv="x-ua-compatible" content="ie=edge">
   <title>MJ Hauling United LLC</title>
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
                     <h3 class="page-title mb-25">services</h3>
                     <div class="breadcrumb-menu">
                        <nav aria-label="Breadcrumbs" class="breadcrumb-trail breadcrumbs">
                           <ul class="trail-items">
                              <li class="trail-item trail-begin"><a href="index.html"><span>Home</span></a></li>
                              <li class="trail-item trail-end"><span>services</span></li>
                           </ul>
                        </nav>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- page title area end -->

      <!-- Services 3 Area Start Here  -->
      <section class="services__3 grey-bg-4 pt-120 pb-90">
         <div class="container">
            <div class="row justify-content-center wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
               <div class="col-md-8">
                  <div class="section__title mb-55 text-center">
                     <span class="sub-title">services</span>
                     <h2 class="title">what we do</h2>
                  </div>
               </div>
            </div>
            <div class="row wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".5s">

                <?php                                      
                     $qryp = "SELECT * FROM services";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        {
                        ?> 
               <div class="col-xl-4 col-md-6">
                  <div class="services__3-item mb-30">
                     <div class="services__3-item-num">
                        <h3><?php echo $rowp['number']; ?></h3>
                     </div>
                     <div class="services__3-item-icon">
                        <i class="<?php echo $rowp['icon']; ?>"></i>
                     </div>
                     <h3 class="services__3-item-title"><a href="#"><?php echo $rowp['heading']; ?></a></h3>
                     <p class="services__3-item-text" style="text-align: justify;"><?php echo $rowp['des']; ?>
                     </p>
                  </div>
               </div>
            <?php } ?>

              
             

            </div>
         </div>
      </section>
      <!-- Services 3 Area End Here  -->

      <!-- Brand Area Start Here 
      <div class="brand green-bg pt-35 pb-35">
         <div class="container">
            <div class="swiper-container brand-padd brand-active">
      
               <div class="swiper-wrapper wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
      <div class="swiper-slide">
                        <div class="brand-items">
                           <a href="#"><img src="assets/img/brand/db1.png" alt="Brand"></a>
                        </div>
                     </div>
                     <div class="swiper-slide">
                        <div class="brand-items">
                           <a href="#"><img src="assets/img/brand/db2.png" alt="Brand"></a>
                        </div>
                     </div>
                     <div class="swiper-slide">
                        <div class="brand-items">
                           <a href="#"><img src="assets/img/brand/db3.png" alt="Brand"></a>
                        </div>
                     </div>
                     <div class="swiper-slide">
                        <div class="brand-items">
                           <a href="#"><img src="assets/img/brand/db4.png" alt="Brand"></a>
                        </div>
                     </div>
                     <div class="swiper-slide">
                        <div class="brand-items">
                           <a href="#"><img src="assets/img/brand/db5.png" alt="Brand"></a>
                        </div>
                     </div>
               </div>
            </div>
         </div>
      </div>
      <!-- Brand Area End Here  -->

      <!-- Accordion Area Start Here  
      <section class="accordion__area grey-bg-3 pt-120 pb-100">
         <div class="container">
            <div class="row wow fadeInUp" data-wow-duration="1.5s"
            data-wow-delay=".3s">
               <div class="col-xl-6">
                  <div class="accordion__wrapper accordion__wrapper-1 pr-20">
                     <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingOne">
                              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                 servicing time frame
                              </button>
                           </h2>
                           <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                              data-bs-parent="#accordionExample">
                              <div class="accordion-body">
                                 <p>From finance, retail, and travel, to social media, cybersecurity, adtech,
                                    and more, market leaders are leveraging web data to maintain their transt
                                    advantage. Discover how it can work for you.
                                 </p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingTwo">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                 refund policy & pricing
                              </button>
                           </h2>
                           <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                              data-bs-parent="#accordionExample">
                              <div class="accordion-body">
                                 <p>It was popularised in the 1960s with the release of Letraset sheets containing Lorem
                                    Ipsum passages, and more recently with desktop publishing software like Aldus
                                    PageMaker including versions of Lorem Ipsum.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingThree">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                 our own products
                              </button>
                           </h2>
                           <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                              data-bs-parent="#accordionExample">
                              <div class="accordion-body">
                                 <p>The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those
                                    interested. Sections 1.10.32 and 1.10.33 from "de Finibus Bonorum et Malorum" by
                                    Cicero are also reproduced in their exact original form, accompanied by English
                                    versions from the 1914 translation by H. Rackham.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingFour">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                 troubleshooting process
                              </button>
                           </h2>
                           <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                              data-bs-parent="#accordionExample">
                              <div class="accordion-body">
                                 <p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a
                                    piece of classical Latin literature from 45 BC, making it over 2000 years old.
                                    Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked
                                    up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingFive">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                 terms & conditions
                              </button>
                           </h2>
                           <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                              data-bs-parent="#accordionExample">
                              <div class="accordion-body">
                                 <p>t is a long established fact that a reader will be distracted by the readable
                                    content of a page when looking at its layout. The point of using Lorem Ipsum is that
                                    it has a more-or-less normal distribution of letters, as opposed to using 'Content
                                    here.</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="col-xl-6">
                  <div class="accordion__wrapper accordion__wrapper-1 pr-20">
                     <div class="accordion" id="accordionExample2">
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingSix">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                 refund policy & pricing
                              </button>
                           </h2>
                           <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix"
                              data-bs-parent="#accordionExample2">
                              <div class="accordion-body">
                                 <p>It was popularised in the 1960s with the release of Letraset sheets containing Lorem
                                    Ipsum passages, and more recently with desktop publishing software like Aldus
                                    PageMaker including versions of Lorem Ipsum.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingSeven">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                 refund policy & pricing
                              </button>
                           </h2>
                           <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven"
                              data-bs-parent="#accordionExample2">
                              <div class="accordion-body">
                                 <p>It was popularised in the 1960s with the release of Letraset sheets containing Lorem
                                    Ipsum passages, and more recently with desktop publishing software like Aldus
                                    PageMaker including versions of Lorem Ipsum.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingEight">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                 our own products
                              </button>
                           </h2>
                           <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight"
                              data-bs-parent="#accordionExample2">
                              <div class="accordion-body">
                                 <p>The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those
                                    interested. Sections 1.10.32 and 1.10.33 from "de Finibus Bonorum et Malorum" by
                                    Cicero are also reproduced in their exact original form, accompanied by English
                                    versions from the 1914 translation by H. Rackham.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingNine">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                                 troubleshooting process
                              </button>
                           </h2>
                           <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine"
                              data-bs-parent="#accordionExample2">
                              <div class="accordion-body">
                                 <p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a
                                    piece of classical Latin literature from 45 BC, making it over 2000 years old.
                                    Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked
                                    up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage.</p>
                              </div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header" id="headingTen">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                 data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                                 terms & conditions
                              </button>
                           </h2>
                           <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen"
                              data-bs-parent="#accordionExample2">
                              <div class="accordion-body">
                                 <p>t is a long established fact that a reader will be distracted by the readable
                                    content of a page when looking at its layout. The point of using Lorem Ipsum is that
                                    it has a more-or-less normal distribution of letters, as opposed to using 'Content
                                    here.</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- Accordion Area End Here  -->

   </main>
   <!-- footer area start  -->
   <?php include("footer.php");?>
   <!-- footer area end  -->
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