<?php
namespace Tests\Email\Fixture;

/**
 * A stand in for the https wrapper, so a read of a url answers from here
 *
 * The captcha is checked by reading a url with file_get_contents, and this is
 * what makes that answer without leaving the machine. Register it with
 * stream_wrapper_unregister("https") and put the real one back afterwards.
 */
class FakeHttps {

    public static string $body = "";
    public static string $url  = "";

    /** @var resource|null */
    public $context;

    private int $position = 0;


    /**
     * Opens the stream, which is the whole of the body handed to it
     * @param string      $path
     * @param string      $mode
     * @param int         $options
     * @param string|null $openedPath
     * @return bool
     */
    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath,
    ): bool {
        self::$url      = $path;
        $this->position = 0;
        return true;
    }

    /**
     * Reads from the body
     * @param int $count
     * @return string
     */
    public function stream_read(int $count): string {
        $result = substr(self::$body, $this->position, $count);
        $this->position += strlen($result);
        return $result;
    }

    /**
     * Returns true once the body has been read whole
     * @return bool
     */
    public function stream_eof(): bool {
        return $this->position >= strlen(self::$body);
    }

    /**
     * Returns the stats of the stream, which it has none of
     * @return array<int|string,int>
     */
    public function stream_stat(): array {
        return [];
    }
}
