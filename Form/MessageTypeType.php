<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

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
            ->add('expunge_days', 'Symfony\Component\Form\Extension\Core\Type\NumberType', array('label' => "Expunge, in days. 0 means never delete messages. Only applicable on types, not groups."))
            ->add('parent', 'Symfony\Bridge\Doctrine\Form\Type\EntityType',
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
