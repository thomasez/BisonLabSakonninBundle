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

use BisonLab\SakonninBundle\Lib\ExternalEntityConfig;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType as MessageTypeEntity;

class MessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type_choices = array();
        if (!$options['data']->getMessageType()) {
            $builder
            ->add('subject', TextType::class, array('label' => "Subject:", 'required' => false, "attr" => array("size" => "40")))
            ->add('message_type', EntityType::class,
                array(
                    'label' => 'Type',
                    'placeholder' => 'Choose a Type',
                    'required' => true,
                    'class' => MessageTypeEntity::class,
                    'query_builder' => function(EntityRepository $er) {
                     return $er->createQueryBuilder('m')
                         ->where('m.parent is not null')
                         ->orderBy('m.parent, m.name', 'ASC');
                        },
                ));
        } else {
            // We have a type.
            $message_type = $options['data']->getMessageType();

            if ($message_type->getBaseType() == "NOTE")
                $builder->add('subject', TextType::class, array('label' => "Subject:", 'required' => false, "attr" => array("size" => "40")));
            else
                $builder->add('subject', TextType::class, array('label' => "Subject:", 'required' => false, "attr" => array("size" => "40")));

            if (count($message_type->getChildren()) > 0) {
                $type_choices = $message_type->getChildren();
            } elseif ($mt_parent = $message_type->getparent()) { 
                $type_choices = $mt_parent->getChildren();
            } else { 
                $type_choices = array($message_type);
            }
            // Todo: Add default type.
            if (count($type_choices) == 1) {
                $builder->add('message_type', EntityType::class,
                    array(
                        'attr' => array('class' => 'd-none'),
                        'label' => false,
                        'required' => true,
                        'class' => MessageTypeEntity::class,
                        'choices' => $type_choices,
                    ));
            } else {
                $builder->add('message_type', EntityType::class,
                    array(
                        'label' => 'Type',
                        'placeholder' => 'Choose a Type',
                        'required' => true,
                        'class' => MessageTypeEntity::class,
                        'choices' => $type_choices,
                    ));
            }
        }
        if ($options['data']->getTo()) {
            $builder
            ->add('to', TextType::class, array('label' => "To:", 'required' => false));
        } else {
            $builder
                ->add('to', HiddenType::class, array('required' => false));
        }
        if ($in_reply_to = $options['data']->getInReplyTo()) {
            $builder->add('in_reply_to', EntityType::class,
                array(
                    'label' => 'Type',
                    'required' => true,
                    'class' => Message::class,
                    'choices' => [$in_reply_to],
                ));
        } else {
            $builder ->add('in_reply_to', HiddenType::class,
                    array('required' => false));
        }

        $builder
            ->add('state', ChoiceType::class, array('choices' => array_combine(Message::getStates(), Message::getStates())))
            ->add('expire_at', DateType::class, array(
                'label' => "Expire at",
                'required' => false,
                'widget' => "single_text"))
            ->add('body', TextareaType::class, array('label' => false, 'required' => true, "attr" => array("cols" => "40", "rows" => 5)))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'full_edit' => false,
            'with_expire' => false,
            'data_class' => 'BisonLab\SakonninBundle\Entity\Message'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'message_data';
    }
}
