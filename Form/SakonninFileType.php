<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

class SakonninFileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', VichFileType::class, [
                'required' => true,
                'allow_delete' => true, 
        ])
            ->add('file_type', ChoiceType::class, array(
                'required' => false,
                'choices' => ExternalEntityConfig::getFileTypesAsChoices(),
                'placeholder' => 'Will be guessed'))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\SakonninFile'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bisonlab_sakonninbundle_file';
    }


}
