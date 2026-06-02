<?php
namespace Framework\Provider;

use Framework\Provider\Type\CurlMethod;
use Framework\Provider\Type\OllamaOutput;
use Framework\Date\Timer;
use Framework\System\Config;
use Framework\Utils\Dictionary;

/**
 * The Ollama Provider
 */
class Ollama {

    /**
     * Does a POST Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return Dictionary
     */
    private static function post(string $route, ?array $request = null): Dictionary {
        $response = Curl::execute(
            method:       CurlMethod::POST,
            url:          Config::getOllamaUrl() . "/api/$route",
            params:       $request,
            headers:      [ "Content-Type" => "application/json" ],
            jsonBody:     true,
            jsonResponse: true,
            returnError:  true,
        );
        return new Dictionary($response);
    }

    /**
     * Creates a Completion and returns the Result
     * @param string                                  $model
     * @param string                                  $prompt
     * @param list<array{role:string,content:string}> $context          Optional.
     * @param Dictionary|null                         $schema           Optional.
     * @param bool                                    $removeReferences Optional.
     * @return OllamaOutput
     */
    public static function createCompletion(
        string $model,
        string $prompt,
        array $context = [],
        ?Dictionary $schema = null,
        bool $removeReferences = true,
    ): OllamaOutput {
        $timer  = new Timer();
        $params = [
            "stream"   => false,
            "model"    => $model,
            "messages" => array_merge($context, [
                [
                    "role"    => "user",
                    "content" => $prompt,
                ],
            ]),
        ];

        if ($schema !== null && $schema->isNotEmpty()) {
            $params["format"] = [
                "type"                 => "object",
                "properties"           => $schema->toArray(),
                "required"             => $schema->getKeys(),
                "additionalProperties" => false,
            ];
        }


        // Perform the Request
        $result   = new OllamaOutput();
        $response = self::post("/generate", $params);

        // Check for errors
        if ($response->hasValue("error")) {
            $error = $response->get("error");
            if (is_string($error)) {
                $result->error = $error;
            } else {
                $result->error = $response->getDict("error")->getString("message");
            }
            return $result;
        }

        // Generate the Output
        $result->text         = $response->getString("response");
        $result->inputTokens  = $response->getInt("prompt_eval_count");
        $result->outputTokens = $response->getInt("eval_count");
        $result->runTime      = $timer->getElapsedSecondsInt();
        return $result;
    }
}
