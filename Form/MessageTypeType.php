<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use BisonLab\SakonninBundle\Entity\MessageType;

class MessageTypeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('security_model', ChoiceType::class, array(
                'placeholder' => "None",
                'choices' => MessageType::getSecurityModelsAsChoices(),
                ))
            ->add('expunge_days', NumberType::class, array('label' => "Expunge, in days. 0 means never delete messages.<br>Only applicable on types, not groups.", "attr" => array("size" => "3")))
            ->add('expunge_method', ChoiceType::class, array(
                'label' => 'What to do when the expunge is triggered.',
                'choices' => MessageType::getExpungeMethodsAsChoices(),
                ))
            ->add('expire_method', ChoiceType::class, array(
                'label' => 'What to do at the expire at date.',
                'choices' => MessageType::getExpungeMethodsAsChoices(),
                ))
            ->add('parent', EntityType::class,
                array(
                    'label' => 'Group',
                    'placeholder' => 'Choose a Group',
                    'required' => false,
                    'class' => 'BisonLabSakonninBundle:MessageType',
                    'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('mt')
                     ->where('mt.parent is null')
                     ->orderBy('mt.name', 'ASC');
                    },
                ))
            ->add('sakonnin_template', EntityType::class,
                array(
                    'label' => 'Template',
                    'placeholder' => 'Eventual template',
                    'required' => false,
                    'class' => 'BisonLabSakonninBundle:SakonninTemplate'
                ));
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\MessageType'
        ));
    }
}
