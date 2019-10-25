<?php

declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ParpV1\JasperReportsBundle\Constants\ReportFormat;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use ParpV1\JasperReportsBundle\Fetch\JasperFetch;
use Jaspersoft\Dto\Report\InputControl;
use ParpV1\JasperReportsBundle\Constants\RaportInputParameters;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use ParpV1\JasperReportsBundle\InputControl\Validator;
use ParpV1\JasperReportsBundle\Exception\InvalidOptionKeyOrValueException;

/**
 * Formularz PathFormType
 * Obsługuje format wydruku raportu oraz opcjonalnie parametry wejściowe.
 */
class GenerateReportType extends AbstractType
{
    /**
     * Ciąg tekstowy zwracany przez jasper jako NULL.
     *
     * @var string
     */
    const JASPER_NULL_VALUE = '~NULL~';

    /**
     * @var JasperFetch
     */
    private $jasperFetch;

    /**
     * Konsturktor
     *
     * @param JasperFetch $jasperFetch
     */
    public function __construct(JasperFetch $jasperFetch)
    {
        $this->jasperFetch = $jasperFetch;
    }
    /**
     * @see AbstractType
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $inputOptions = $this
            ->jasperFetch
            ->getReportOptions($options['report_uri'])
        ;

        if (null !== $inputOptions) {
            $validator = new Validator();
            foreach ($inputOptions as $inputOption) {
                if ($inputOption instanceof InputControl) {
                    if (!$validator::key($inputOption->id)) {
                        throw new InvalidOptionKeyOrValueException(sprintf('Niepoprawny klucz parametru ("%s")', $inputOption->id));
                    }
                    if ($inputOption->id !== RaportInputParameters::LOGIN_PARAMETER) {
                        $builder->add($inputOption->id, TextType::class, [
                            'data' => self::JASPER_NULL_VALUE !== $inputOption->value ? $inputOption->value : null,
                        ]);
                    }
                }
            }
        }

        $builder
            ->add('format', ChoiceType::class, [
                'label' => 'Format raportu',
                'choices' => ReportFormat::getFormats(true),
            ])
            ->add('report_uri', HiddenType::class, [
                'data' => $options['report_uri']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Generuj',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
        ;

        $builder->setMethod('POST');
    }

    /**
     * @see AbstractType
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => null,
            ])
            ->setRequired('report_uri')
            ->setAllowedTypes('report_uri', 'string')
            ;
    }
}
