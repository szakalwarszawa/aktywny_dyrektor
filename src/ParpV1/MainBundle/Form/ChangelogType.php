<?php

namespace ParpV1\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChangelogType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('createdAt')
            ->add('deletedAt')
            ->add('samaccountname', TextType::class)
            ->add('wersja', TextType::class)
            ->add('dodatkowyTytul', TextType::class)
            ->add('opis', TextareaType::class)
            ->add('opublikowany')
        ;
    //     ->add('url', TextType::class, [
    //         'label' => 'Adres raportu lub folderu',
    //         'required' => true,
    //     ])
    //     ->add('isRepository', CheckboxType::class, [
    //         'required' => false,
    //         'label' => 'Folder zawierający raporty',
    //     ])
    //     ->add('title', TextType::class, [
    //         'required' => false,
    //         'label' => 'Tytuł raportu'
    //     ])
    //     ->add('submit', SubmitType::class, [
    //         'label' => 'Wyślij'
    //     ])
    // ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ParpV1\MainBundle\Entity\Changelog'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'parpv1_mainbundle_changelog';
    }
}
