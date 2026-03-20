<?php
/*
 * data_security.php
 *
 * A brief description of why we use https and a link for the root certificate
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

        <h2>HTTPS &amp; Encrypted Transport</h2>

        <p>This website uses <strong>HTTPS</strong> for all login sessions and
            authenticated communications. HTTPS layers the <strong>TLS (Transport
                Layer Security)</strong> protocol on top of standard web (HTTP) traffic,
            creating an encrypted tunnel for everything transmitted between your
            browser and our servers. This ensures that your login credentials,
            analysis parameters, and experimental data cannot be read or tampered
            with in transit, even on shared or public networks.</p>

        <h2>Two Environments, Two Certificate Types</h2>

        <p>UltraScan operates two distinct environments, each using a different
            type of TLS certificate:</p>

        <ul>
            <li><strong>Public-facing site</strong> &mdash; Uses a certificate issued
                by a trusted public certificate authority (CA). Your browser will
                recognize and trust it automatically with no additional steps
                required.</li>

            <li><strong>Private LIMS</strong> &mdash; Uses a self-signed certificate
                within the internal LIMS environment. Self-signed certificates provide
                the same encryption strength as CA-issued certificates; the only
                difference is that they are not vouched for by a public CA. Because of
                this, your browser will display a security warning the first time you
                connect. This is expected &mdash; see the instructions below.</li>
        </ul>

        <h2>Accessing a Private LIMS</h2>

        <p>When connecting to the private LIMS for the first time your browser will
            show a warning such as <em>&ldquo;Your connection is not private&rdquo;</em>
            or <em>&ldquo;Potential security risk ahead.&rdquo;</em> This is normal
            for self-signed certificates in a controlled internal environment. Use
            the steps below for your browser to proceed:</p>

        <p><strong>Chrome / Edge</strong></p>
        <ol>
            <li>Click <em>Advanced</em> on the warning page.</li>
            <li>Click <em>Proceed to [hostname] (unsafe)</em>. The label sounds alarming
                but within the LIMS network this is the expected and correct path.</li>
        </ol>

        <p><strong>Firefox</strong></p>
        <ol>
            <li>Click <em>Advanced&hellip;</em> on the warning page.</li>
            <li>Click <em>Accept the Risk and Continue</em>. Firefox will remember the
                exception for subsequent sessions.</li>
        </ol>

        <p><strong>Safari</strong></p>
        <ol>
            <li>Click <em>Show Details</em> on the warning page.</li>
            <li>Click <em>visit this website</em> and enter your macOS password if
                prompted to confirm the trust decision.</li>
        </ol>

        <h2>Security Best Practices</h2>

        <p>Even with HTTPS in place, a few habits help keep your account and data
            secure:</p>

        <ul>
            <li>Always verify the address bar shows <strong>https://</strong> and the
                correct hostname before entering your credentials.</li>
            <li>Log out when you are finished, especially on shared workstations.</li>
            <li>A certificate warning on the <em>public-facing site</em> would be
                unexpected. If you see one there, please contact us before
                proceeding.</li>
        </ul>

    </div>

<?php
include 'footer.php';
exit();
?>