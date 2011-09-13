<?php
/*
 * cluster_info.php
 *
 * Information about clusters that programs might want to know
 *
 */

$serviceURL = "http://gw33.quarry.iu.teragrid.org:8080/ogce-rest/job";

$clusters = array();   // Information about the clusters

$clusters[ 'bcf' ] = array 
(
  "name"       => "bcf.uthscsa.edu",
  "submittype" => "http",
  "submithost" => "http://gw33.quarry.iu.teragrid.org",
  "httpport"   => 8080,
  "sshport"    => 22,
  "workdir"    => "/ogce-rest/job" 
);

$clusters[ 'bcf-local' ] = array 
(
  "name"       => "bcf.uthscsa.edu",
  "submittype" => "local",
  "submithost" => "",
  "httpport"   => 0,
  "sshport"    => 22,
  "workdir"    => "/home/us3/work/"   // Need trailing slash
);

$clusters[ 'alamo' ] = array 
(
  "name"       => "alamo.biochemistry.uthscsa.edu",
  "submittype" => "http",
  "submithost" => "http://gw33.quarry.iu.teragrid.org",
  "httpport"   => 8080,
  "sshport"    => 22,
  "workdir"    => "/ogce-rest/job" 
);

$clusters[ 'alamo-local' ] = array 
(
  "name"       => "alamo.uthscsa.edu",
  "submittype" => "local",
  "submithost" => "",
  "httpport"   => 0,
  "sshport"    => 22,
  "workdir"    => "/home/us3/work/"   // Need trailing slash
);

$clusters[ 'ranger' ] = array 
(
  "name"       => "ranger.tacc.teragrid.org",
  "submittype" => "http",
  "submithost" => "http://gw33.quarry.iu.teragrid.org",
  "httpport"   => 8080,
  "sshport"    => 22,
  "workdir"    => "/ogce-rest/job" 
);

$clusters[ 'lonestar' ] = array 
(
  "name"       => "lonestar.tacc.teragrid.org",
  "submittype" => "http",
  "submithost" => "http://gw33.quarry.iu.teragrid.org",
  "httpport"   => 8080,
  "sshport"    => 22,
  "workdir"    => "/ogce-rest/job" 
);

