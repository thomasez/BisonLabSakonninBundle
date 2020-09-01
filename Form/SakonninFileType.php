<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

class SakonninFileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description');
        if (isset($options['data']) && $options['data']->getFileType()) {
            $builder->add('file_type', HiddenType::class);
        } else {
            $builder->add('file_type', ChoiceType::class, array(
                'required' => false,
                'choices' => ExternalEntityConfig::getFileTypesAsChoices(),
                'placeholder' => 'Will be guessed'));
        }
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
        return 'sakonninfile';
    }
}
