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
    public string $ownerHost     = "";
    public string $ownerValue    = "";

    // SPF DNS Record
    public bool   $spfValid      = false;
    public string $spfType       = "";
    public string $spfHost       = "";
    public string $spfValue      = "";

    // DKIM DNS Record
    public bool   $dkimValid     = false;
    public string $dkimType      = "";
    public string $dkimHost      = "";
    public string $dkimValue     = "";

    // Tracking DNS Record
    public bool   $trackingValid = false;
    public string $trackingType  = "";
    public string $trackingHost  = "";
    public string $trackingValue = "";

    // DMARC DNS Record
    public bool   $dmarcValid    = false;
    public string $dmarcType     = "";
    public string $dmarcHost     = "";
    public string $dmarcValue    = "";

}
