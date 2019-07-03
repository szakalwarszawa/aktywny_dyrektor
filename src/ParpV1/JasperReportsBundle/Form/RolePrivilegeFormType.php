<?php declare(strict_types=1);

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

/**
 * Formularz RolePrivilegeFormType
 */
class RolePrivilegeFormType extends AbstractType
{
    /**
     * @see AbstractType
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $usedRoles = $options['entity_manager']
            ->getRepository(RolePrivilege::class)
            ->findUsedRoles()
        ;

        $formData = $builder->getData();

        if (null !== $formData->getRole()) {
            $currentEditRoleId = $formData
                ->getRole()
                ->getId()
            ;
            $currentEditIndex = array_search($currentEditRoleId, $usedRoles);
            if ($currentEditIndex) {
                unset($usedRoles[$currentEditIndex]);
            }
        }

        $builder
            ->add('role', EntityType::class, [
                'class' => AclRole::class,
                'choice_label' => 'name',
                'label' => 'Rola użytkownika',
                'choice_attr' => function ($choice, $key, $value) use ($usedRoles) {
                    if (in_array($value, $usedRoles)) {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                },
            ])
            ->add('paths', EntityType::class, [
                'label' => 'Wybierz raporty lub/i foldery',
                'class' => Path::class,
                'choice_label' => function ($path) {
                    $isRepository = $path->isRepository();
                    $repositoryInfo = $isRepository? '[Folder] ': '';

                    return $repositoryInfo . $path->getUrl();
                },
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Wyślij',
            ])
        ;
    }

    /**
     * @see AbstractType
     */
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
