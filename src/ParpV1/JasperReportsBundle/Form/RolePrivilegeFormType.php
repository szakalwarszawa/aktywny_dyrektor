<?php

namespace ParpV1\JasperReportsBundle\Form;

use ParpV1\JasperReportsBundle\Entity\RolePrivilege;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use ParpV1\MainBundle\Entity\AclRole;
use ParpV1\JasperReportsBundle\Entity\Path;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManager;

class RolePrivilegeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $usedRoles = $options['entity_manager']
            ->getRepository(RolePrivilege::class)
            ->findUsedRoles()
        ;
        $builder
            ->add('role', EntityType::class, [
                'class' => AclRole::class,
                'choice_attr' => function($choice, $key, $value) use ($usedRoles) {
                    if (in_array($value, $usedRoles)) {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                },
            ])
            ->add('paths', EntityType::class, [
                'class' => Path::class,
                'choice_label' => function ($path) {
                    $isRepository = $path->isRepository();
                    $repositoryInfo = $isRepository? '[Repozytorium] ': '';

                    return $repositoryInfo . $path->getUrl();
                },
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => RolePrivilege::class,
            ])
            ->setRequired('entity_manager')
            ->setAllowedTypes('entity_manager', EntityManager::class)
        ;
    }
}
