<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextAreaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class MessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class, array('label' => "Subject:", 'required' => true, "attr" => array("size" => "40")))
            ->add('from', TextType::class, array('label' => "From:", 'required' => true, "attr" => array("size" => "40")))
            ->add('to', TextType::class, array('label' => "To:", 'required' => false, "attr" => array("size" => "40")))
            ->add('in_reply_to', HiddenType::class, array('required' => false))
            ->add('body', TextareaType::class, array('label' => "Message content", 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
        $type_choices = array();
        // Bytte til en streit Choices, med navn.
        if (!$options['data']->getMessageType()) {
            $builder->add('message_type', EntityType::class,
                array(
                    'label' => 'Group',
                    'placeholder' => 'Choose a Message Type',
                    'required' => true,
                    'choices_as_values' => true,
                    'class' => 'BisonLabSakonninBundle:MessageType',
                ));
        } else {
            if (count($options['data']->getMessageType()->getChildren()) > 0) {
                $type_choices = $options['data']->getMessageType()->getChildren();
            } else { 
                $type_choices = array($options['data']->getMessageType());
            }
            $builder->add('message_type', EntityType::class,
                array(
                    'label' => 'Group',
                    'placeholder' => 'Choose a Message Type',
                    'required' => true,
                    'class' => 'BisonLabSakonninBundle:MessageType',
                    'choices' => $type_choices,
                ));
        }
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
