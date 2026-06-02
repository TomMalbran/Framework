<?php
namespace Framework\File;

use Framework\File\Storage;
use Framework\File\FileType;
use Framework\File\MediaFile;

use CURLFile;
use JsonSerializable;

/**
 * The File
 * @phpstan-type FileRequest = array{
 *   name: string,
 *   type: string,
 *   tmp_name: string,
 *   error: int,
 *   size: int
 * }
 */
class File implements JsonSerializable {

    /** @var FileRequest|null */
    private ?array $fileRequest = null;

    private string $filePath = "";


    /**
     * Creates a new File instance
     * @param string           $filePath    Optional.
     * @param FileRequest|null $fileRequest Optional.
     */
    public function __construct(
        string $filePath = "",
        ?array $fileRequest = null,
    ) {
        $this->filePath    = $filePath;
        $this->fileRequest = $fileRequest;
    }

    /**
     * Creates a new File instance from a request
     * @param string $key
     * @param string $filePath Optional.
     * @return File
     */
    public static function fromRequest(string $key, string $filePath = ""): File {
        if (!isset($_FILES[$key])) {
            return new File($filePath);
        }

        /** @var FileRequest */
        $fileRequest = $_FILES[$key];
        return new File("", $fileRequest);
    }



    /**
     * Returns true if the File exists in the request
     * @return bool
     */
    public function hasFile(): bool {
        return $this->fileRequest !== null;
    }

    /**
     * Returns true if the File is valid
     * @return bool
     */
    public function isValid(): bool {
        if ($this->fileRequest !== null) {
            return $this->fileRequest["error"] === UPLOAD_ERR_OK;
        }
        return $this->filePath !== "";
    }

    /**
     * Returns true if there was a size error in the upload
     * @return bool
     */
    public function hasSizeError(): bool {
        if ($this->fileRequest !== null) {
            return $this->fileRequest["error"] === UPLOAD_ERR_INI_SIZE;
        }
        return false;
    }

    /**
     * Returns true if the File has the given extension
     * @param string ...$extensions
     * @return bool
     */
    public function hasExtension(string ...$extensions): bool {
        $fileName = $this->getName();
        return Storage::hasExtension($fileName, ...$extensions);
    }

    /**
     * Returns true if the File is an image
     * @return bool
     */
    public function isImage(): bool {
        if ($this->fileRequest !== null) {
            return FileType::isImage($this->getName());
        }
        return FileType::isImage($this->filePath);
    }

    /**
     * Returns true if the file is a valid image
     * @return bool
     */
    public function isValidImage(): bool {
        if ($this->fileRequest !== null) {
            return Image::isValidType($this->getTmpName());
        }
        return FileType::isImage($this->filePath);
    }

    /**
     * Returns true if the File exists
     * @return bool
     */
    public function mediaExists(): bool {
        if ($this->fileRequest !== null) {
            return false;
        }
        return MediaFile::exists($this->filePath);
    }



    /**
     * Returns the File name
     * @return string
     */
    public function getName(): string {
        if ($this->fileRequest !== null) {
            return $this->fileRequest["name"];
        }
        return $this->filePath;
    }

    /**
     * Returns the File temporal name
     * @return string
     */
    public function getTmpName(): string {
        if ($this->fileRequest !== null) {
            return $this->fileRequest["tmp_name"];
        }
        return $this->filePath;
    }

    /**
     * Returns the File type
     * @return string
     */
    public function getType(): string {
        if ($this->fileRequest !== null) {
            return $this->fileRequest["type"];
        }
        return "";
    }

    /**
     * Returns the File extension
     * @return string
     */
    public function getExtension(): string {
        return Storage::getExtension($this->getName());
    }

    /**
     * Returns the File as CURLFile
     * @return CURLFile|null
     */
    public function getCurlFile(): ?CURLFile {
        if ($this->fileRequest !== null) {
            return curl_file_create(
                $this->getTmpName(),
                $this->getType(),
                $this->getName(),
            );
        }
        return null;
    }

    /**
     * Returns the File as a value (file path or CURLFile)
     * @return CURLFile|string
     */
    public function getValue(): CURLFile|string {
        $value = $this->getCurlFile();
        if ($value !== null) {
            return $value;
        }
        return $this->filePath;
    }



    /**
     * Parses a new file name based on the original name
     * @param string $newName
     * @return string
     */
    public function parseName(string $newName): string {
        $fileName = $this->getName();
        return Storage::parseName($newName, $fileName);
    }

    /**
     * Uploads the File to the given path
     * @param string $path
     * @param string $name Optional.
     * @return bool
     */
    public function upload(string $path, string $name = ""): bool {
        if ($this->fileRequest !== null) {
            return Storage::uploadFile($path, $name, $this->getTmpName());
        }
        return false;
    }

    /**
     * Deletes the File from the given path
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool {
        if ($this->filePath !== "") {
            return Storage::deleteFile($path, $this->filePath);
        }
        return false;
    }



    /**
     * Returns the file as a string (file path)
     * @return string
     */
    public function toString(): string {
        return $this->filePath;
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->toString();
    }
}
