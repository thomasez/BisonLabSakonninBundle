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

class EditMessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type_choices = array();
        $message = $options['data'];
        if (!$options['no_subject'] && $message->getMessageType()->getBaseType() != "CHECK")
            $builder->add('subject', TextType::class, array('label' => "Subject:", 'required' => false, "attr" => array("size" => "30")));

        $builder->add('body', TextareaType::class, array('label' => "Content", 'required' => true, "attr" => array("cols" => "30", "rows" => 5)));

        if ($options['with_expire'] ?? false) {
            $builder->add('expire_at', DateType::class, array(
                'label' => "Expire at",
                'required' => false,
                'widget' => "single_text"));
        }
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'with_expire' => false,
            'no_subject' => false,
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
