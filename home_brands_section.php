
<div class="brand white-bg two border-tb">
         <div class="container-fluid p-0 wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
               <div class="swiper-container brand-active-2">
                  <!-- Additional required wrapper -->
                  <div class="swiper-wrapper text-center">
                     <!-- Slides -->
                       <?php                                      
                     $qryp = "SELECT * FROM home_brands";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        { 
                        ?>                 
                     <div class="swiper-slide"  style="width: 296.5px; margin-right: 30px;" role="group" aria-label="5 / 11" data-swiper-slide-index="1">
                    
                           <div class="brand-items-2" >
                              <a href="#"><img class="custom-brand-img" src="assets/img/brand/<?php echo $rowp['img']; ?>" alt="Brand"></a>
                           </div>
                   
                     </div>
                     
                     <?php } ?>
                  </div>
                    
               </div>
         </div>
      </div>
      