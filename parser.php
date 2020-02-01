<?php
include_once 'config.php';
/**
 * @param $url
 * @return bool|string|string[]|null
 */
function read_doc($url)
{
    $doc = file_get_contents($url);
    if (!empty($doc)) {
        file_put_contents(LOGS_DIRECTORY . "temp.doc", $doc);
        $document = new doc();
        $document->read(LOGS_DIRECTORY . "temp.doc");
        return $document->parse();
    } else
        return '';
}

/**
 * Class cfb
 */
class cfb
{
    /**
     *
     */
    const ENDOFCHAIN = 0xFFFFFFFE;
    /**
     *
     */
    const FREESECT = 0xFFFFFFFF;
    /**
     * @var string
     */
    protected $data = "";
    /**
     * @var int
     */
    protected $sectorShift = 9;
    /**
     * @var int
     */
    protected $miniSectorShift = 6;
    /**
     * @var int
     */
    protected $miniSectorCutoff = 4096;
    /**
     * @var array
     */
    protected $fatChains = array();
    /**
     * @var array
     */
    protected $fatEntries = array();
    /**
     * @var array
     */
    protected $miniFATChains = array();
    /**
     * @var string
     */
    protected $miniFAT = "";
    /**
     * @var int
     */
    private $version = 3;
    /**
     * @var bool
     */
    private $isLittleEndian = true;
    /**
     * @var int
     */
    private $cDir = 0;
    /**
     * @var int
     */
    private $fDir = 0;
    /**
     * @var int
     */
    private $cFAT = 0;
    /**
     * @var int
     */
    private $cMiniFAT = 0;
    /**
     * @var int
     */
    private $fMiniFAT = 0;
    /**
     * @var array
     */
    private $DIFAT = array();
    /**
     * @var int
     */
    private $cDIFAT = 0;
    /**
     * @var int
     */
    private $fDIFAT = 0;

    /**
     * @param $filename
     */
    public function read($filename)
    {
        $this->data = file_get_contents($filename);
    }

    /**
     * @return bool
     */
    public function parse()
    {
        $abSig = strtoupper(bin2hex(substr($this->data, 0, 8)));
        if ($abSig != "D0CF11E0A1B11AE1" && $abSig != "0E11FC0DD0CF11E0") {
            return false;
        }

        $this->readHeader();
        $this->readDIFAT();
        $this->readFATChains();
        $this->readMiniFATChains();
        $this->readDirectoryStructure();


        $reStreamID = $this->getStreamIdByName("Root Entry");
        if ($reStreamID === false) {
            return false;
        }
        $this->miniFAT = $this->getStreamById($reStreamID, true);

        unset($this->DIFAT);

    }

    /**
     *
     */
    private function readHeader()
    {
        $uByteOrder = strtoupper(bin2hex(substr($this->data, 0x1C, 2)));
        $this->isLittleEndian = $uByteOrder == "FEFF";

        $this->version = $this->getShort(0x1A);

        $this->sectorShift = $this->getShort(0x1E);
        $this->miniSectorShift = $this->getShort(0x20);
        $this->miniSectorCutoff = $this->getLong(0x38);

        if ($this->version == 4)
            $this->cDir = $this->getLong(0x28);
        $this->fDir = $this->getLong(0x30);

        $this->cFAT = $this->getLong(0x2C);

        $this->cMiniFAT = $this->getLong(0x40);
        $this->fMiniFAT = $this->getLong(0x3C);

        $this->cDIFAT = $this->getLong(0x48);
        $this->fDIFAT = $this->getLong(0x44);
    }

    /**
     * @param $from
     * @param null $data
     * @return float|int
     */
    protected function getShort($from, $data = null)
    {
        return $this->getSomeBytes($data, $from, 2);
    }

    /**
     * @param $data
     * @param $from
     * @param $count
     * @return float|int
     */
    protected function getSomeBytes($data, $from, $count)
    {
        if ($data === null)
            $data = $this->data;

        $string = substr($data, $from, $count);
        if ($this->isLittleEndian)
            $string = strrev($string);

        return hexdec(bin2hex($string));
    }

