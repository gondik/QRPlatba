<?php

namespace Defr\QRPlatba;

use Endroid\QrCode\QrCode;

/**
 * Knihovna pro generování QR plateb v PHP.
 *
 * @package Defr\QRPlatba
 * @see https://raw.githubusercontent.com/snoblucha/QRPlatba/master/QRPlatba.php
 */
class QRPlatba
{

    /**
     * Verze QR formátu QR Platby
     */
    const VERSION = '1.0';

    /**
     * @var array
     */
    private $keys = [
        'ACC'     => null, // Max. 46 - znaků IBAN, BIC Identifikace protistrany !povinny
        'ALT-ACC' => null, // Max. 93 - znaků Seznam alternativnich uctu. odddeleny carkou,
        'AM'      => null, //Max. 10 znaků - Desetinné číslo Výše částky platby.
        'CC'      => 'CZK', // Právě 3 znaky - Měna platby.
        'DT'      => null, // Právě 8 znaků - Datum splatnosti YYYYMMDD.
        'MSG'     => null, // Max. 60 znaků - Zpráva pro příjemce.
        'X-VS'    => null, // Max. 10 znaků - Celé číslo - Variabilní symbol
        'X-SS'    => null, // Max. 10 znaků - Celé číslo - Specifický symbol
        'X-KS'    => null, // Max. 10 znaků - Celé číslo - Konstantní symbol
        'RF'      => null, // Max. 16 znaků - Identifikátor platby pro příjemce.
        'RN'      => null, // Max. 35 znaků - Jméno příjemce.
        'PT'      => null, // Právě 3 znaky - Typ platby.
        'CRC32'   => null, // Právě 8 znaků - Kontrolní součet - HEX.
        'NT'      => null, // Právě 1 znak P|E - Identifikace kanálu pro zaslání notifikace výstavci platby.
        'NTA'     => null, //Max. 320 znaků - Telefonní číslo v mezinárodním nebo lokálním vyjádření nebo E-mailová adresa
        'X-PER'   => null, // Max. 2 znaky -  Celé číslo - Počet dní, po které se má provádět pokus o opětovné provedení neúspěšné platby
        'X-ID'    => null, // Max. 20 znaků. -  Identifikátor platby na straně příkazce. Jedná se o interní ID, jehož použití a interpretace závisí na bance příkazce.
        'X-URL'   => null, // Max. 140 znaků. -  URL, které je možno využít pro vlastní potřebu
    ];

    /**
     * Kontruktor nové platby.
     *
     * @param null $account
     * @param null $amount
     * @param null $variable
     */
    public function __construct($account = null, $amount = null, $variable = null)
    {
        if ($account) {
            $this->setAccount($account);
        }
        if ($amount) {
            $this->setAmount($amount);
        }
        if ($variable) {
            $this->setVariableSymbol($variable);
        }
    }

    /**
     * Statický konstruktor nové platby.
     *
     * @param null $account
     * @param null $amount
     * @param null $variable
     * @return QRPlatba
     */
    public static function create($account = null, $amount = null, $variable = null)
    {
        return new self($account, $amount, $variable);
    }

    /**
     * Nastavení čísla účtu ve formátu 12-3456789012/0100.
     *
     * @param $account
     * @return $this
     */
    public function setAccount($account)
    {
        $this->keys['ACC'] = $this->accountToIban($account);

        return $this;
    }

    /**
     * Nastavení částky.
     *
     * @param $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->keys['AM'] = sprintf("%.2f", $amount);

        return $this;
    }

    /**
     * Nastavení variabilního symbolu.
     *
     * @param $vs
     * @return $this
     */
    public function setVariableSymbol($vs)
    {
        $this->keys['X-VS'] = $vs;

        return $this;
    }

    /**
     * Nastavení konstatního symbolu.
     *
     * @param $cs
     * @return $this
     */
    public function setConstantSymbol($cs)
    {
        $this->keys['X-KS'] = $cs;

        return $this;
    }

    /**
     * Nastavení specifického symbolu.
     *
     * @param $ss
     * @return $this
     * @throws QRPlatbaException
     */
    public function setSpecificSymbol($ss)
    {
        if (strlen($ss) > 10) {
            throw new QRPlatbaException();
        }
        $this->keys['X-SS'] = $ss;

        return $this;
    }

    /**
     * Nastavení zprávy pro příjemce. Z řetězce bude odstraněna diaktirika.
     *
     * @param $msg
     * @return $this
     */
    public function setMessage($msg)
    {
        $this->keys['MSG'] = substr($this->stripDiacritics($msg), 0, 60);

        return $this;
    }

    /**
     * Nastavení data úhrady.
     *
     * @param \DateTime $date
     * @return $this
     */
    public function setDueDate(\DateTime $date)
    {
        $this->keys['DT'] = $date->format('Ymd');

        return $this;
    }

