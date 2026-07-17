<?php
namespace Framework\Provider\Type;

use Framework\Utils\Dictionary;

/**
 * The OpenAI Output
 */
class OpenAIOutput {

    public Dictionary $response;

    public string $error         = "";

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



    /**
     * Creates a Basic Output
     * @param Dictionary $response
     * @param string     $error    Optional.
     */
    public function __construct(
        Dictionary $response,
        string $error = "",
    ) {
        $this->response   = $response;
        $this->externalID = $response->getString("id");
        $this->error      = $error;

        if ($error === "" && $response->hasValue("error")) {
            $error = $response->get("error");
            if (is_string($error)) {
                $this->error = $error;
            } else {
                $this->error = $response->getDict("error")->getString("message");
            }
        }
    }
}