    /**
     * @param $from
     * @param null $data
     * @return float|int
     */
    protected function getLong($from, $data = null)
    {
        return $this->getSomeBytes($data, $from, 4);
    }

    /**
     *
     */
    private function readDIFAT()
    {
        $this->DIFAT = array();
        for ($i = 0; $i < 109; $i++)
            $this->DIFAT[$i] = $this->getLong(0x4C + $i * 4);

        if ($this->fDIFAT != self::ENDOFCHAIN) {
            $size = 1 << $this->sectorShift;
            $from = $this->fDIFAT;
            $j = 0;

            do {
                $start = ($from + 1) << $this->sectorShift;
                for ($i = 0; $i < ($size - 4); $i += 4)
                    $this->DIFAT[] = $this->getLong($start + $i);
                $from = $this->getLong($start + $i);
            } while ($from != self::ENDOFCHAIN && ++$j < $this->cDIFAT);
        }

        while ($this->DIFAT[count($this->DIFAT) - 1] == self::FREESECT)
            array_pop($this->DIFAT);
    }

    /**
     *
     */
    private function readFATChains()
    {
        $size = 1 << $this->sectorShift;
        $this->fatChains = array();

        for ($i = 0; $i < count($this->DIFAT); $i++) {
            $from = ($this->DIFAT[$i] + 1) << $this->sectorShift;
            for ($j = 0; $j < $size; $j += 4)
                $this->fatChains[] = $this->getLong($from + $j);
        }
    }

    /**
     *
     */
    private function readMiniFATChains()
    {
        $size = 1 << $this->sectorShift;
        $this->miniFATChains = array();

        $from = $this->fMiniFAT;
        while ($from != self::ENDOFCHAIN) {
            $start = ($from + 1) << $this->sectorShift;
            for ($i = 0; $i < $size; $i += 4)
                $this->miniFATChains[] = $this->getLong($start + $i);
            $from = isset($this->fatChains[$from]) ? $this->fatChains[$from] : self::ENDOFCHAIN;
        }
    }

    /**
     *
     */
    private function readDirectoryStructure()
    {
        $from = $this->fDir;
        $size = 1 << $this->sectorShift;
        $this->fatEntries = array();
        do {
            $start = ($from + 1) << $this->sectorShift;
            for ($i = 0; $i < $size; $i += 128) {
                $entry = substr($this->data, $start + $i, 128);
                $this->fatEntries[] = array(
                    "name" => $this->utf16_to_ansi(substr($entry, 0, $this->getShort(0x40, $entry))),
                    "type" => ord($entry[0x42]),
                    "color" => ord($entry[0x43]),
                    "left" => $this->getLong(0x44, $entry),
                    "right" => $this->getLong(0x48, $entry),
                    "child" => $this->getLong(0x4C, $entry),
                    "start" => $this->getLong(0x74, $entry),
                    "size" => $this->getSomeBytes($entry, 0x78, 8),
                );
            }

            $from = isset($this->fatChains[$from]) ? $this->fatChains[$from] : self::ENDOFCHAIN;
        } while ($from != self::ENDOFCHAIN);

        while ($this->fatEntries[count($this->fatEntries) - 1]["type"] == 0)
            array_pop($this->fatEntries);

        #dump($this->fatEntries, false);
    }

    /**
     * @param $in
     * @return string
     */
    private function utf16_to_ansi($in)
    {
        $out = "";
        for ($i = 0; $i < strlen($in); $i += 2)
            $out .= chr($this->getShort($i, $in));
        return trim($out);
    }

    /**
     * @param $name
     * @param int $from
     * @return bool|int
     */
    public function getStreamIdByName($name, $from = 0)
    {
        for ($i = $from; $i < count($this->fatEntries); $i++) {
            if ($this->fatEntries[$i]["name"] == $name)
                return $i;
        }
        return false;
    }

