<?php

namespace ParpV1\MainBundle\Api\Type;

use JMS\Serializer\Annotation as JMS;

/**
 * Typ danych zaweirający uprawnienia do nadania w LSI1420.
 *
 * @JMS\ExclusionPolicy("all")
 */
class UprawnienieLsi1420 implements \JsonSerializable
{
    /**
     * @var string
     * 
     * @JMS\Expose
     * @JMS\Type("string")
     */
    protected $wniosek;

    /**
     * @var string
     * 
     * @JMS\Expose
     * @JMS\Type("string")
     */
    protected $uzytkownik;

    /**
     * @var string
     * 
     * @JMS\Expose
     * @JMS\Type("string")
     */
    protected $uprawnienie;

    /**
     * @var string
     * 
     * @JMS\Expose
     * @JMS\Type("string")
     */
    protected $dzialanie;

    /**
     * @var string
     * 
     * @JMS\Expose
     * @JMS\Type("string")
     */
    public $numerNaboru;

    /**
     * Konstruktor.
     *
     * @param string $wniosek
     * @param string $uzytkownik
     * @param string $uprawnienie
     * @param string $dzialanie
     * @param string $numerNaboru
     */
    public function __construct($wniosek, $uzytkownik, $uprawnienie, $dzialanie, $numerNaboru)
    {
        $this->wniosek = $this->sanitize($wniosek);
        $this->uzytkownik = $this->sanitize($uzytkownik);
        $this->uprawnienie = $this->sanitize($uprawnienie);
        $this->dzialanie = $this->sanitize($dzialanie);
        $this->numerNaboru = $this->sanitize($numerNaboru);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'wniosek' => $this->wniosek,
            'uzytkownik' => $this->uzytkownik,
            'uprawnienie' => $this->uprawnienie,
            'dzialanie' => $this->dzialanie,
            'numer_naboru' => $this->numerNaboru,
        );
    }

    /**
     * Określa czy obiekt zawiera poprawne dane.
     *
     * @return bool
     */
    public function isValid()
    {
        $emptyData =
            empty($this->wniosek) ||
            empty($this->uzytkownik) ||
            empty($this->uprawnienie) ||
            empty($this->dzialanie) ||
            empty($this->numerNaboru)
        ;

        return $emptyData ? false : true;
    }

    /**
     * Czyści parametry wejściowe.
     *
     * @param mixed $input
     *
     * @return string
     */
    private function sanitize($input) {
        return trim((string) $input);
    }
}
