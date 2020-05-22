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

class CheckType extends AbstractType
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
                        'attr' => array('class' => 'd-none'),
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
        $builder
            ->add('state', HiddenType::class)
            ->add('expire_at', DateType::class, array(
                'label' => "Expire at",
                'required' => false,
                'widget' => "single_text"))
            ->add('body', TextType::class, array('label' => "Content", 'required' => true))
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
