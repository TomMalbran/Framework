<?php
namespace Framework\Provider\Type;

/**
 * The Domain Data
 */
class DomainData {

    public bool   $isEmpty       = true;
    public bool   $isActive      = true;
    public string $id            = "";
    public string $domain        = "";

    // Owner DNS Record
    public bool   $ownerValid    = false;
    public string $ownerType     = "";
    public string $ownerName     = "";
    public string $ownerValue    = "";

    // SPF DNS Record
    public bool   $spfValid      = false;
    public string $spfType       = "";
    public string $spfName       = "";
    public string $spfValue      = "";

    // DKIM DNS Record
    public bool   $dkimValid     = false;
    public string $dkimType      = "";
    public string $dkimName      = "";
    public string $dkimValue     = "";

    // Tracking DNS Record
    public bool   $trackingValid = false;
    public string $trackingType  = "";
    public string $trackingName  = "";
    public string $trackingValue = "";

    // DMARC DNS Record
    public bool   $dmarcValid    = false;
    public string $dmarcType     = "";
    public string $dmarcName     = "";
    public string $dmarcValue    = "";

}
