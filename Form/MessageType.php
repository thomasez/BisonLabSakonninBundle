<?php

namespace BisonLab\SakonninBundle\Form;

use Doctrine\ORM\EntityRepository;
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
use BisonLab\SakonninBundle\Entity\Message;

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
            ->add('to', TextType::class, array('label' => "To:", 'required' => false, "attr" => array("size" => "40")))
            ->add('to_type', ChoiceType::class, array('choices' => ExternalEntityConfig::getAddressTypesAsChoices()))
            ->add('in_reply_to', HiddenType::class, array('required' => false))
            ->add('state', ChoiceType::class, array('choices' => array_combine(Message::getStates(), Message::getStates())))
            ->add('body', TextareaType::class, array('label' => "Content", 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
        $type_choices = array();
        if (!$options['data']->getMessageType()) {
            $builder->add('message_type', EntityType::class,
                array(
                    'label' => 'Type',
                    'placeholder' => 'Choose a Message Type',
                    'required' => true,
                    'class' => 'BisonLabSakonninBundle:MessageType',
                    'query_builder' => function(EntityRepository $er) {
                     return $er->createQueryBuilder('m')
                         ->where('m.parent is not null')
                         ->orderBy('m.parent, m.name', 'ASC');
                        },
                ));
        } else {
            if (count($options['data']->getMessageType()->getChildren()) > 0) {
                $type_choices = $options['data']->getMessageType()->getChildren();
            } else { 
                $type_choices = array($options['data']->getMessageType());
            }
            // Todo: Add default type.
            if (count($type_choices) == 1) {
                $builder->add('message_type', EntityType::class,
                    array(
                        'attr' => array('class' => 'hidden'),
                        'label' => false,
                        'required' => true,
                        'class' => 'BisonLabSakonninBundle:MessageType',
                        'choices' => $type_choices,
                    ));
            } else {
                $builder->add('message_type', EntityType::class,
                    array(
                        'label' => 'Message type',
                        'placeholder' => 'Choose a Message Type',
                        'required' => true,
                        'class' => 'BisonLabSakonninBundle:MessageType',
                        'choices' => $type_choices,
                    ));
            }
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
