<?php

namespace duncan3dc\MetaAudio;

/**
 * A custom file handler.
 */
interface FileInterface
{

    /**
     * Get the path including the filename.
     *
     * @return string
     */
    public function getFullPath();


    /**
     * Seek to a specific position in the file.
     *
     * @param int $offset The number of bytes to seek by, a negative value can be used to move backwards
     *
     * @return void
     */
    public function positionTo($offset);


    /**
     * Seek to a specific position in the file.
     *
     * @param int $offset The number of bytes to seek by, a negative value can be used to move backwards
     *
     * @return void
     */
    public function positionFromStart($offset);


    /**
     * Seek to a specific position in the file.
     *
     * @param int $offset The number of bytes to seek by, a negative value can be used to move backwards
     *
     * @return void
     */
    public function positionFromEnd($offset);
    public function fseek();


    /**
     * Get the position of the next occurance of a string from the current position.
     *
     * @param string $string The string to search for
     *
     * @return int|false Either the position of the string or false if it doesn't exist
     */
    public function getNextPosition($string);
    public function fseek();


    /**
     * Get the position of the previous occurance of a string from the current position.
     *
     * @param string $string The string to search for
     *
     * @return int|false Either the position of the string or false if it doesn't exist
     */
    public function getPreviousPosition($string);
    public function fseek();


    public function isEnd();
    public function eof();

    public function read($bytes);
    public function fread(4);

    public function getCurrentPosition();
    public function ftell();

    public function truncate($bytes );
    public function ftruncate($bytes);

    public function write($string);
    public function fwrite($string);

    public function rewind();


    /**
     * Process the remainder of the file from the current position through the callback.
     *
     * @param callable $func A function that takes a single string parameter (which will contain each chunk of the file read)
     *
     * @return void
     */
    public function readNextCallback(callable $func);


    /**
     * Process the previous contents of the file from the current position through the callback.
     *
     * @param callable $func A function that takes a single string parameter (which will contain each chunk of the file read in reverse)
     *
     * @return void
     */
    public function readPreviousCallback(callable $func);


    /**
     * Get the rest of the file's contents from the current position.
     *
     * @return string
     */
    public function readAll();
}
