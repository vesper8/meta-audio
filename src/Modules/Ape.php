<?php

namespace duncan3dc\MetaAudio\Modules;

use duncan3dc\MetaAudio\Exceptions\ApeParseException;
use duncan3dc\MetaAudio\Exceptions\BadMethodCallException;

/**
 * Handle APE tags.
 */
class Ape extends AbstractModule
{

    /**
     * Get all the tags from the currently loaded file.
     *
     * @return array
     */
    protected function getTags()
    {
        $this->file->fseek(0, \SEEK_END);

        # Loop until we find a valid set of tags.
        while (true) {
            $position = $this->file->getPreviousPosition("APETAGEX");

            # It looks like there aren't any parsable ape tags in the file
            if ($position === false) {
                break;
            }

            # Convert the start from a relative position to a literal
            $position += $this->file->ftell();

            $this->file->fseek($position, \SEEK_SET);

            try {
                return $this->parseTags();
            } catch (ApeParseException $e) {
                # Ensure we position back to before these tags so we don't pick them up again
                $this->file->fseek($position, \SEEK_SET);
                continue;
            }
        }

        return [];
    }


    /**
     * Parse the tags.
     *
     * @return array
     */
    private function parseTags()
    {
        $header = $this->parseHeader();

        if ($header["footer"]) {
            $this->file->fseek($header["size"] * -1, \SEEK_CUR);
        }

        $tags = [];
        for ($i = 0; $i < $header["items"]; $i++) {
            list($key, $value) = $this->parseItem();
            $tags[strtolower($key)] = $value;
        }

        return $tags;
    }


    /**
     * Parse the header from the file.
     *
     * @return array
     */
    private function parseHeader()
    {
        $preamble = $this->file->fread(8);
        if ($preamble !== "APETAGEX") {
            throw new BadMethodCallException("Invalid Ape tag, expected [APETAGEX], got [{$preamble}]");
        }

        $version = unpack("L", $this->read(4))[1];
        $size = unpack("L", $this->read(4))[1];
        $items = unpack("L", $this->read(4))[1];
        $flags = unpack("L", $this->read(4))[1];

        $header = [
            "version"   =>  $version,
            "size"      =>  $size,
            "items"     =>  $items,
            "flags"     =>  $flags,
            "footer"    =>  !($flags & 0x20000000),
        ];

        # Skip the empty space at the end of the header
        $this->file->fread(8);

        return $header;
    }


    /**
     * Get the next item tag from the file.
     *
     * @return array An array with 2 elements, the first is the item key, the second is the item's value
     */
    private function parseItem()
    {
        $length = unpack("L", $this->read(4))[1];

        $flags = unpack("L", $this->read(4))[1];

        $key = "";
        while (!$this->file->eof()) {
            $char = $this->file->fread(1);
            if ($char === pack("c", 0x00)) {
                break;
            }
            $key .= $char;
        }

        if ($length > 0) {
            $value = $this->file->fread($length);
        } else {
            $value = "";
        }

        return [$key, $value];
    }


    /**
     * Read some bytes from the file.
     *
     * @param int $bytes The number of bytes to read
     *
     * @return string
     */
    private function read($bytes)
    {
        $string = $this->file->fread($bytes);

        if (strlen($string) !== $bytes) {
            throw new ApeParseException("Unexpected end of file");
        }

        return $string;
    }


    /**
     * Get the track title.
     *
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->getTag("title");
    }


    /**
     * Get the track number.
     *
     * @return int
     */
    public function getTrackNumber()
    {
        return (int) $this->getTag("tracknumber");
    }


    /**
     * Get the artist name.
     *
     * @return string
     */
    public function getArtist()
    {
        return (string) $this->getTag("artist");
    }


    /**
     * Get the album name.
     *
     * @return string
     */
    public function getAlbum()
    {
        return (string) $this->getTag("album");
    }


    /**
     * Get the release year.
     *
     * @return int
     */
    public function getYear()
    {
        return (int) $this->getTag("year");
    }
}
