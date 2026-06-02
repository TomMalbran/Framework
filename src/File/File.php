<?php
namespace Framework\File;

use Framework\File\Storage;
use Framework\File\FileType;
use Framework\File\MediaFile;

use CURLFile;
use JsonSerializable;

/**
 * The File Value
 * @phpstan-type FileValue = array{
 *   name: string,
 *   type: string,
 *   tmp_name: string,
 *   error: int,
 *   size: int
 * }
 */
class File implements JsonSerializable {

    /** @var FileValue|null */
    private ?array $file = null;

    private string $filePath = "";


    /**
     * Creates a new File instance
     * @param string $key Optional.
     */
    public function __construct(string $key = "") {
        if (!isset($_FILES[$key])) {
            $this->filePath = $key;
            return;
        }

        /** @var FileValue */
        $file = $_FILES[$key];
        $this->file = $file;
    }


    /**
     * Returns true if the file is valid
     * @return bool
     */
    public function isValid(): bool {
        if ($this->file !== null) {
            return $this->file["error"] === UPLOAD_ERR_OK;
        }
        return $this->filePath !== "";
    }

    /**
     * Returns true if the file exists in the request
     * @return bool
     */
    public function hasFile(): bool {
        return $this->file !== null;
    }

    /**
     * Returns true if there was a size error in the upload
     * @return bool
     */
    public function hasSizeError(): bool {
        if ($this->file !== null) {
            return $this->file["error"] === UPLOAD_ERR_INI_SIZE;
        }
        return false;
    }

    /**
     * Returns true if the file has the given extension
     * @param string ...$extensions
     * @return bool
     */
    public function hasExtension(string ...$extensions): bool {
        $fileName = $this->getName();
        return Storage::hasExtension($fileName, ...$extensions);
    }

    /**
     * Returns true if the file is an image
     * @return bool
     */
    public function isImage(): bool {
        if ($this->hasFile()) {
            return FileType::isImage($this->getName());
        }
        return FileType::isImage($this->filePath);
    }

    /**
     * Returns true if the file is a valid image
     * @return bool
     */
    public function isValidImage(): bool {
        if ($this->hasFile()) {
            return Image::isValidType($this->getTmpName());
        }
        return FileType::isImage($this->filePath);
    }

    /**
     * Returns true if the file exists
     * @return bool
     */
    public function mediaExists(): bool {
        if ($this->hasFile()) {
            return false;
        }
        return MediaFile::exists($this->filePath);
    }



    /**
     * Returns the file name
     * @return string
     */
    public function getName(): string {
        if ($this->file !== null) {
            return $this->file["name"];
        }
        return $this->filePath;
    }

    /**
     * Returns the file temporal name
     * @return string
     */
    public function getTmpName(): string {
        if ($this->file !== null) {
            return $this->file["tmp_name"];
        }
        return $this->filePath;
    }

    /**
     * Returns the file type
     * @return string
     */
    public function getType(): string {
        if ($this->file !== null) {
            return $this->file["type"];
        }
        return "";
    }

    /**
     * Returns the file extension
     * @return string
     */
    public function getExtension(): string {
        return Storage::getExtension($this->getName());
    }

    /**
     * Returns the file as CURLFile
     * @return CURLFile|null
     */
    public function getCurlFile(): ?CURLFile {
        if ($this->file !== null) {
            return curl_file_create(
                $this->getTmpName(),
                $this->getType(),
                $this->getName(),
            );
        }
        return null;
    }

    /**
     * Returns the file as a value (file path or CURLFile)
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
     * Returns the file as a string (file path)
     * @return string
     */
    public function toString(): string {
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
     * Uploads the file to the given path
     * @param string $path
     * @param string $name Optional.
     * @return bool
     */
    public function upload(string $path, string $name = ""): bool {
        if ($this->file !== null) {
            return Storage::uploadFile($path, $name, $this->getTmpName());
        }
        return false;
    }

    /**
     * Deletes the file from the given path
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
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->filePath;
    }
}
