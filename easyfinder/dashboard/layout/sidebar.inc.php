 <!--**********************************
            Sidebar start
        ***********************************-->
 <div class="no-print deznav">
     <div class="deznav-scroll">
         <ul class="metismenu" id="menu">

             <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-networking"></i>
                     <span class="nav-text">Dashboard</span>
                 </a>
             </li>
             <?php if ($links = $site_settings->url_link($Auth->admin_role)) {

                    // Define the specific links you want to prioritize -- for multiple sorting
                    $specificLinks = ['verifications', 'mobile-topup'];

                    // Extract the specific links in the defined order
                    $prioritizedLinks = array_filter($links, fn($link) => in_array($link->link, $specificLinks));

                    // Sort the prioritized links to match the order in $specificLinks
                    usort($prioritizedLinks, function ($a, $b) use ($specificLinks) {
                        return array_search($a->link, $specificLinks) - array_search($b->link, $specificLinks);
                    });

                    // Get the remaining links
                    $remainingLinks = array_filter($links, fn($link) => !in_array($link->link, $specificLinks));

                    // Combine prioritized links with the remaining links
                    $links = array_merge($prioritizedLinks, $remainingLinks);

                    foreach ($links as $link) {
                        if ($link->has_sub == 0) { ?>

                         <li><a href="<?= $link->link ?>" class="ai-icon" aria-expanded="false">
                                 <i class="<?= $link->link_icon ?>"></i>
                                 <span class="nav-text"><?= $link->link_name ?></span>
                             </a>
                         </li>

                     <?php } else { ?>

                         <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                                 <i class="<?= $link->link_icon ?>"></i>
                                 <span class="nav-text"><?= $link->link_name ?></span>
                             </a>
                             <ul aria-expanded="false">
                                 <?php if (
                                        $sub_links = $site_settings->sub_url_link(
                                            $link->id,
                                            $Auth->admin_role
                                        )
                                    ) {
                                        foreach ($sub_links as $sub_link) { ?>
                                         <li><a href="../<?= $sub_link->sub_link ?>"><?= $sub_link->sub_link_name ?></a></li>
                                 <?php }
                                    } ?>
                             </ul>
                         </li>

             <?php }
                    }
                } ?>

             <!-- ── My Notifications (all users) ─────────────────────────── -->
             <li>
                 <a href="<?php echo SITE_URL . 'easyfinder/dashboard/my-notifications.php' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-notification"></i>
                     <span class="nav-text">My Notifications</span>
                 </a>
             </li>

             <!-- ── Referral (all users) ───────────────────────────────────── -->
             <li>
                 <a href="<?php echo SITE_URL . 'easyfinder/dashboard/referral.php' ?>" class="ai-icon" aria-expanded="false">
                     <i class="flaticon-381-diploma-1"></i>
                     <span class="nav-text">Referral</span>
                 </a>
             </li>

             <!-- ── Notification Management (admin only) ──────────────────── -->
             <?php
             $role = (string)($Auth->admin_role ?? '');
             $isSuperAdmin = !empty($Auth->super_admin) && $Auth->super_admin == 1;
             $isAdmin = $isSuperAdmin || strpos($role, '1') !== false || strpos($role, '2') !== false || strpos($role, '3') !== false;
             if ($isAdmin): ?>
             <li>
                 <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                     <i class="flaticon-381-television"></i>
                     <span class="nav-text">Notification Mgmt</span>
                 </a>
                 <ul aria-expanded="false">
                     <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/admin-notifications.php' ?>">
                         All Notifications
                     </a></li>
                     <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/admin-notification-create.php' ?>">
                         Send Notification
                     </a></li>
                     <li><a href="<?php echo SITE_URL . 'easyfinder/dashboard/admin-notification-settings.php' ?>">
                         Settings
                     </a></li>
                 </ul>
             </li>
             <?php endif; ?>

         </ul>

         <div class="add-menu-sidebar">
             <img src="images/icon1.png" alt="" />
             <p>Get Your Own Bill Payment Website</p>
             <a href="javascript:void(0);" class="btn btn-primary btn-block light">+ Create Now</a>
         </div>
         <div class="copyright">
             <p><strong>Azzeetech IT</strong> © 2021 All Rights Reserved</p>
         </div>
     </div>
 </div>
 <!--**********************************
            Sidebar end
        ***********************************-->
