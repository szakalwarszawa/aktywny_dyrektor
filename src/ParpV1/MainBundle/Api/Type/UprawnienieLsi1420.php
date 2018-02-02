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
     * Nazwa operacji nadania uprawnienia.
     *
     * @var string
     */
    const GRANT_PRIVILAGE = 'grant';

    /**
     * Nazwa operacji odebrania uprawnienia.
     *
     * @var string
     */
    const REVOKE_PRIVILAGE = 'revoke';

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
     * @var boolean
     *
     * @JMS\Expose
     * @JMS\Type("boolean")
     */
    public $bezterminowo;

    /**
     * @var \DateTime
     *
     * @JMS\Expose
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    public $aktywneDo;

    /**
     * @var string
     */
    public $operacja;

    /**
     * Konstruktor.
     *
     * @param string $wniosek
     * @param string $uzytkownik
     * @param string $uprawnienie
     * @param string $dzialanie
     * @param string $numerNaboru
     * @param string $operacja
     * @param bool $bezterminowo
     * @param \DateTime $aktywneDo
     */
    public function __construct(
        $wniosek,
        $uzytkownik,
        $uprawnienie,
        $dzialanie,
        $numerNaboru,
        $operacja = self::GRANT_PRIVILAGE,
        $bezterminowo = true,
        $aktywneDo = null
    ) {
        $this->wniosek = $this->sanitizeStr($wniosek);
        $this->uzytkownik = $this->sanitizeStr($uzytkownik);
        $this->uprawnienie = $this->sanitizeStr($uprawnienie);
        $this->dzialanie = $this->sanitizeStr($dzialanie);
        $this->numerNaboru = $this->sanitizeStr($numerNaboru);
        $this->operacja = $this->sanitizeStr($operacja);
        $this->bezterminowo = $this->sanitizeBool($bezterminowo);

        $this->setAktywneDo($aktywneDo);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $serialize = array(
            'wniosek' => $this->wniosek,
            'uzytkownik' => $this->uzytkownik,
            'uprawnienie' => $this->uprawnienie,
            'dzialanie' => $this->dzialanie,
            'numer_naboru' => $this->numerNaboru,
            'operacja' => $this->operacja,
        );

        if ($this->operacja === self::GRANT_PRIVILAGE) {
            $serialize = array_merge($serialize, array(
                'bezterminowo' => $this->$bezterminowo,
                'aktywne_do' => $this->$aktywneDo,
            ));
        }

        return $serialize;
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

        $unknownOperacja = !in_array($this->operacja, array(
            self::GRANT_PRIVILAGE,
            self::REVOKE_PRIVILAGE,
        ));

        $inconsistentTermin = (
            ($this->dzialanie === self::GRANT_PRIVILAGE) &&
            in_array($this->bezterminowo, array(false, null)) &&
            (! $this->aktywneDo instanceof \DateTime)
        );

        $isValid = ($emptyData || $unknownOperacja || $inconsistentTermin) ? false : true;

        return $isValid;
    }

    /**
     * Czyści parametry wejściowe dla ciągów tekstowych.
     *
     * @param mixed $input
     *
     * @return string
     */
    private function sanitizeStr($input)
    {
        return trim((string) $input);
    }

    /**
     * Czyści parametry wejściowe dla wartości logicznych.
     *
     * @param mixed $input
     *
     * @return bool
     */
    private function sanitizeBool($input)
    {
        $inputStr = trim((string) $input);
        if (in_array($inputStr, array(0, "false", false, "NIE", "nie", "N", "n", ""))) {
            $input = false;
        } elseif (in_array($inputStr, array(1, "true", true, "TAK", "tak", "T", "t"))) {
            $input = true;
        } else {
            $input = false;
        }

        return $input;
    }

    /**
     * Mutator daty, do której ma obowiązywać uprawnienie.
     *
     * @param mixed $aktywneDo
     *
     * @return UprawnienieLsi1420
     */
    public function setAktywneDo($aktywneDo)
    {
        if ($aktywneDo instanceof \DateTime) {
            $aktywneDo->setTime(0, 0, 0);
            $this->aktywneDo = $aktywneDo;
            $this->bezterminowo = false;
        } else {
            $this->aktywneDo = null;
            $this->bezterminowo = true;
        }

        return $this;
    }
}