    /**
     * Metoda vrátí QR Platbu jako textový řetězec.
     *
     * @return string
     */
    public function __toString()
    {

        $chunks = array('SPD', self::VERSION);
        foreach ($this->keys as $key => $value) {
            if ($value === null) {
                continue;
            }
            $chunks[] = $key . ":" . $value;
        }

        return implode('*', $chunks);
    }

    /**
     * Metoda vrátí QR kód jako HTML tag, případně jako data-uri.
     *
     * @param bool $htmlTag
     * @param int $size
     * @param int $padding
     * @return string
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     */
    public function getQRCodeImage($htmlTag = true, $size = 300, $padding = 10)
    {
        $qrCode = $this->getQRCodeInstance($size, $padding);
        $data = $qrCode->getDataUri();

        return $htmlTag
            ? sprintf('<img src="%s" alt="QR Platba">', $data)
            : $data;
    }

    /**
     * Uložení QR kódu do souboru.
     *
     * @param null|string $filename File name of the QR Code
     * @param null|string $format Format of the file (png, jpeg, jpg, gif, wbmp)
     * @param int $size
     * @param int $padding
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     * @return QRPlatba
     */
    public function saveQRCodeImage($filename = null, $format = 'png', $size = 300, $padding = 10)
    {
        $qrCode = $this->getQRCodeInstance($size, $padding);
        $qrCode->render($filename, $format);

        return $this;
    }

    /**
     * Instance třídy QrCode pro libovolné úpravy (barevnost, atd.).
     *
     * @param int $size
     * @param int $padding
     * @return QrCode
     */
    public function getQRCodeInstance($size = 300, $padding = 10)
    {
        $qrCode = new QrCode();
        $qrCode
            ->setText((string)$this)
            ->setSize($size)
            ->setPadding($padding)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

        return $qrCode;
    }

    /**
     * Převedení čísla účtu na formát IBAN.
     *
     * @param $accountNumber
     * @return string
     */
    public static function accountToIban($accountNumber)
    {
        $accountNumber = explode('/', $accountNumber);
        $bank = $accountNumber[1];
        $pre = 0;
        $acc = 0;
        if (strpos($accountNumber[0], '-') === false) {
            $acc = $accountNumber[0];
        } else {
            list($pre, $acc) = explode('-', $accountNumber[0]);
        }

        $accountPart = sprintf("%06d%010d", $pre, $acc);
        $iban = 'CZ00' . $bank . $accountPart;

        $alfa = "A B C D E F G H I J K L M N O P Q R S T U V W X Y Z";
        $alfa = explode(" ", $alfa);
        for ($i = 1; $i < 27; $i++) {
            $alfa_replace[] = $i + 9;
        }
        $controlegetal = str_replace($alfa, $alfa_replace, substr($iban, 4, strlen($iban) - 4) . substr($iban, 0, 2) . "00");
        $controlegetal = 98 - (int)bcmod($controlegetal, 97);
        $iban = sprintf("CZ%02d%04d%06d%010d", $controlegetal, $bank, $pre, $acc);

        return $iban;
    }

    /**
     * Odstranění diaktitiky.
     *
     * @param $string
     * @return mixed
     */
    private function stripDiacritics($string)
    {
        $string = str_replace(
            array(
                'ě',
                'š',
                'č',
                'ř',
                'ž',
                'ý',
                'á',
                'í',
                'é',
                'ú',
                'ů',
                'ó',
                'ť',
                'ď',
                'ľ',
                'ň',
                'ŕ',
                'â',
                'ă',
                'ä',
                'ĺ',
                'ć',
                'ç',
                'ę',
                'ë',
                'î',
                'ń',
                'ô',
                'ő',
                'ö',
                'ů',
                'ű',
                'ü',
            ),
            array(
                'e',
                's',
                'c',
                'r',
                'z',
                'y',
                'a',
                'i',
                'e',
                'u',
                'u',
                'o',
                't',
                'd',
                'l',
                'n',
                'a',
                'a',
                'a',
                'a',
                'a',
                'a',
                'c',
                'e',
                'e',
                'i',
                'n',
                'o',
                'o',
                'o',
                'u',
                'u',
                'u',
            ),
            $string
        );
        $string = str_replace(
            array(
                'Ě',
                'Š',
                'Č',
                'Ř',
                'Ž',
                'Ý',
                'Á',
                'Í',
                'É',
                'Ú',
                'Ů',
                'Ó',
                'Ť',
                'Ď',
                'Ľ',
                'Ň',
                'Ä',
                'Ć',
                'Ë',
                'Ö',
                'Ü',
            ),
            array(
                'E',
                'S',
                'C',
                'R',
                'Z',
                'Y',
                'A',
                'I',
                'E',
                'U',
                'U',
                'O',
                'T',
                'D',
                'L',
                'N',
                'A',
                'C',
                'E',
                'O',
                'U',
            ),
            $string
        );

        return $string;
    }

}