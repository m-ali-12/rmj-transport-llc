<?php 
include("connection.php");
?>
<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Team Members - MJ Hauling United LLC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
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
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <?php include("header2.php");?>

    
   <!-- page title area  -->
      <section class="page-title-area breadcrumb-spacing" data-background="assets/img/breadcrumb/breadcrumb-bg.png">
         <div class="container">
            <div class="row justify-content-center">
               <div class="col-xxl-9">
                  <div class="page-title-wrapper text-center">
                     <h3 class="page-title mb-25">Our Team</h3>
                     <div class="breadcrumb-menu">
                        <nav aria-label="Breadcrumbs" class="breadcrumb-trail breadcrumbs">
                           <ul class="trail-items">
                              <li class="trail-item trail-begin"><a href="index.html"><span>Home</span></a></li>
                              <li class="trail-item trail-end"><span>Team</span></li>
                           </ul>
                        </nav>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- page title area end -->
    <main>
        <section class="py-20">
            <?php
            $team = [
                ["name" => "John Doe", "role" => "Frontend Developer"],
                ["name" => "Jane Smith", "role" => "UI/UX Designer"],
                ["name" => "Alan Walker", "role" => "Backend Engineer"],
                ["name" => "Sara Lee", "role" => "Product Manager"],
                ["name" => "Mike Ross", "role" => "DevOps Engineer"],
                ["name" => "Emily Clark", "role" => "QA Tester"],
                ["name" => "Tom Hardy", "role" => "Tech Lead"],
                ["name" => "Nina Patel", "role" => "Scrum Master"],
            ];

            foreach ($team as $index => &$member) {
                $imgNum = $index + 1;
                $member['image'] = "Team_images/Team_img{$imgNum}.jpeg";
                $grayIndexes = [1, 3, 4, 6]; // cards 2, 4, 5, 7
                $member['bg'] = in_array($index, $grayIndexes) ? 'bg-gray-300' : 'bg-white';
            }
            unset($member);
            ?>

            <div class="flex flex-wrap justify-center w-full max-w-6xl mx-auto">
                <?php foreach ($team as $member): ?>
                    <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 aspect-square <?= $member['bg'] ?> flex flex-col justify-center items-center p-4">
                        <img src="<?= htmlspecialchars($member['image']) ?>" alt="<?= htmlspecialchars($member['name']) ?>" class="rounded-full w-32 h-32 object-cover mb-3">
                        <h2 class="text-lg font-semibold text-center"><?= htmlspecialchars($member['name']) ?></h2>
                        <p class="text-sm text-center px-2"><?= htmlspecialchars($member['role']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php include("footer.php");?>
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
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