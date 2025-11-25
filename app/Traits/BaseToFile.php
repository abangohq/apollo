<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ParameterBag;

trait BaseToFile
{
    /**
     * Helper method to get the body parameters bag.
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    private function bodyParametersBag(): ParameterBag
    {
        return $this->request;
    }

    /**
     * Helper method to get the uploaded files bag.
     *
     * @return FileBag
     */
    private function uploadFilesBag(): FileBag
    {
        return $this->files;
    }

    /**
     * Set file extension
     * 
     * @return string
     */
    private function setExtension($tempFileName, $mime)
    {
        return $tempFileName . '.' . explode('/', $mime)[1];
    }

    /**
     * Set file upload instance
     * 
     * @return UploadedFile
     */
    private function fileUploaded($tempFilePath, $tempFilename)
    {
        $tempFile = new UploadedFile($tempFilePath, $tempFilename, null, null, true);

        $filename = $this->setExtension($tempFilename, $tempFile->getMimeType());

        return new UploadedFile($tempFilePath, $filename, null, null, true);
    }

    /**
     * Pulls the Base64 contents for each image key and creates
     * an UploadedFile instance from it and sets it on the
     * request.
     *
     * @return void
     */
    protected function convertFiles()
    {
        $flattened = Arr::dot($this->base64FileKeys());

        Collection::make($flattened)->each(function ($key) {
            $base64Contents = $this->input($key);

            if (is_array($base64Contents)) {
                collect($base64Contents)->each(function ($base64Content, $index) use ($key) {
                    $tempFilename = bin2hex(random_bytes(10));

                    if (!$base64Content) {
                        return;
                    }

                    // Generate a temporary path to store the Base64 contents
                    $tempFilePath = tempnam(sys_get_temp_dir(), $tempFilename);

                    $this->storeBase64Content($base64Content, $tempFilePath);

                    $uploadedFile = $this->fileUploaded($tempFilePath, $tempFilename);

                    $this->replaceRequestBody($key, $index);
                    $this->replaceUploadedFiles($key, $uploadedFile, $index);
                });
            }

            if (!is_array($base64Contents)) {
                $tempFilename = bin2hex(random_bytes(10));

                if (!$base64Contents) {
                    return;
                }

                // Generate a temporary path to store the Base64 contents
                $tempFilePath = tempnam(sys_get_temp_dir(), $tempFilename);

                $this->storeBase64Content($base64Contents, $tempFilePath);

                $uploadedFile = $this->fileUploaded($tempFilePath, $tempFilename);

                $this->replaceRequestBody($key);
                $this->replaceUploadedFiles($key, $uploadedFile);
            }
        });
    }

    /**
     * Store the contents using a stream, or by decoding manually
     */
    private function storeBase64Content($base64Content, $tempFilePath)
    {
        if (Str::startsWith($base64Content, 'data:') && count(explode(',', $base64Content)) > 1) {
            $source = fopen($base64Content, 'r');
            $destination = fopen($tempFilePath, 'w');

            stream_copy_to_stream($source, $destination);

            fclose($source);
            fclose($destination);
        } else {
            file_put_contents($tempFilePath, base64_decode($base64Content, true));
        }
    }

    /**
     * Replace the request body using params bag
     */
    private function replaceRequestBody($key, $index = null)
    {
        $body = $this->bodyParametersBag()->all();
        Arr::forget($body, is_null($index) ? $key : "$key.$index");
        $this->bodyParametersBag()->replace($body);
    }

    /**
     * Replace the uploaded file body using params bag
     */
    private function replaceUploadedFiles($key, $uploadedFile, $index = null)
    {
        $files = $this->uploadFilesBag()->all();
        Arr::set($files, is_null($index) ? $key : "$key.$index", $uploadedFile);
        $this->uploadFilesBag()->replace($files);
    }
}
