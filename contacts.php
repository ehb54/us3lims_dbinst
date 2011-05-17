<?php
/*
 * contacts.php
 *
 * A place to list contact information for this instance
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Contacts";
include 'top.php';
include 'links.php';

echo <<<HTML
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">UltraScan Software Contacts</h1>
  <!-- Place page content here -->

  <table cellspacing='0' cellpadding='10' class='style1'>

  <thead>
    <tr><th colspan='2'>UltraScan Contacts</th></tr>
  </thead>

  <tbody>
    <tr><th>Local Administrator:</th>
        <td>$admin</td></tr>
    <tr><td>Telephone:</td>
        <td>Office: $admin_phone</td></tr>
    <tr><td>E-mail:</td>
        <td><a href="$admin_email">$admin_email</a></td></tr>

    <tr><th> UltraScan Software Project:</th>
        <td> Borries Demeler </td></tr>
    <tr><td>Telephone:</td>
        <td>Office: (210) 767-3332<br/>
            Lab: (210) 567-2672<br/>
            Fax: (210) 567-6595</td></tr>
    <tr><td>E-mail:</td>
        <td><a href='mailto:demeler@biochem.uthscsa.edu'>demeler@biochem.uthscsa.edu</a>
        </td></tr>

    <tr><th>Software Installation Help:</th>
        <td>Jeremy Mann</td></tr>
    <tr><td>Telephone:</td>
        <td>Office/Lab: (210) 767-3419<br/>
            Fax: (210) 567-6595</td></tr>
    <tr><td>E-mail:</td>
        <td> <a href='mailto:jeremy@biochem.uthscsa.edu'>jeremy@biochem.uthscsa.edu</a>
        </td></tr>
  </tbody>

  </table>

</div>

HTML;

include 'bottom.php';
exit();
?>
