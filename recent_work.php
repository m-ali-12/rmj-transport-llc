<section class="dp-gallery-area grey-bg-3 pt-120 pb-120">


         <div class="container">
            <div class="row align-items-center wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
               <div class="col-xl-7 col-lg-8 col-12">
                     <div class="section__title gallery-section-title mb-55">
                        <span class="sub-title">RMJ Transport LLC</span>
                        <h2 class="title">Our Recent Work</h2>
                     </div>
               </div>
                <div class="col-xl-5 col-lg-4 col-12">
                     <div class="services-two-nav dp-gallery-nav text-end">
                        <div class="services-button-prev"><i class="fas fa-long-arrow-left"></i></div>
                        <div class="services-button-next"><i class="fas fa-long-arrow-right"></i></div>
                     </div>
               </div> 
            </div>
         </div>


         <div class="dp-gallery-active swiper-container wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
            <div class="swiper-wrapper">

                     <?php                                      
                     $qryp = "SELECT * FROM home_recent_work";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        { 
                        ?>  
             <div class="swiper-slide">
                     <div class="dp-single-gallery">
                       <<div class="dp-gallery-thumb">
                           <img class="img-fluid"  style="width:296px; height:234px;"  src="assets/img/gallery/<?php echo $rowp['slider_img']; ?>" alt="gallery-image">
                        </div>

                       
                     </div>
               </div> 
            <?php } ?>


            </div>
         </div> 


      </section>