    /**
     * @param $id
     * @param bool $isRoot
     * @return bool|string
     */
    public function getStreamById($id, $isRoot = false)
    {
        $entry = $this->fatEntries[$id];
        $from = $entry["start"];
        $size = $entry["size"];


        $stream = "";
        if ($size < $this->miniSectorCutoff && !$isRoot) {
            $ssize = 1 << $this->miniSectorShift;

            do {
                $start = $from << $this->miniSectorShift;
                $stream .= substr($this->miniFAT, $start, $ssize);
                $from = isset($this->miniFATChains[$from]) ? $this->miniFATChains[$from] : self::ENDOFCHAIN;
            } while ($from != self::ENDOFCHAIN);
        } else {
            $ssize = 1 << $this->sectorShift;

            do {
                $start = ($from + 1) << $this->sectorShift;
                $stream .= substr($this->data, $start, $ssize);
                #if (!isset($this->fatChains[$from]))
                #	$from = self::ENDOFCHAIN;
                #elseif ($from != self::ENDOFCHAIN && $from != self::FREESECT)
                #	$from = $this->fatChains[$from];
                $from = isset($this->fatChains[$from]) ? $this->fatChains[$from] : self::ENDOFCHAIN;
            } while ($from != self::ENDOFCHAIN);
        }
        return substr($stream, 0, $size);
    }

    /**
     * @param $in
     * @param bool $check
     * @return mixed|string
     */
    protected function unicode_to_utf8($in, $check = false)
    {
        $out = "";
        if ($check && strpos($in, chr(0)) !== 1) {
            while (($i = strpos($in, chr(0x13))) !== false) {
                $j = strpos($in, chr(0x15), $i + 1);
                if ($j === false)
                    break;

                $in = substr_replace($in, "", $i, $j - $i);
            }
            for ($i = 0; $i < strlen($in); $i++) {
                if (ord($in[$i]) >= 32) {
                } elseif ($in[$i] == ' ' || $in[$i] == '\n') {
                } else
                    $in = substr_replace($in, "", $i, 1);
            }
            $in = str_replace(chr(0), "", $in);

            return $in;
        } elseif ($check) {
            while (($i = strpos($in, chr(0x13) . chr(0))) !== false) {
                $j = strpos($in, chr(0x15) . chr(0), $i + 1);
                if ($j === false)
                    break;

                $in = substr_replace($in, "", $i, $j - $i);
            }
            $in = str_replace(chr(0) . chr(0), "", $in);
        }

        $skip = false;
        for ($i = 0; $i < strlen($in); $i += 2) {
            $cd = substr($in, $i, 2);
            if ($skip) {
                if (ord($cd[1]) == 0x15 || ord($cd[0]) == 0x15)
                    $skip = false;
                continue;
            }

            if (ord($cd[1]) == 0) {
                if (ord($cd[0]) >= 32)
                    $out .= $cd[0];
                elseif ($cd[0] == ' ' || $cd[0] == '\n')
                    $out .= $cd[0];
                elseif (ord($cd[0]) == 0x13)
                    $skip = true;
                else {
                    continue;
                    switch (ord($cd[0])) {
                        case 0x0D:
                        case 0x07:
                            $out .= "\n";
                            break;
                        case 0x08:
                        case 0x01:
                            $out .= "";
                            break;
                        case 0x13:
                            $out .= "HYPER13";
                            break;
                        case 0x14:
                            $out .= "HYPER14";
                            break;
                        case 0x15:
                            $out .= "HYPER15";
                            break;
                        default:
                            $out .= " ";
                            break;
                    }
                }
            } else {
                if (ord($cd[1]) == 0x13) {
                    echo "@";
                    $skip = true;
                    continue;
                }
                $out .= "&#x" . sprintf("%04x", $this->getShort(0, $cd)) . ";";
            }
        }

        return $out;
    }
}

/**
 * Class doc
 */
