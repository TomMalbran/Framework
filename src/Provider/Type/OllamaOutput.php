<?php
namespace Framework\Provider\Type;

/**
 * The Ollama Output
 */
class OllamaOutput {

    public string $error        = "";

    public string $text         = "";
    public int    $runTime      = 0;
    public int    $inputTokens  = 0;
    public int    $outputTokens = 0;
}
