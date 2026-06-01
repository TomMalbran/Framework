<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\File\Storage;
use Framework\File\FileType;
use Framework\File\MediaFile;
use Framework\Utils\Strings;

use CURLFile;

/**
 * The File Value
 * @implements ValueInterface<string,string>
 */
class FileValue extends Value implements ValueInterface {

    private string $value;


    /**
     * Creates a new FileValue instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        parent::__construct($request, $key);
        $this->value = $request->getString($key);
    }

    /**
     * Sets the value
     * @param string $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value;
        $this->setRaw($value);
    }

    /**
     * Sets the value if the value is not empty
     * @param string $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value !== "") {
            $this->set($value);
        }
    }

    /**
     * Unsets the value
     * @return void
     */
    #[\Override]
    public function unset(): void {
        $this->set("");
    }



    /**
     * Returns the value
     * @return string
     */
    #[\Override]
    public function get(): string {
        return $this->value;
    }

    /**
     * Returns the value or null if the value is empty
     * @return string|null
     */
    #[\Override]
    public function getOrNull(): ?string {
        return $this->value !== "" ? $this->value : null;
    }

    /**
     * Returns the value for database storage
     * @return string
     */
    #[\Override]
    public function toDatabase(): string {
        return $this->value;
    }

    /**
     * Returns the file name
     * @return string
     */
    public function getFileName(): string {
        if ($this->hasFile()) {
            return $this->request->getFileName($this->key);
        }
        return $this->value;
    }

    /**
     * Returns the request file temporal name
     * @return string
     */
    public function getTmpName(): string {
        if ($this->hasFile()) {
            return $this->request->getTmpName($this->key);
        }
        return $this->value;
    }

    /**
     * Returns the request file as CURLFile
     * @return CURLFile|null
     */
    public function getCurlFile(): ?CURLFile {
        if ($this->hasFile()) {
            return curl_file_create(
                $this->request->getTmpName($this->key),
                $this->request->getFileType($this->key),
                $this->request->getFileName($this->key),
            );
        }
        return null;
    }



    /**
     * Returns true if the value is valid
     * @return bool
     */
    public function isValid(): bool {
        return Strings::isValid($this->raw);
    }

    /**
     * Returns true if the file exists in the request
     * @return bool
     */
    public function hasFile(): bool {
        return $this->request->hasFile($this->key);
    }

    /**
     * Returns true if there was a size error in the upload
     * @return bool
     */
    public function hasSizeError(): bool {
        return $this->request->hasSizeError($this->key);
    }

    /**
     * Returns true if the file has the given extension
     * @param string ...$extensions
     * @return bool
     */
    public function hasExtension(string ...$extensions): bool {
        $fileName = $this->getFileName();
        return Storage::hasExtension($fileName, ...$extensions);
    }

    /**
     * Returns true if the file is a valid image
     * @return bool
     */
    public function isValidImage(): bool {
        if ($this->hasFile()) {
            return $this->request->isValidImage($this->key);
        }
        return FileType::isImage($this->value);
    }

    /**
     * Returns true if the file exists
     * @return bool
     */
    public function mediaExists(): bool {
        return MediaFile::exists($this->value);
    }
}