class doc extends cfb
{
    /**
     * @return bool|string|string[]|null
     */
    public function parse()
    {
        parent::parse();

        $wdStreamID = $this->getStreamIdByName("WordDocument");
        if ($wdStreamID === false) {
            return false;
        }

        $wdStream = $this->getStreamById($wdStreamID);

        $bytes = $this->getShort(0x000A, $wdStream);
        $fWhichTblStm = ($bytes & 0x0200) == 0x0200;

        $fcClx = $this->getLong(0x01A2, $wdStream);
        $lcbClx = $this->getLong(0x01A6, $wdStream);

        $ccpText = $this->getLong(0x004C, $wdStream);
        $ccpFtn = $this->getLong(0x0050, $wdStream);
        $ccpHdd = $this->getLong(0x0054, $wdStream);
        $ccpMcr = $this->getLong(0x0058, $wdStream);
        $ccpAtn = $this->getLong(0x005C, $wdStream);
        $ccpEdn = $this->getLong(0x0060, $wdStream);
        $ccpTxbx = $this->getLong(0x0064, $wdStream);
        $ccpHdrTxbx = $this->getLong(0x0068, $wdStream);

        $lastCP = $ccpFtn + $ccpHdd + $ccpMcr + $ccpAtn + $ccpEdn + $ccpTxbx + $ccpHdrTxbx;
        $lastCP += ($lastCP != 0) + $ccpText;

        $tStreamID = $this->getStreamIdByName(intval($fWhichTblStm) . "Table");
        if ($tStreamID === false) {
            return false;
        }

        $tStream = $this->getStreamById($tStreamID);
        $clx = substr($tStream, $fcClx, $lcbClx);

        $lcbPieceTable = 0;
        $pieceTable = "";


        $from = 0;
        while (($i = strpos($clx, chr(0x02), $from)) !== false) {
            $lcbPieceTable = $this->getLong($i + 1, $clx);
            $pieceTable = substr($clx, $i + 5);

            if (strlen($pieceTable) != $lcbPieceTable) {
                $from = $i + 1;
                continue;
            }
            break;
        }

        $cp = array();
        $i = 0;
        while (($cp[] = $this->getLong($i, $pieceTable)) != $lastCP)
            $i += 4;
        $pcd = str_split(substr($pieceTable, $i + 4), 8);

        $text = "";
        for ($i = 0; $i < count($pcd); $i++) {
            $fcValue = $this->getLong(2, $pcd[$i]);
            $isANSI = ($fcValue & 0x40000000) == 0x40000000;
            $fc = $fcValue & 0x3FFFFFFF;

            $lcb = $cp[$i + 1] - $cp[$i];
            if (!$isANSI)
                $lcb *= 2;
            else
                $fc /= 2;

            $part = substr($wdStream, $fc, $lcb);
            if (!$isANSI)
                $part = $this->unicode_to_utf8($part);

            $text .= $part;
        }

        $text = preg_replace("/HYPER13 *(INCLUDEPICTURE|HTMLCONTROL)(.*)HYPER15/iU", "", $text);
        $text = preg_replace("/HYPER13(.*)HYPER14(.*)HYPER15/iU", "$2", $text);
        return $text;
    }

    /**
     * @param $in
     * @param bool $check
     * @return mixed|string
     */
    protected function unicode_to_utf8($in, $check = false)
    {
        $out = "";
        for ($i = 0; $i < strlen($in); $i += 2) {
            $cd = substr($in, $i, 2);

            if (ord($cd[1]) == 0) {
                if (ord($cd[0]) >= 32)
                    $out .= $cd[0];

                switch (ord($cd[0])) {
                    case 0x0D:
                    case 0x07:
                        $out .= "\n";
                        break;
                    case 0x08:
                    case 0x01:
                        $out .= "";
                        break;
                    case 0x13:
                        $out .= "HYPER13";
                        break;
                    case 0x14:
                        $out .= "HYPER14";
                        break;
                    case 0x15:
                        $out .= "HYPER15";
                        break;
                }
            } else                $out .= html_entity_decode("&#x" . sprintf("%04x", $this->getShort(0, $cd)) . ";");
        }

        return $out;
    }
}
