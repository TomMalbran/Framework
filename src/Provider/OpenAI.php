<?php
namespace Framework\Provider;

use Framework\Config\Config;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use CURLStringFile;

/**
 * The OpenAI Provider
 */
class OpenAI {

    const BaseUrl = "https://api.openai.com/v1";

    const MaxAssistantFiles = 20;

    private static bool   $loaded = false;
    private static string $apiKey = "";



    /**
     * Creates the OpenAI Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }
        self::$apiKey = Config::getString("openAiKey");
        return true;
    }

    /**
     * Does a GET Request
     * @param string       $route
     * @param array{}|null $request Optional.
     * @return mixed
     */
    private static function get(string $route, ?array $request = null): mixed {
        self::load();
        return Curl::get(self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . self::$apiKey,
            "OpenAI-Beta"   => "assistants=v1",
        ], jsonResponse: true);
    }

    /**
     * Does a POST Request
     * @param string       $route
     * @param array{}|null $request Optional.
     * @return mixed
     */
    private static function post(string $route, ?array $request = null): mixed {
        self::load();
        return Curl::post(self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . self::$apiKey,
            "Content-Type"  => "application/json",
            "OpenAI-Beta"   => "assistants=v1",
        ], jsonBody: true, jsonResponse: true);
    }

    /**
     * Does an UPLOAD Request
     * @param string       $route
     * @param array{}|null $request Optional.
     * @return mixed
     */
    private static function upload(string $route, ?array $request = null): mixed {
        self::load();
        return Curl::post(self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . self::$apiKey,
            "Content-Type"  => "multipart/form-data",
            "OpenAI-Beta"   => "assistants=v1",
        ], jsonResponse: true);
    }

    /**
     * Does a DELETE Request
     * @param string       $route
     * @param array{}|null $request Optional.
     * @return mixed
     */
    private static function delete(string $route, ?array $request = null): mixed {
        self::load();
        return Curl::custom("DELETE", self::BaseUrl . $route, $request, [
            "Authorization" => "Bearer " . self::$apiKey,
            "Content-Type"  => "application/json",
            "OpenAI-Beta"   => "assistants=v1",
        ], jsonResponse: true);
    }



    /**
     * Returns a Select of Models
     * @return Select[]
     */
    public static function getModelSelect(): array {
        $request = self::get("/models");
        $result  = [];

        if (!empty($request["data"])) {
            foreach ($request["data"] as $model) {
                $name     = $model["id"];
                $result[] = new Select($name, $name);
            }
        }
        return $result;
    }

    /**
     * Returns true if the given Model exists
     * @param string $model
     * @return boolean
     */
    public static function modelExists(string $model): bool {
        if (empty($model)) {
            return false;
        }
        $request = self::get("/models/$model");
        return !empty($request["id"]);
    }



    /**
     * Uploads the given File
     * @param string $fileName
     * @param string $fileContent
     * @return string
     */
    public static function uploadFile(string $fileName, string $fileContent): string {
        $request = self::upload("/files", [
            "purpose" => "assistants",
            "file"    => new CURLStringFile($fileContent, $fileName, "text/plain"),
        ]);
        return !empty($request["id"]) ? $request["id"] : "";
    }

    /**
     * Deletes the given File
     * @param string $fileID
     * @return boolean
     */
    public static function deleteFile(string $fileID): bool {
        $request = self::delete("/files/$fileID");
        return !empty($request["id"]);
    }



    /**
     * Creates a new Assistant
     * @param string   $name
     * @param string   $model
     * @param string   $description
     * @param string   $instructions
     * @param string[] $fileIDs      Optional.
     * @return string
     */
    public static function createAssistant(
        string $name,
        string $model,
        string $description,
        string $instructions,
        array $fileIDs = [],
    ): string {
        $request = self::post("/assistants", [
            "name"         => $name,
            "model"        => $model,
            "description"  => $description,
            "instructions" => $instructions,
            "file_ids"     => $fileIDs,
            "tools"        => [[ "type" => "retrieval" ]],
        ]);
        return !empty($request["id"]) ? $request["id"] : "";
    }

    /**
     * Edits an Assistant
     * @param string   $assistantID
     * @param string   $name
     * @param string   $model
     * @param string   $description
     * @param string   $instructions
     * @param string[] $fileIDs      Optional.
     * @return string
     */
    public static function editAssistant(
        string $assistantID,
        string $name,
        string $model,
        string $description,
        string $instructions,
        array $fileIDs = [],
    ): string {
        $request = self::post("/assistants/$assistantID", [
            "name"         => $name,
            "model"        => $model,
            "description"  => $description,
            "instructions" => $instructions,
            "file_ids"     => $fileIDs,
            "tools"        => [[ "type" => "retrieval" ]],
        ]);
        return !empty($request["id"]) ? $request["id"] : "";
    }

    /**
     * Deletes the given Assistant
     * @param string $assistantID
     * @return boolean
     */
    public static function deleteAssistant(string $assistantID): bool {
        $request = self::delete("/assistants/$assistantID");
        return !empty($request["id"]);
    }



    /**
     * Creates a new Thread
     * @return string
     */
    public static function createThread(): string {
        $request = self::post("/threads");
        return !empty($request["id"]) ? $request["id"] : "";
    }

    /**
     * Adds a Message to a Thread
     * @param string $threadID
     * @param string $message
     * @return boolean
     */
    public static function addThreadMessage(string $threadID, string $message): bool {
        $request = self::post("/threads/$threadID/messages", [
            "role"    => "user",
            "content" => $message,
        ]);
        return !empty($request["id"]);
    }

    /**
     * Returns the last Message in a Thread
     * @param string $threadID
     * @return string
     */
    public static function getLastMessage(string $threadID): string {
        $request = self::get("/threads/$threadID/messages", [
            "limit" => 1,
            "order" => "desc",
        ]);
        if (empty($request["data"][0])) {
            return "";
        }

        $message = [];
        foreach ($request["data"][0]["content"] as $content) {
            if ($content["type"] === "text") {
                $message[] = $content["text"]["value"];
            }
        }
        return Strings::join($message, "\n");
    }

    /**
     * Deletes the given Thread
     * @param string $threadID
     * @return boolean
     */
    public static function deleteThread(string $threadID): bool {
        $request = self::delete("/threads/$threadID");
        return !empty($request["id"]);
    }



    /**
     * Creates a Thread Run
     * @param string $assistantID
     * @param string $message
     * @return string[]
     */
    public static function createRun(string $assistantID, string $message): array {
        $request = self::post("/threads/runs", [
            "assistant_id" => $assistantID,
            "thread"       => [
                "messages" => [
                    [
                        "role"    => "user",
                        "content" => $message,
                    ],
                ],
            ],
        ]);

        if (empty($request["id"])) {
            return [ "", "" ];
        }
        return [ $request["id"], $request["thread_id"] ];
    }

    /**
     * Starts a Thread Run
     * @param string $threadID
     * @param string $assistantID
     * @return string
     */
    public static function startRun(string $threadID, string $assistantID): string {
        $request = self::post("/threads/$threadID/runs", [
            "assistant_id" => $assistantID,
        ]);
        return !empty($request["id"]) ? $request["id"] : "";
    }

    /**
     * Returns true if the Thread Run is Completed
     * @param string $threadID
     * @param string $runID
     * @return boolean
     */
    public static function isRunComplete(string $threadID, string $runID): bool {
        $request = self::get("/threads/$threadID/runs/$runID");
        return empty($request["id"]) || $request["status"] === "completed";
    }

    /**
     * Returns the total tokens of a Run
     * @param string $threadID
     * @param string $runID
     * @return object
     */
    public static function getRunData(string $threadID, string $runID): object {
        $request = self::get("/threads/$threadID/runs/$runID");
        if (empty($request["usage"])) {
            return (object)[];
        }

        return (object)[
            "runTime"      => $request["completed_at"] - $request["started_at"],
            "fileIDs"      => Strings::join($request["file_ids"], ", "),
            "inputTokens"  => $request["usage"]["prompt_tokens"],
            "outputTokens" => $request["usage"]["completion_tokens"],
        ];
    }

    /**
     * Cancels a Thread Run
     * @param string $threadID
     * @param string $runID
     * @return string
     */
    public static function cancelRun(string $threadID, string $runID): string {
        $request = self::post("/threads/$threadID/runs/$runID/cancel");
        return !empty($request["id"]);
    }



    /**
     * Transcribes an Audio
     * @param string $fileContent
     * @param string $fileName
     * @param string $language
     * @return string
     */
    public static function transcribeAudio(string $fileContent, string $fileName, string $language): string {
        $request = self::upload("/audio/transcriptions", [
            "file"     => new CURLStringFile($fileContent, $fileName),
            "model"    => "whisper-1",
            "language" => $language,
        ]);
        return !empty($request["text"]) ? $request["text"] : "";
    }
}
