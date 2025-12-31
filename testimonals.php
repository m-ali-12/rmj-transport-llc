<section class="acc-testi grey-bg-3 pt-120 pb-35 wow fadeInUp" data-wow-duration="1.5s" data-wow-delay=".3s">
         <div class="container">
            <div class="row align-items-center">

               <div class="col-xl-6 col-lg-6">
                  <div class="accordion__wrapper accordion__wrapper-1 mb-85 mr-40">
                     <div class="accordion" id="accordionExample">

                           <?php                                      
                     $qryp = "SELECT * FROM faq";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        { 
                        ?>  

                        <div class="accordion-item">
                           <h2 class="accordion-header" id="<?php echo $rowp['qus_no']; ?>">
                              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                 data-bs-target="<?php echo $rowp['qus_collapse1']; ?>" aria-expanded="true" aria-controls="<?php echo $rowp['qus_collapse2']; ?>">
                                 <?php echo $rowp['qus']; ?>
                              </button>
                           </h2>

                           <div id="<?php echo $rowp['qus_collapse2']; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $rowp['qus_no']; ?>"
                              data-bs-parent="#accordionExample">
                              <div class="accordion-body">
                                 <p><?php echo $rowp['ans']; ?>
                                 </p>
                              </div>
                           </div>
                        </div>
                     <?php } ?>

                     </div>
                  </div>
               </div>




               <div class="col-xl-6 col-lg-6">
                   <?php                                      
                     $qryp = "SELECT * FROM client_testimonals";
                     $resp = mysqli_query($conn, $qryp);
                     while($rowp = mysqli_fetch_array($resp))
                        { 
                           ?>
                  <div class="testimonial-two mb-85">
                     <div class="testimonial__item p-relative mb-60">

                        <div class="testimonial__item-img f-left">
                           <img src="assets/img/testimonial/<?php echo $rowp['img']; ?>" alt="Testimonial">
                        </div>

                        <div class="testimonial__item-content white-bg fix">
                           <p>“<?php echo $rowp['des']; ?> ”
                           </p>
                           <div class="testimonial__item-bottom">
                              <div class="testimonial__item-auth">
                                 <h4><?php echo $rowp['name']; ?></h4>
                                 <h6><?php echo $rowp['post']; ?></h6>
                              </div>
                              
                           </div>
                        </div>

                     </div>


                  </div>
               <?php } ?>
               </div>
            </div>
         </div>
      </section>