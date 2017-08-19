<?php

namespace duncan3dc\MetaAudio;

/**
 * A custom file handler.
 */
class File implements FileInterface
{
    const CALLBACK_STOP = 408;

    const BUFFER_SIZE = 32768;

    /**
     * SplFileObject $file The underlying file instance.
     */
    private $file;


    /**
     * Create a new file object.
     *
     * @param string $filename The filename to open
     */
    public function __construct($filename)
    {
        $this->file = new \SplFileObject($filename, "r+");
    }


    /**
     * Get the path including the filename.
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->file->getPath() . "/" . $this->file->getFilename();
    }


    /**
     * Process the remainder of the file from the current position through the callback.
     *
     * @param callable $func A function that takes a single string parameter (which will contain each chunk of the file read)
     *
     * @return void
     */
    public function readNextCallback(callable $func)
    {
        while (!$this->file->eof()) {
            $data = $this->file->fread(self::BUFFER_SIZE);

            if ($data === false) {
                throw new Exception("Failed to read from the file");
            }

            $result = $func($data);

            # If the callback has finished reading and isn't interested in the rest then stop here
            if ($result === self::CALLBACK_STOP) {
                break;
            }
        }
    }


    /**
     * Process the previous contents of the file from the current position through the callback.
     *
     * @param callable $func A function that takes a single string parameter (which will contain each chunk of the file read in reverse)
     *
     * @return void
     */
    public function readPreviousCallback(callable $func)
    {
        while ($this->file->ftell() > 0) {
            $length = self::BUFFER_SIZE;
            if ($this->file->ftell() < $length) {
                $length = $this->file->ftell();
            }

            # Position back to the start of the chunk we want to read
            $this->file->fseek($length * -1, \SEEK_CUR);

            # Read the chunk
            $data = $this->file->fread($length);

            # Position back to the start of the chunk we've just read
            $this->file->fseek($length * -1, \SEEK_CUR);

            if ($data === false) {
                throw new Exception("Failed to read from the file");
            }

            $func($data);
        }
    }


    /**
     * Get the position of the next occurance of a string from the current position.
     *
     * @param string $string The string to search for
     *
     * @return int|false Either the position of the string or false if it doesn't exist
     */
    public function getNextPosition($string)
    {
        $stringPosition = false;

        $startingPosition = $this->file->ftell();

        $this->readNextCallback(function ($data) use (&$stringPosition, $string, $startingPosition) {

            $position = strpos($data, $string);
            if ($position === false) {
                if (!$this->file->eof()) {
                    $length = strlen($string) - 1;
                    $this->file->fseek($length * -1, \SEEK_CUR);
                }
                return;
            }

            # Calculate the position of the string as an offset of the starting position
            $stringPosition = $this->file->ftell() - $startingPosition - strlen($data) + $position;

            # Tell the readNextCallback() that we're done reading
            return self::CALLBACK_STOP;
        });

        # Position back to where we were before finding the string
        $this->file->fseek($startingPosition, \SEEK_SET);

        return $stringPosition;
    }


    /**
     * Get the position of the previous occurance of a string from the current position.
     *
     * @param string $string The string to search for
     *
     * @return int|false Either the position of the string or false if it doesn't exist
     */
    public function getPreviousPosition($string)
    {
        $stringPosition = false;

        $startingPosition = $this->file->ftell();

        $this->readPreviousCallback(function ($data) use (&$stringPosition, $string, $startingPosition) {

            $position = strrpos($data, $string);
            if ($position === false) {
                if ($this->file->ftell() > 0) {
                    $length = strlen($string) - 1;
                    $this->file->fseek($length, \SEEK_CUR);
                }
                return;
            }

            # Calculate the position of the string as an offset of the starting position
            $stringPosition = $this->file->ftell() - $startingPosition + $position;

            # Tell the readNextCallback() that we're done reading
            return self::CALLBACK_STOP;
        });

        # Position back to where we were before finding the string
        $this->file->fseek($startingPosition, \SEEK_SET);

        return $stringPosition;
    }


    /**
     * Get the rest of the file's contents from the current position.
     *
     * @return string
     */
    public function readAll()
    {
        $contents = "";

        $this->readNextCallback(function ($data) use (&$contents) {
            $contents .= $data;
        });

        return $contents;
    }
}
