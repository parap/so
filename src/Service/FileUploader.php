<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file, $oldName)
    {
        if (!file_exists($this->targetDirectory)) {
            throw new FileException($this->targetDirectory. ' does not exist');
        }

        if (!is_writable($this->targetDirectory)) {
            throw new FileException($this->targetDirectory. ' is not writable');
        }

        // this is needed to safely include the file name as part of the URL
        $newFilename = uniqid() . '.' . $file->guessExtension();

        // Delete old avatar file, if exists
        $oldFileName = $this->targetDirectory . '/' . $oldName;
        if (file_exists($oldFileName)) {
            unlink($oldFileName);
        }

        // Move the file to the directory where avatars are stored
        $file->move(
            $this->targetDirectory,
            $newFilename
        );

        return $newFilename;
    }
}