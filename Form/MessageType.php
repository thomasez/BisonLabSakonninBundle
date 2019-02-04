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
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\UserBundle\Form\Type\UsernameFormType;

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
            ->add('subject', TextType::class, array('label' => "Subject:", 'required' => true, "attr" => array("size" => "40")));
        $type_choices = array();
        if (!$options['data']->getMessageType()) {
            $builder->add('message_type', EntityType::class,
                array(
                    'label' => 'Type',
                    'placeholder' => 'Choose a Type',
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
                        'label' => 'Type',
                        'placeholder' => 'Choose a Type',
                        'required' => true,
                        'class' => 'BisonLabSakonninBundle:MessageType',
                        'choices' => $type_choices,
                    ));
            }
        }
        if ($options['data']->getToType()) {
            $builder
            ->add('to_type', HiddenType::class, array('required' => false));
        } else {
            $builder
                ->add('to_type', ChoiceType::class,
                    array('choices' => ExternalEntityConfig::getAddressTypesAsChoices()));
        }
        if ($options['data']->getTo()) {
            $builder
            ->add('to', UsernameFormType::class, array('label' => "To:", 'required' => false));
        } else {
            $builder
                ->add('to', HiddenType::class, array('required' => false));
        }
        $builder
            ->add('in_reply_to', HiddenType::class, array('required' => false))
            ->add('state', ChoiceType::class, array('choices' => array_combine(Message::getStates(), Message::getStates())))
            ->add('expire_at', DateType::class, array(
                'label' => "Expire at",
                'required' => false,
                'widget' => "single_text"))
            ->add('body', TextareaType::class, array('label' => "Content", 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'edit' => false,
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
