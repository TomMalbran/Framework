<?php
namespace Framework\Provider\Type;

/**
 * The OpenAI Output
 */
class OpenAIOutput {

    public string $externalID    = "";
    public string $vectorStoreID = "";
    public string $fileIDs       = "";

    public string $text          = "";
    public string $language      = "";
    public int    $duration      = 0;

    public int    $runTime       = 0;
    public int    $inputTokens   = 0;
    public int    $outputTokens  = 0;
    public bool   $didSearchCall = false;
}
