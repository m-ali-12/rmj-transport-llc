<section class="dp-funfact-area pt-120 pb-90">
         <div class="container">
            <div class="dp-funfactor-grid wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">

                <?php                                      
                     $qryp = "SELECT * FROM home_funfact_area";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        { 
                        ?> 
               <div class="dp-funfact-wrapper mb-30">
                     <div class="dp-funfact-icon">
                        <i class="<?php echo $rowp['icon']; ?>"></i>
                     </div>
                     <div class="dp-funfact-content">
                        <h3 class="counter"><?php echo $rowp['number']; ?></h3>
                        <p><?php echo $rowp['des']; ?></p>
                     </div>
               </div>  
            <?php } ?>
              

            </div>
         </div>
      </section>