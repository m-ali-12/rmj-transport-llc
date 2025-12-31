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
                     <h3 class="page-title mb-25">About Us</h3>
                     <div class="breadcrumb-menu">
                        <nav aria-label="Breadcrumbs" class="breadcrumb-trail breadcrumbs">
                          <ul class="trail-items">
                              <li class="trail-item trail-begin"><a href="index.html"><span>Home</span></a></li>
                              <li class="trail-item trail-end"><span>About Us</span></li>
                           </ul>
                        </nav>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- page title area end -->

      <!-- About Us 3 Area Start Here -->
      <section class="about__3 about__gray-bg p-relative pt-120 pb-60 wow fadeInUp" data-wow-duration="1.5s"
         data-wow-delay=".3s">
         <div class="container">
            <div class="row align-items-center">
                <?php                                      
                     $qryp = "SELECT * FROM about_us";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        {
                        ?> 
               <div class="col-xl-6 col-lg-6">
                  <div class="about__3-img-wrapper p-relative mb-60">
                     <div class="about__3-top w-img">
                        <img src="assets/img/<?php echo $rowp['img1']; ?>" alt="About">
                     </div>
                     <div class="about__3-main w-img">
                        <img src="assets/img/<?php echo $rowp['img2']; ?>" alt="About">
                     </div>
                     <div class="about__3-text clip-box-sm">
                        <span><i class="far fa-trophy-alt"></i></span>
                        <h4 class="about__3-title"><?php echo $rowp['experience']; ?></h4>
                     </div>
                  </div>
               </div>

               <div class="col-xl-6 col-lg-6">
                  <div class="about__3-content mb-60">
                     <div class="section__title mb-30">
                        <span class="sub-title">about us</span>
                        <h2 class="title"><?php echo $rowp['title']; ?></h2>
                     </div>
                     <div class="about__3-content-inner p-relative">
                        <div class="about__3-content-left">
                          <?php echo $rowp['des']; ?>
                          
                        </div>
                        <div class="about__3-content-right">
                           <div class="about__3-shadow">
                              <div class="about__3-content-num">
                                 <h2><?php echo $rowp['projects']; ?></h2>
                                 <h6>Car Shipped</h6>
                              </div>
                           </div>
                           <div class="about__3-shadow">
                              <div class="about__3-content-num">
                                 <h2><?php echo $rowp['Rating']; ?></h2>
                                 <h6>star ratings</h6>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

            <?php } ?>
            </div>
         </div>
      </section>
      <!-- About Us 3 Area End Here -->



    <!--  <section class="about__3 p-relative pt-120 pb-60 wow fadeInUp" data-wow-duration="1.5s"
         data-wow-delay=".3s">
         <div class="container">
            <div class="row align-items-center"> 
                <center><h2 class="mb-4" >WE MOVE CARS IN OUR TRUCKS </h2></center>
               <div class="col-xl-4 col-lg-4 mt-2 mb-2">
                  <center>
                   <img src="assets/img/move/1.jpg" style="width:100px" >
                   <h4>Box Truck</h4>
                  </center>
               </div>
               <div class="col-xl-4 col-lg-4 mt-2 mb-2">
                  <center>
                   <img src="assets/img/move/2.jpg" style="width:100px" >
                   <h4>Dry Van</h4>
                  </center>
               </div>
               <div class="col-xl-4 col-lg-4 mt-2 mb-2">
                  <center>
                   <img src="assets/img/move/4.jpg" style="width:100px" >
                   <h4>Refer</h4>
                    </center>
               </div>
               <div class="col-xl-4 col-lg-4 mt-2 mb-2">
               <center>
                   <img src="assets/img/move/5.png" style="width:100px" >
                   <h4>Power Only</h4>
               </div>
               <div class="col-xl-4 col-lg-4 mt-2 mb-2">
               <center>
                   <img src="assets/img/move/6.png" style="width:100px" >
                   <h4>Flatbed</h4>
               </div>
               <div class="col-xl-4 col-lg-4 mt-2 mb-2">
               <center>
                   <img src="assets/img/move/7.png" style="width:100px" >
                   <h4>Hotshot</h4>
               </div>

             
            </div>
         </div>
      </section>

-->
<section class="w3l-features-3">
	<!-- /features -->
		<div class="features py-5" id="services">
            <div class="container py-md-3">
			<div class="heading text-center mx-auto">
				<h3 class="head">Car Transport</h3>
				<p class="my-3 head"> Whether you're moving across the country or need to transport your vehicle for any reason, our team is here to ensure a hassle-free experience.

