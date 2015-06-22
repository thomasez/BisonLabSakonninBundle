<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
            ->add('callbackFunction')
            ->add('callbackType')
            ->add('forwardFunction')
            ->add('forwardType')
            ->add('parent', 'entity',
                array(
                    'label' => 'Group',
                    'placeholder' => 'Choose a Group (Or create one if not)',
                    'required' => true,
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\MessageType'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bisonlab_sakonninbundle_messagetype';
    }
}
