<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2014 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// Sets the user's consent to the terms of use.
// Logs user in by sending a cookie.

require_once("../inc/boinc_db.inc");
require_once("../inc/util.inc");
require_once("../inc/user.inc");
require_once("../inc/consent.inc");

// Get the next url from POST
$next_url = post_str("next_url", true);
$next_url = urldecode($next_url);
$next_url = sanitize_local_url($next_url);
if (strlen($next_url) == 0) {
    $next_url = USER_HOME;
}

// Check opt-in checkbox
$optin = post_str("terms_of_use_optin_consent", true);
if (!$optin) {
    error_page(tra("You have not opted in to our terms of use. You may not continue until you do so."));
}

// Obtain session information
session_start();
$user = $_SESSION['user'];
$authenticator = $user->authenticator;
$perm = $_SESSION['perm'];

// Set consent in database
$consent_result = BoincConsent::lookup("userid=$user->id AND consent_id=1");
if ($consent_result) {
    $cquery = "consent_flag=1, consent_not_required=0";
    $cquery .= " WHERE userid = $user->id AND consent_id = 1";
    $rc1 = $consent_result->update($cquery);
    if (!$rc1) {
        error_page("Database error when attempting to UPDATE table consent with ID=$user->id. Please contact site administrators.");
    }
}
else {
    $rc2 = consent_to_a_policy($user, 1, 1, 0, 'Webreg', time());
    if (!$rc2) {
        error_page("Database error when attempting to INSERT into table consent with ID=$user->id. Please contact site administrators.");
    }
}

// Log-in user
send_cookie('auth', $authenticator, $perm);
session_unset();
session_destroy();

// Send user to next_url
Header("Location: ".url_base()."$next_url");
?>
