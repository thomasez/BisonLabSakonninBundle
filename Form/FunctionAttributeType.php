<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FunctionAttributeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('key')
            ->add('value')
        ;
    }
    
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'function_attribute';
    }
}
