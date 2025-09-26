<?php
namespace Framework\Provider;

use Framework\Provider\Type\OpenAIOutput;
use Framework\System\Config;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use CURLStringFile;

/**
 * The OpenAI Provider
 */
class OpenAI {

    private const BaseUrl = "https://api.openai.com/v1";



    /**
     * Does a GET Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return Dictionary
     */
    private static function get(string $route, ?array $request = null): Dictionary {
        $response = Curl::execute("GET", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . Config::getOpenAiKey(),
        ], jsonResponse: true);
        return new Dictionary($response);
    }

    /**
     * Does a POST Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return Dictionary
     */
    private static function post(string $route, ?array $request = null): Dictionary {
        $response = Curl::execute("POST", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . Config::getOpenAiKey(),
            "Content-Type"  => "application/json",
        ], jsonBody: true, jsonResponse: true);
        return new Dictionary($response);
    }

    /**
     * Does an UPLOAD Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return Dictionary
     */
    private static function upload(string $route, ?array $request = null): Dictionary {
        $response = Curl::execute("POST", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . Config::getOpenAiKey(),
            "Content-Type"  => "multipart/form-data",
        ], jsonResponse: true);
        return new Dictionary($response);
    }

    /**
     * Does a DELETE Request
     * @param string       $route
     * @param array{}|null $request Optional.
     * @return Dictionary
     */
    private static function delete(string $route, ?array $request = null): Dictionary {
        $response = Curl::execute("DELETE", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . Config::getOpenAiKey(),
            "Content-Type"  => "application/json",
        ], jsonResponse: true);
        return new Dictionary($response);
    }



    /**
     * Returns a Select of Models
     * @return Select[]
     */
    public static function getModelSelect(): array {
        $response = self::get("/models");
        $result   = [];

        foreach ($response->getDict("data") as $model) {
            $name     = $model->getString("id");
            $result[] = new Select($name, $name);
        }
        return $result;
    }

    /**
     * Returns true if the given Model exists
     * @param string $model
     * @return boolean
     */
    public static function modelExists(string $model): bool {
        if ($model === "") {
            return false;
        }

        $response = self::get("/models/$model");
        return $response->hasValue("id");
    }



    /**
     * Uploads the given File
     * @param string $fileName
     * @param string $fileContent
     * @return string
     */
    public static function uploadFile(string $fileName, string $fileContent): string {
        $response = self::upload("/files", [
            "purpose" => "assistants",
            "file"    => new CURLStringFile($fileContent, $fileName, "text/plain"),
        ]);
        return $response->getString("id");
    }

    /**
     * Deletes the given File
     * @param string $fileID
     * @return boolean
     */
    public static function deleteFile(string $fileID): bool {
        $response = self::delete("/files/$fileID");
        return $response->hasValue("id");
    }



    /**
     * Lists all the Vector Store
     * @return array<integer|string,mixed>
     */
    public static function getAllVectorStores(): array {
        $response = self::get("/vector_stores");
        return $response->getArray("data");
    }

    /**
     * Creates a Vector Store
     * @param string $name
     * @return string
     */
    public static function createVectorStore(string $name): string {
        $response = self::post("/vector_stores", [
            "name" => $name,
        ]);
        return $response->getString("id");
    }

    /**
     * Edits a Vector Store
     * @param string $vectorStoreID
     * @param string $name
     * @return string
     */
    public static function editVectorStore(string $vectorStoreID, string $name): string {
        $response = self::post("/vector_stores/$vectorStoreID", [
            "name" => $name,
        ]);
        return $response->getString("id");
    }

    /**
     * Deletes a Vector Store
     * @param string $vectorStoreID
     * @return string
     */
    public static function deleteVectorStore(string $vectorStoreID): string {
        $response = self::delete("/vector_stores/$vectorStoreID");
        return $response->getString("id");
    }

    /**
     * Creates a Vector Store File
     * @param string $vectorStoreID
     * @param string $fileID
     * @return string
     */
    public static function createVectorFile(string $vectorStoreID, string $fileID): string {
        $response = self::post("/vector_stores/$vectorStoreID/files", [
            "file_id" => $fileID,
        ]);
        return $response->getString("id");
    }

    /**
     * Deletes a Vector Store File
     * @param string $vectorStoreID
     * @param string $fileID
     * @return string
     */
    public static function deleteVectorFile(string $vectorStoreID, string $fileID): string {
        $response = self::delete("/vector_stores/$vectorStoreID/files/$fileID");
        return $response->getString("id");
    }



    /**
     * Creates a Completion and returns the Result
     * @param string                              $model
     * @param string                              $prompt
     * @param array{role:string,content:string}[] $context          Optional.
     * @param Dictionary|null                     $schema           Optional.
     * @param boolean                             $removeReferences Optional.
     * @return OpenAIOutput
     */
    public static function createCompletion(
        string $model,
        string $prompt,
        array $context = [],
        ?Dictionary $schema = null,
        bool $removeReferences = true,
    ): OpenAIOutput {
        $startTime = microtime(true);
        $params    = [
            "model"    => $model,
            "messages" => array_merge($context, [
                [
                    "role"    => "user",
                    "content" => $prompt,
                ],
            ]),
        ];

        if ($schema !== null) {
            $params["response_format"] = [
                "type"        => "json_schema",
                "json_schema" => [
                    "name"   => "Schema",
                    "strict" => true,
                    "schema" => [
                        "type"                 => "object",
                        "properties"           => $schema->toArray(),
                        "required"             => $schema->getKeys(),
                        "additionalProperties" => false,
                    ],
                ],
            ];
        }

        $result   = new OpenAIOutput();
        $response = self::post("/chat/completions", $params);
        $endTime  = microtime(true);
        $choice   = $response->getFirst("choices");
        if ($choice->isEmpty()) {
            return $result;
        }

        $text = $choice->getDict("message")->getString("content");
        if ($removeReferences) {
            $text = Strings::replace($text, '/【.*?†.*?】/', "");
        }

        $result->externalID   = $response->getString("id");
        $result->text         = $text;
        $result->inputTokens  = $response->getDict("usage")->getInt("prompt_tokens");
        $result->outputTokens = $response->getDict("usage")->getInt("completion_tokens");
        $result->runTime      = Numbers::roundInt($endTime - $startTime);
        return $result;
    }

    /**
     * Creates a Response and returns the Result
     * @param string                              $model
     * @param string                              $prompt
     * @param array{role:string,content:string}[] $context          Optional.
     * @param Dictionary|null                     $schema           Optional.
     * @param string                              $vectorStoreID    Optional.
     * @param boolean                             $allowWebSearch   Optional.
     * @param boolean                             $removeReferences Optional.
     * @return OpenAIOutput
     */
    public static function createResponse(
        string $model,
        string $prompt,
        array $context = [],
        ?Dictionary $schema = null,
        string $vectorStoreID = "",
        bool $allowWebSearch = false,
        bool $removeReferences = true,
    ): OpenAIOutput {
        $startTime = microtime(true);
        $params    = [
            "model" => $model,
            "input" => array_merge($context, [
                [
                    "role"    => "user",
                    "content" => $prompt,
                ],
            ]),
        ];

        // Set the Schema
        if ($schema !== null) {
            $params["text"] = [
                "format" => [
                    "type"   => "json_schema",
                    "name"   => "Schema",
                    "strict" => true,
                    "schema" => [
                        "type"                 => "object",
                        "properties"           => $schema->toArray(),
                        "required"             => $schema->getKeys(),
                        "additionalProperties" => false,
                    ],
                ],
            ];
        }

        // Parse the required tools
        $tools = [];
        if ($vectorStoreID !== "") {
            $params["tool_choice"] = "required";
            $tools[] = [
                "type"             => "file_search",
                "vector_store_ids" => [ $vectorStoreID ]
            ];
        }
        if ($allowWebSearch) {
            $tools[] = [
                "type"                => "web_search",
                "search_context_size" => "low",
            ];
        }
        if (count($tools) > 0) {
            $params["tools"] = $tools;
        }

        // Perform the request and get the Result
        $result   = new OpenAIOutput();
        $response = self::post("/responses", $params);
        $endTime  = microtime(true);
        $outputs  = $response->getList("output");
        $text     = "";

        foreach ($outputs as $output) {
            if ($output->getString("type") === "message") {
                $text = $output->getFirst("content")->getString("text");
                break;
            }
        }
        if ($removeReferences) {
            $text = Strings::replace($text, '/【.*?†.*?】/', "");
        }

        if ($text !== "") {
            $result->externalID    = $response->getString("id");
            $result->vectorStoreID = $vectorStoreID;
            $result->text          = $text;
            $result->inputTokens   = $response->getDict("usage")->getInt("input_tokens");
            $result->outputTokens  = $response->getDict("usage")->getInt("output_tokens");
            $result->runTime       = Numbers::roundInt($endTime - $startTime);

            foreach ($response->getList("tools") as $tool) {
                if ($tool->getString("type") === "web_search_preview") {
                    $result->didSearchCall = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Transcribes an Audio
     * @param string $fileContent
     * @param string $fileName
     * @param string $language
     * @return OpenAIOutput
     */
    public static function transcribeAudio(string $fileContent, string $fileName, string $language): OpenAIOutput {
        $result    = new OpenAIOutput();
        $timeStart = microtime(true);
        $response  = self::upload("/audio/transcriptions", [
            "file"            => new CURLStringFile($fileContent, $fileName),
            "model"           => "whisper-1",
            "language"        => $language,
            "response_format" => "verbose_json",
        ]);
        $timeEnd   = microtime(true);
        if (!$response->hasValue("text")) {
            return $result;
        }

        $outputTokens = 0;
        foreach ($response->getDict("segments") as $segment) {
            $outputTokens += count($segment->getArray("tokens"));
        }

        $result->text         = $response->getString("text");
        $result->language     = $response->getString("language");
        $result->duration     = (int)ceil($response->getFloat("duration"));
        $result->runTime      = Numbers::roundInt(($timeEnd - $timeStart) / 60);
        $result->outputTokens = $outputTokens;
        return $result;
    }
}
