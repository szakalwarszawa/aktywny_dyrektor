<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\Constants;

class Attributes
{
    /**
     * @var string
     */
    const LOGIN = 'samaccountname';

    /**
     * @var string
     */
    const IMIE_NAZWISKO = 'name';

    /**
     * @var string
     */
    const IMIE = 'givenName';

    /**
     * @var string
     */
    const NAZWISKO = 'sn';

    /**
     * @var string
     */
    const LOGIN_DOMENOWY = 'userPrincipalName';

    /**
     * @var string
     */
    const NAZWA_WYSWIETLANA = 'displayName';

    /**
     * @var string
     */
    const EMAIL = 'email';

    /**
     * @var string
     */
    const STANOWISKO = 'title';

    /**
     * @var string
     */
    const DEPARTAMENT_NAZWA = 'department';

    /**
     * @var string
     */
    const DEPARTAMENT_SKROT = 'description';

    /**
     * @var string
     */
    const SEKCJA_SKROT = 'division';

    /**
     * @var string
     */
    const SEKCJA_NAZWA = 'info';

    /**
     * @var string
     */
    const PRZELOZONY = 'manager';

    /**
     * @var string
     */
    const GRUPY_AD = 'memberOf';

    /**
     * @var string
     */
    const INICJALY = 'initials';

    /**
     * @var string
     */
    const WYGASA = 'accountExpires';

    /**
     * @var string
     */
    const WYLACZONE = 'isDisabled';

    /**
     * @var string
     */
    const POWOD_WYLACZENIA = 'disableDescription';

    /**
     * @var string
     */
    const AD_STRING = 'distinguishedname';

    /**
     * @var string
     */
    const CN_AD_STRING = 'cn';

    /**
     * @var string
     */
    const USER_ACCOUNT_CONTROL = 'useraccountcontrol';

    /**
     * Atrybut używany do składowania jakiejś opcjonalnej wartości.
     *
     * @var string
     */
    const OPTIONAL_ATTRIBUTE = 'extensionAttribute14';

    /**
     * Atrybut używany do ukrycia konta w książce adresowej
     *
     * @var string
     */
    const OUTLOOK_UKRYCIE_W_KSIAZCE = 'msExchHideFromAddressLists';
}
