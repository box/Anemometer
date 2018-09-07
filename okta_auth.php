<?php
/* -*- coding: utf-8 -*-
 * Copyright 2015 Okta, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require('simplesamlphp/lib/_autoload.php');
session_start();

$bootstrap_cdn_css_url = '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/css/bootstrap.min.css';
$bootstrap_cdn_js_url  = '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js';
$jquery_cdn_url        = '//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.2/jquery.min.js';

$title = 'HelloSlow';
$user_session_key = 'user_session';
$saml_sso = 'saml_sso';

// If the user is logged in and requesting a logout.
if (isset($_SESSION[$user_session_key]) && isset($_REQUEST['logout'])) {
    $sp = $_SESSION[$user_session_key]['sp'];
    unset($_SESSION[$user_session_key]);
    $as = new SimpleSAML\Auth\Simple($sp);
    $as->logout(["ReturnTo" => $_SERVER['PHP_SELF']]);
}

// If the user is logging in.
if (isset($_REQUEST[$saml_sso])) {
    $sp = $_REQUEST[$saml_sso];
    $as = new SimpleSAML\Auth\Simple($sp);
    $as->requireAuth();
    $user = array(
        'sp'         => $sp,
        'authed'     => $as->isAuthenticated(),
        'idp'        => $as->getAuthData('saml:sp:IdP'),
        'nameId'     => $as->getAuthData('saml:sp:NameID')['Value'],
        'attributes' => $as->getAttributes(),
    );

    error_log("okta-stat-authed: " . $as->isAuthenticated());
    error_log("okta-stat-idp: " . $as->getAuthData('saml:sp:IdP'));
    error_log("okta-stat-nameId: " . $as->getAuthData('saml:sp:NameID')['Value']);
    error_log("okta-stat-atts: " . $as->getAttributes());

    $_SESSION[$user_session_key] = $user;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="<?= $bootstrap_cdn_css_url ?>" rel="stylesheet" media="screen">
</head>
<body style="padding-top: 60px">
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <!-- this is what makes the "hamburger" icon -->
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/"><?= $title ?></a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <?php if(isset($_SESSION[$user_session_key])) { ?>
                    <li><a href="?logout=true">Logout</a></li>
                <?php } ?>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>
<div class="container">
    <?php if(isset($_SESSION[$user_session_key])) { ?>
        <h1>Logged in</h1>
        <p class="lead">Contents of the most recent SAML assertion:</p>
        <div class="col-md-8">
            <table class="table">
                <?php foreach($_SESSION[$user_session_key]['attributes'] as $key => $value) { ?>
                    <tr>
                        <td><?= $key ?></td>
                        <td><?= $value[0] ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <?php
    } else {
        $sources = SimpleSAML\Auth\Source::getSources();
        ?>
        <p class="lead">Select the IdP you want to use to authenticate:</p>
        <ol>
            <?php foreach($sources as $source) { ?>
                <li><a href="?<?= $saml_sso ?>=<?= $source ?>"><?= $source ?></a></li>
            <?php } ?>
        </ol>
    <?php } ?>
</div>
<script src="<?= $bootstrap_cdn_js_url ?>"></script>
<script src="<?= $jquery_cdn_url ?>"></script>
</body>
</html>
