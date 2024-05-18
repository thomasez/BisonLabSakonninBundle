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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('to', TextType::class, array('label' => "To:", 'required' => true))
            // ->add('to', TextType::class, array('label' => "To:", 'required' => true, "attr" => array("size" => "30")))
            ->add('in_reply_to', HiddenType::class, array('required' => false))
            ->add('body', TextareaType::class, array('label' => "Message content", 'required' => true, "attr" => array("cols" => "30", "rows" => 5)))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\Message',
            'allow_extra_fields' => true,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
