<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;

class PmType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class, array('label' => "Subject:", 'required' => true, "attr" => array("size" => "40")))
            ->add('to', TextType::class, array('label' => "To:", 'required' => false, "attr" => array("size" => "40")))
            ->add('in_reply_to', HiddenType::class, array('required' => false))
            ->add('body', TextareaType::class, array('label' => "Message content", 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\Message'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'message_data';
    }
}