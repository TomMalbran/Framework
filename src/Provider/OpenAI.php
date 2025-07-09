<?php
namespace Framework\Provider;

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

    public  const MaxAssistantFiles = 20;


    /**
     * Does a GET Request
     * @param string                   $route
     * @param array<string,mixed>|null $request Optional.
     * @return Dictionary
     */
    private static function get(string $route, ?array $request = null): Dictionary {
        $response = Curl::execute("GET", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . Config::getOpenAiKey(),
            "OpenAI-Beta"   => "assistants=v2",
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
            "OpenAI-Beta"   => "assistants=v2",
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
            "OpenAI-Beta"   => "assistants=v2",
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
            "OpenAI-Beta"   => "assistants=v2",
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
     * Creates a Completion and returns the Result
     * @param string                              $model
     * @param string                              $prompt
     * @param array{role:string,content:string}[] $context Optional.
     * @param mixed                               $schema  Optional.
     * @return OpenAIOutput
     */
    public static function createCompletion(
        string $model,
        string $prompt,
        array $context = [],
        mixed $schema = null,
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

        if ($schema !== null && is_array($schema)) {
            $params["response_format"] = [
                "type"        => "json_schema",
                "json_schema" => [
                    "name"   => "Schema",
                    "strict" => true,
                    "schema" => [
                        "type"                 => "object",
                        "properties"           => $schema,
                        "required"             => array_keys($schema),
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

        $result->externalID   = $response->getString("id");
        $result->text         = $choice->getDict("message")->getString("content");
        $result->inputTokens  = $response->getDict("usage")->getInt("prompt_tokens");
        $result->outputTokens = $response->getDict("usage")->getInt("completion_tokens");
        $result->runTime      = Numbers::roundInt($endTime - $startTime);
        return $result;
    }

    /**
     * Creates a Response and returns the Result
     * @param string                              $model
     * @param string                              $prompt
     * @param array{role:string,content:string}[] $context       Optional.
     * @param mixed                               $schema        Optional.
     * @param string                              $vectorStoreID Optional.
     * @return OpenAIOutput
     */
    public static function createResponse(
        string $model,
        string $prompt,
        array $context = [],
        mixed $schema = null,
        string $vectorStoreID = "",
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

        if ($schema !== null && is_array($schema)) {
            $params["text"] = [
                "format" => [
                    "type"   => "json_schema",
                    "name"   => "Schema",
                    "strict" => true,
                    "schema" => [
                        "type"                 => "object",
                        "properties"           => $schema,
                        "required"             => array_keys($schema),
                        "additionalProperties" => false,
                    ],
                ],
            ];
        }

        if ($vectorStoreID !== "") {
            $params["tool_choice"] = "required";
            $params["tools"]       = [ [
                "type"             => "file_search",
                "vector_store_ids" => [ $vectorStoreID ]
            ] ];
        }

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

        if ($text !== "") {
            $result->externalID    = $response->getString("id");
            $result->vectorStoreID = $vectorStoreID;
            $result->text          = $text;
            $result->inputTokens   = $response->getDict("usage")->getInt("input_tokens");
            $result->outputTokens  = $response->getDict("usage")->getInt("output_tokens");
            $result->runTime       = Numbers::roundInt($endTime - $startTime);
        }
        return $result;
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
     * Creates a new Assistant
     * @param string $name
     * @param string $model
     * @param string $description
     * @param string $instructions
     * @param string $vectorStoreID
     * @return string
     */
    public static function createAssistant(
        string $name,
        string $model,
        string $description,
        string $instructions,
        string $vectorStoreID,
    ): string {
        $response = self::post("/assistants", [
            "name"           => $name,
            "model"          => $model,
            "description"    => $description,
            "instructions"   => $instructions,
            "tools"          => [[ "type" => "file_search" ]],
            "tool_resources" => [ "file_search" => [ "vector_store_ids" => [ $vectorStoreID ]]],
        ]);
        return $response->getString("id");
    }

    /**
     * Edits an Assistant
     * @param string $assistantID
     * @param string $name
     * @param string $model
     * @param string $description
     * @param string $instructions
     * @param string $vectorStoreID
     * @return string
     */
    public static function editAssistant(
        string $assistantID,
        string $name,
        string $model,
        string $description,
        string $instructions,
        string $vectorStoreID,
    ): string {
        $response = self::post("/assistants/$assistantID", [
            "name"           => $name,
            "model"          => $model,
            "description"    => $description,
            "instructions"   => $instructions,
            "tools"          => [[ "type" => "file_search" ]],
            "tool_resources" => [ "file_search" => [ "vector_store_ids" => [ $vectorStoreID ]]],
        ]);
        return $response->getString("id");
    }

    /**
     * Deletes the given Assistant
     * @param string $assistantID
     * @return boolean
     */
    public static function deleteAssistant(string $assistantID): bool {
        $response = self::delete("/assistants/$assistantID");
        return $response->hasValue("id");
    }



    /**
     * Creates a new Thread
     * @return string
     */
    public static function createThread(): string {
        $response = self::post("/threads");
        return $response->getString("id");
    }

    /**
     * Adds a Message to a Thread
     * @param string  $threadID
     * @param string  $message
     * @param boolean $fromUser Optional.
     * @return string
     */
    public static function addThreadMessage(string $threadID, string $message, bool $fromUser = true): string {
        $response = self::post("/threads/$threadID/messages", [
            "role"    => $fromUser ? "user" : "assistant",
            "content" => $message,
        ]);
        return $response->getString("id");
    }

    /**
     * Deletes a Message to a Thread
     * @param string $threadID
     * @param string $messageID
     * @return boolean
     */
    public static function deleteThreadMessage(string $threadID, string $messageID): bool {
        $response = self::delete("/threads/$threadID/messages/$messageID");
        return $response->hasValue("id");
    }

    /**
     * Returns the last Message in a Thread
     * @param string $threadID
     * @return array{string,string} [Message, FileIDs]
     */
    public static function getLastMessage(string $threadID): array {
        $response = self::get("/threads/$threadID/messages", [
            "limit" => 1,
            "order" => "desc",
        ]);
        $data = $response->getFirst("data");
        if ($data->isEmpty()) {
            return [ "", "", "" ];
        }

        $messages = [];
        $fileIDs  = [];
        foreach ($data->getDict("content") as $content) {
            if ($content->getString("type") === "text") {
                $text    = $content->getDict("text");
                $message = $text->getString("value");

                foreach ($text->getDict("annotations") as $annotation) {
                    $message = Strings::replace($message, $annotation->getString("text"), "");
                    $fileID  = $annotation->getDict("file_citation")->getString("file_id");
                    if ($fileID !== "") {
                        $fileIDs[] = $fileID;
                    }
                }
                $messages[] = $message;
            }
        }
        return [
            Strings::join($messages, "\n"),
            Strings::join($fileIDs, ", "),
        ];
    }

    /**
     * Deletes the given Thread
     * @param string $threadID
     * @return boolean
     */
    public static function deleteThread(string $threadID): bool {
        $response = self::delete("/threads/$threadID");
        return $response->hasValue("id");
    }



    /**
     * Creates a Thread and a Run
     * @param string                              $assistantID
     * @param string                              $message
     * @param array{role:string,content:string}[] $context       Optional.
     * @param boolean                             $requiresFiles Optional.
     * @return array{string,string} [RunID, ThreadID]
     */
    public static function createRun(
        string $assistantID,
        string $message,
        array $context = [],
        bool $requiresFiles = false,
    ): array {
        $response = self::post("/threads/runs", [
            "assistant_id" => $assistantID,
            "tool_choice"  => $requiresFiles ? "required" : "auto",
            "thread"       => [
                "messages" => array_merge($context, [
                    [
                        "role"    => "user",
                        "content" => $message,
                    ],
                ]),
            ],
        ]);

        return [
            $response->getString("id"),
            $response->getString("thread_id"),
        ];
    }

    /**
     * Starts a Thread Run
     * @param string  $threadID
     * @param string  $assistantID
     * @param string  $instructions
     * @param boolean $requiresFiles Optional.
     * @return string
     */
    public static function startRun(string $threadID, string $assistantID, string $instructions, bool $requiresFiles = false): string {
        $response = self::post("/threads/$threadID/runs", [
            "assistant_id" => $assistantID,
            "instructions" => $instructions,
            "tool_choice"  => $requiresFiles ? "required" : "auto",
        ]);
        return $response->getString("id");
    }

    /**
     * Returns true if the Thread Run is Completed
     * @param string $threadID
     * @param string $runID
     * @return boolean
     */
    public static function isRunComplete(string $threadID, string $runID): bool {
        $response = self::get("/threads/$threadID/runs/$runID");
        return !$response->hasValue("id") || $response->getString("status") === "completed";
    }

    /**
     * Returns the output of a Run
     * @param string $threadID
     * @param string $runID
     * @return OpenAIOutput
     */
    public static function getRunOutput(string $threadID, string $runID): OpenAIOutput {
        $result   = new OpenAIOutput();
        $response = self::get("/threads/$threadID/runs/$runID");
        if (!$response->hasValue("usage")) {
            return $result;
        }

        [ $message, $fileIDs ] = self::getLastMessage($threadID);

        $result->externalID   = $runID;
        $result->threadID     = $threadID;
        $result->fileIDs      = $fileIDs;

        $result->text         = $message;
        $result->runTime      = $response->getInt("completed_at") - $response->getInt("started_at");
        $result->inputTokens  = $response->getDict("usage")->getInt("prompt_tokens");
        $result->outputTokens = $response->getDict("usage")->getInt("completion_tokens");
        return $result;
    }

    /**
     * Cancels a Thread Run
     * @param string $threadID
     * @param string $runID
     * @return boolean
     */
    public static function cancelRun(string $threadID, string $runID): bool {
        $response = self::post("/threads/$threadID/runs/$runID/cancel");
        return $response->hasValue("id");
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

/**
 * The OpenAI Data
 */
class OpenAIOutput {

    public string $externalID    = "";
    public string $threadID      = "";
    public string $vectorStoreID = "";
    public string $fileIDs       = "";

    public string $text          = "";
    public string $language      = "";
    public int    $duration      = 0;

    public int    $runTime       = 0;
    public int    $inputTokens   = 0;
    public int    $outputTokens  = 0;

}
