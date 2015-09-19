<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', 'text', array('label' => "Subject:", 'required' => true, "attr" => array("size" => "40")))
            ->add('from', 'text', array('label' => "From:", 'required' => true, "attr" => array("size" => "40")))
            ->add('to', 'text', array('label' => "To:", 'required' => false, "attr" => array("size" => "40")))
            ->add('in_reply_to', 'hidden', array('required' => false))
            ->add('body', 'textarea', array('label' => "Message content", 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
        $type_choices = array();
        // Bytte til en streit Choices, med navn.
        if (!$options['data']->getMessageType()) {
            $builder->add('message_type', 'entity',
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
            $builder->add('message_type', 'entity',
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\Message'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'message_data';
    }
}
