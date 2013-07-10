<?php
/*
 * data_security.php
 *
 * A brief description of why we use https, and a link for the root certificate
 *
 */
include 'checkinstance.php';

include 'config.php';

// Start displaying page
$page_title = "Data Security";
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Data Security</h1>
  <!-- Place page content here -->
  <p>This website uses the secure HTTP protocol (HTTPS) for logging in and for
     most communication while you are logged in. What is HTTPS and why do we 
     need it? Basically HTTPS is what we get when we layer HTTP, the language 
     of the web, on top of the SSL protocol. By putting the SSL protocol into 
     the mix we can add the security capabilities of SSL to regular web (HTTP) 
     communications. Since regular HTTP communications take place over the 
     internet connection in clear text, HTTPS is widely used on the web when 
     extra security is needed. </p>

  <p>Because of this arrangement, HTTPS can communicate over what is often
     called an <em>encrypted tunnel.</em> This includes the entire content
     of what is sent in both directions. In this way you can be sure that
     your login information, as well as any data and analysis parameters,
     are being sent over the internet securely.</p>

  <p>HTTPS uses <em>certificates</em> to establish the secure connection. This
     is handled by your browser for the most part, so it is transparent
     to you once it is set up correctly. On this website these certificates
     are based on <a href='ssl/cacert.crt'>our certificate authority</a>.
     Before logging in, if you have not done so already, please click on this 
     link to accept our certificate authority. If you are using Firefox be sure
     to check the box to trust this certficate authority to identify websites
     in the dialog that pops up and click ok. If you are using Internet Explorer,
     click the <em>Open</em> button to open the certificate file, and then in 
     the dialog that pops up click on the button marked <em>Install certificate...</em>.
     Then, in the Certificate Import Wizard, click the radio button that says
     <em>Automatically select the certificate store based on the type of certificate</em>.
     Finally, in the <em>Security Warning</em> dialog box confirming that you want
     to install the certificate authority (CA) representing bcf.uthscsa.edu, click
     <em>Yes</em>. Whichever browser you use, accepting our certificate authority
     will help your browser identify our website and make it easier to use the 
     secure parts of it.</p>

  <p>If you have any questions or problems with our certificate authority, please
     contact us. Thanks!</p>

  <p><a href='mailto:demeler@biochem.uthscsa.edu'>Borries Demeler, Ph.D.</a><br/>
  Associate Professor<br/> 
  UltraScan Project Director</p>

</div>

<?php
include 'footer.php';
exit();
?>
