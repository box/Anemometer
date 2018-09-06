<?php
/**
 * Require OKTA authentication before proceeding
 */

// Authenticate Request
try {

    $SAML = $_REQUEST['SAMLResponse'];
    $xml = base64_decode($SAML);
    $deserializationContext = new \LightSaml\Model\Context\DeserializationContext();
    $deserializationContext->getDocument()->loadXML($xml);

    $response = new \LightSaml\Model\Protocol\Response();
    $response->deserialize(
        $deserializationContext->getDocument()->firstChild,
        $deserializationContext
    );

    $assertion = $response->getFirstAssertion();
    $attributes = [];
    foreach ($assertion->getFirstAttributeStatement()->getAllAttributes() as $attribute) {
        $attributes[$attribute->getName()] = $attribute->getFirstAttributeValue();
    }
} catch (Exception $e) {
    echo "Auth Error: " . $e->getMessage();
    echo $e->getTraceAsString();
    die();
}

echo print_r($attributes, true);