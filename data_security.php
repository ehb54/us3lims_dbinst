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
  <p>
This website employs the secure Hypertext Transfer Protocol (HTTPS)
for logging in and most communication while you're logged in. 
HTTPS combines HTTP, the language of the web, with the SSL protocol. This
integration allows us to enhance regular web (HTTP) communications with the
robust security features of SSL. Given that standard HTTP transmissions
occur in clear text over the internet, HTTPS becomes indispensable when
heightened security is required.
<p>
One of the key features of HTTPS is its ability to traverse what's
commonly referred to as an encrypted tunnel. This encapsulates all
transmitted content in both directions, ensuring that your login
credentials, data, and analysis parameters traverse the internet securely.
<p>
HTTPS relies on certificates to establish these secure connections. Your
browser manages this process, making it seamless once configured
correctly. For private installations behind a firewall, and servers without
a fully qualified domain name, we use self-signed certificates that are 
not available in the database of a certificate authority that validates
domain names. Consequently, Firefox or Chrome will (incorrectly) complain that the 
connection is not secure. Please accept the certificate anyway, and 
Firefox will remember your choice. Regardless of your browser choice,
once you enable your browser to recognize our website's self-signed certificates, 
seamless access to its secure features are guaranteed.

  <p>If you have any questions or problems with our certificate authority, please
     contact us. Thanks!</p>

  <p><a href='mailto:demeler@gmail.com'>Borries Demeler, Ph.D.</a><br/>
  Professor<br/> 
  UltraScan Project Director</p>

</div>

<?php
include 'footer.php';
exit();
?>
