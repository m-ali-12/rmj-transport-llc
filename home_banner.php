<div class="banner-area banner-area2">
         <div class="swiper-container">
            <div class="swiper-wrapper">
               <div class="swiper-slide">
                  <div class="single-banner single-banner-2 single-banner-responsive banner-970">
                     <?php                                      
                     $qryp = "SELECT * FROM home_banner";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        {    
                        ?> 
                     <div class="banner-bg banner-bg2" data-background="assets/img/slider/<?php echo $rowp['img']; ?>">
                     </div>

                     <div class="container pos-rel">
                        <div class="row align-items-center justify-content-center">
                           <div class="col-lg-8">
                              <div class="banner-content banner-content2 mx-auto text-center banner-content2-1 pt-155">
                                 <h1 class="banner-title" data-animation="fadeInUp" data-delay=".5s">
                                    <?php echo $rowp['title']; ?>
                                 </h1>
                                 <div class="m-auto bounce">
                                    <a class="btn-round" href="#services__area-2"><i
                                          class="fal fa-long-arrow-down"></i></a>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>

                   <?php } ?>
                  </div>
               </div>
            </div>
         </div>
      </div>