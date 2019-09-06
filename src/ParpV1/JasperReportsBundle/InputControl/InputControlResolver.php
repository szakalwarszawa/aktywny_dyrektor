<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\InputControl;

use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\JasperReportsBundle\Fetch\JasperFetch;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\UnexpectedResultException;
use ParpV1\JasperReportsBundle\InputControl\Validator;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Klasa InputControlResolver rozszerzająca OptionsResolver
 * Opcje wejściowe raportu są pobierane bezpośrednio z jasper.
 */
class InputControlResolver extends OptionsResolver
{
    private $jasperFetch;

    public function __construct(JasperFetch $jasperFetch)
    {
        $this->jasperFetch = $jasperFetch;
    }

    /**
     * Na podstawie klucze 'report_uri' pobiera z jasper opcje
     * tego raportu i ustawia niezbędne wartości wejściowe.
     *
     * @param Request $request
     *
     * @return array
     * @return null gdy raport nie posiada opcji wejściowych
     * @return bool gdy wprowadzona wartość jest nieprawidłowa
     */
    public function resolveFormPostRequest(Request $request)
    {
        $requestPostData = current($request->request->all());

        if (!isset($requestPostData['report_uri'])) {
            throw new UnexpectedResultException('Oczekiwano klucza `report_uri`');
        }

        $jasperInputControl = $this
            ->jasperFetch
            ->getReportOptions($requestPostData['report_uri'])
        ;

        if (null === $jasperInputControl) {
            return null;
        }

        foreach ($jasperInputControl as $inputControl) {
            $this
                ->setRequired($inputControl->id)
                ->setAllowedTypes($inputControl->id, ['string', 'int'])
            ;
        }

        foreach ($requestPostData as $key => $postElement) {
            if (!$this->isDefined($key)) {
                unset($requestPostData[$key]);
            }
        }

        return $this->resolve($requestPostData);
    }
}
