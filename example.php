<?php
/**
 * Execute a SwissID login
 */
/**
 * Instantiate the SwissIDConnector object with the RP-specific configuration
 */
$clientID = 'YOUR_CLIENT_ID';
$clientSecret = 'YOUR_SECRET';
$redirectURL = 'YOUR REDIRECT URL';
$environment = 'INT';
$swissIDConnector = new SwissIDConnector($clientID, $clientSecret, $redirectURL, $environment);
if ($swissIDConnector->hasError()) {
    /**
     * Handle the object's error if instantiating the object failed
     */
    $error = $swissIDConnector->getError();
    ;
}

/**
 * Authenticate the end-user
 */
$scope = 'openid profile';
$qoa = null;
$qor = 'qor1';
$locale = 'en';
$state2pass = null;
$nonce = bin2hex(random_bytes(8));
$swissIDConnector->authenticate($scope, $qoa, $qor, $locale, $state2pass, $nonce);
if ($swissIDConnector->hasError()) {
    $error = $swissIDConnector->getError();
    if ($error['type'] == 'swissid') {
        switch ($error['error']) {
            case 'authentication_cancelled':
                /**
                 * Handle the end-user who cancelled the authentication
                 */
                ;
                break;
            case 'access_denied':
                /**
                 * Handle the end-user who didn't give consent
                 */
                ;
                break;
            case 'interaction_required':
                /**
                 * Handle the end-user who didn't authenticate
                 */
                ;
                break;
        }
    } elseif ($error['type'] == 'object') {
        /**
         * Handle the object's error if authentication failed
         */
        ;
    }
}

/**
 * Instantiate the SwissIDConnector object, with the existing configuration
 */
$swissIDConnector = new SwissIDConnector();
if ($swissIDConnector->hasError()) {
    /**
     * Handle the object's error if instantiating the object failed
     */
    $error = $swissIDConnector->getError();
    ;
}

/**
 * Determine if the end-user has the required Quality of Registration
 * and initiate a step-up if this is not the case
 */
if (is_null($rs = $swissIDConnector->getClaim('urn:swissid:qor'))) {
    /**
     * Handle the object's error if getting the claim failed
     */
    $error = $swissIDConnector->getError();
    ;
} elseif ($rs['value'] == 'qor0') {
    /**
     * Guide the end-user with QoR0 into a step-up process to attain QoR1
     */
    $nonce = bin2hex(random_bytes(8));
    $swissIDConnector->stepUpQoR('qor1');
    if ($swissIDConnector->hasError()) {
        $error = $swissIDConnector->getError();
        if ($error['type'] == 'swissid') {
            switch ($error['error']) {
                case 'invalid_client_id':
                    /**
                     * Handle the case in which the client_id was invalid
                     */
                    ;
                    break;
                case 'redirect_uri_mismatch':
                    /**
                     * Handle the case in which the redirect URI was invalid
                     */
                    ;
                    break;
                case 'general_error':
                    /**
                     * Handle the case of a general error
                     */
                    ;
                    break;
                case 'manual_check_needed':
                    /**
                     * Handle the end-user who is subject to a manual verification
                     */
                    ;
                    break;
                case 'cancelled_by_user':
                    /**
                     * Handle the end-user who cancelled the step-up
                     */
                    ;
                    break;
                case 'access_denied':
                    /**
                     * Handle the end-user who didn't give consent
                     */
                    ;
                    break;
            }
        } elseif ($error['type'] == 'object') {
            /**
             * Handle the object's error if authentication failed
             */
            ;
        }
    }
}
?>