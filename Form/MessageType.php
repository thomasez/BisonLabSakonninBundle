<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MessageType extends AbstractType
{
/*
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }
        */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
/* In case of us wanting From being forcefully set, from:
 http://symfony.com/doc/current/cookbook/form/dynamic_form_modification.html
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user) {
            throw new \LogicException(
                'The FriendMessageFormType cannot be used without an authenticated user!'
            );
        }
        */
        
        $builder
            ->add('subject', 'text', array('label' => "Subject:", 'required' => true, "attr" => array("size" => "40")))
            ->add('from', 'text', array('label' => "From:", 'required' => true, "attr" => array("size" => "40")))
            ->add('to', 'text', array('label' => "To:", 'required' => false, "attr" => array("size" => "40")))
            ->add('body', 'textarea', array('label' => "Message content", 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
        $type_choices = array();
        if (!$options['data']->getMessageType()) {
            $builder->add('message_type', 'entity',
                array(
                    'label' => 'Group',
                    'placeholder' => 'Choose a Message Type',
                    'required' => true,
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