</p>
			  </div>
			<div class="fea-gd-vv row mt-5 pt-3">	
			   <div class="float-lt feature-gd col-lg-4 col-md-6">	
					 <div class="icon"> <span class="fa fa-truck" aria-hidden="true"></span></div>
					 <div class="icon-info">
						<h5><a>Enclosed Auto Shipping</a></h5>
						<p>We specialize in enclosed auto shipping, ensuring the safe and secure transportation of your vehicles across the USA with a team dedicated to providing a seamless experience. </p>
						
					</div>
					 
				</div>	
				<div class="float-mid feature-gd col-lg-4 col-md-6 mt-md-0 mt-5">	
					 <div class="icon"> <span class="fa fa-car" aria-hidden="true"></span></div>
					 <div class="icon-info">
						<h5><a>Door-to-Door Auto Transport</a></h5>
						<p> Our door-to-door service offers ultimate convenience, picking up and delivering your vehicle directly to your specified addresses. </p>
						
					</div>
			 </div> 
				<div class="float-rt feature-gd col-lg-4 col-md-6 mt-lg-0 mt-5">	
					 <div class="icon"> <span class="fa fa-clone" aria-hidden="true"></span></div>
					 <div class="icon-info">
						<h5><a>Van Transport</a></h5>
						<p> From single van to fleet transport, BlueSky Transportation LLC offers tailored solutions for all your needs. Experience reliable and efficient service.

 </p>
						

					</div>
			 </div>	 
			 <div class="float-lt feature-gd col-lg-4 col-md-6 mt-5">	
					 <div class="icon"> <span class="fa fa-bullseye" aria-hidden="true"></span></div>
					 <div class="icon-info">
						<h5><a>Open-Vehicle Transport</a>
						</h5>
						<p> Open-vehicle transport is an affordable and efficient way to ship your vehicle. It involves transporting your car on an open carrier, ensuring reliable delivery while keeping costs low.  </p>
						
					</div>
					 
				</div>	
				<div class="float-mid feature-gd col-lg-4 col-md-6 mt-5">	
					 <div class="icon"> <span class="fa fa-cog" aria-hidden="true"></span></div>
					 <div class="icon-info">
						<h5><a>
Luxury Vehicle Transport</a>
						</h5>
						<p> We provide premium, enclosed transport services for luxury vehicles, ensuring safe, secure, and timely delivery with the highest level of care and attention.

 </p>
						
					</div>
			 </div> 
				<div class="float-rt feature-gd col-lg-4 col-md-6 mt-5">	
					 <div class="icon"> <span class="fa fa-car" aria-hidden="true"></span></div>
					 <div class="icon-info">
						<h5><a>Car Transport</a>
						</h5>
						<p>Whether you're moving across the country or need to transport your vehicle for any reason, our team is here to ensure a hassle-free experience.  </p>
						
					</div>
			 </div>		 				 
		  </div>  
		 </div>
	   </div>
   <!-- //features -->
</section>      

    

      <!-- objective area start  -
      <section class="approach__area fix grey-bg-4">
         <?php                                      
                     $qryo = "SELECT * FROM aboutus_objmv where section = 'upper'";
                     $reso = mysqli_query($conn, $qryo);
                     while($rowo = mysqli_fetch_array($reso))
                        {
                        ?> 

         <div class="approach__img m-img">
            <img src="assets/img/approach/<?php echo $rowo['img']; ?>" alt="objective">
         </div>
         <div class="container">
            <div class="row g-0 justify-content-end">
               <div class="col-lg-6">
                  <div class="approach__content wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
                     <div class="section__title mb-35">
                        <span class="sub-title"><?php echo $rowo['title']; ?></span>
                        <h2 class="title"><?php echo $rowo['heading']; ?>
                        </h2>
                     </div>
                     <div class="approach__text">
                        <p style="text-align: justify;"><?php echo $rowo['des']; ?>
                        </p>
                        <ul>
                           <li><i class="fal fa-check-circle"></i>Commercial expertise</li>
                           <li><i class="fal fa-check-circle"></i>Logistical expertise</li>
                           <li><i class="fal fa-check-circle"></i>Sustainability goals</li>
                           <li><i class="fal fa-check-circle"></i>Cost Optimization</li>
                           <li><i class="fal fa-check-circle"></i>Reduce Transit Time</li>
                           <li><i class="fal fa-check-circle"></i>Managing Logistics</li>
                        </ul>
                     </div>
                  </div>
               </div>
            </div>
         </div>

      <?php } ?>
      </section>
      <!-- objective area end -->

      <!-- mission area start  
      <section class="mission__area p-relative fix grey-bg-4">
          <?php                                      
                     $qrym = "SELECT * FROM aboutus_objmv where section = 'middle'";
                     $resm = mysqli_query($conn, $qrym);
                     while($rowm = mysqli_fetch_array($resm))
                        {
                        ?> 

         <div class="mission__img m-img">
            <img src="assets/img/mission/<?php echo $rowm['img']; ?>" alt="mission">
         </div>

         <div class="container">
            <div class="row g-0">
               <div class="col-lg-6">
                  <div class="mission__content wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
                     <div class="section__title mb-35">
                        <span class="sub-title"><?php echo $rowm['title']; ?></span>
                        <h2 class="title"><?php echo $rowm['heading']; ?>
                        </h2>
                     </div>
                     <div class="mission__text">
                        <p style="text-align:justify;"><?php echo $rowm['des']; ?>
                        </p>
                        
                     </div>
                  </div>
               </div>
            </div>
         </div>

      <?php } ?>
      </section>
      <!-- mission area end -->

      <!-- vission area start  
      <section class="approach__area p-relative fix grey-bg-4 mb-140">

                <?php                                      
                     $qryp = "SELECT * FROM aboutus_objmv where section = 'bottom'";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        {
                        ?> 
         <div class="approach__img m-img">
            <img src="assets/img/approach/<?php echo $rowp['img']; ?>" alt="approach">
         </div>

         <div class="container">
            <div class="row g-0 justify-content-end">
               <div class="col-lg-6">
                  <div class="approach__content wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
                     <div class="section__title mb-35">
                        <span class="sub-title"><?php echo $rowp['title']; ?></span>
                        <h2 class="title"><?php echo $rowp['heading']; ?>
                        </h2>
                     </div>
                     <div class="approach__text">
                        <p style="text-align:justify;"><?php echo $rowp['des']; ?>
                        </p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      <?php }?>
      </section>
      <!-- objective area end -->

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