<?php

namespace BisonLab\SakonninBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Norzechowicz\AceEditorBundle\Form\Extension\AceEditor\Type\AceEditorType;

class SakonninTemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array('label' => "Name", 'required' => true, "attr" => array("size" => "50")))
            ->add('description')
            ->add('lang_code', TextType::class, array('label' => "Language code:", 'required' => true, "attr" => array("size" => "7")))
            ->add('template', AceEditorType::class, array(
                'label' => 'Template',
                'required' => false,
                'wrapper_attr' => array(), // aceeditor wrapper html attributes.
                // 'width' => 700,
                'width' => '100%',
                'height' => 200,
                'font_size' => 12,
                'mode' => 'ace/mode/twig', // every single default mode must have ace/mode/* prefix
                'theme' => 'ace/theme/tomorrow', // every single default theme must have ace/theme/* prefix
                'tab_size' => null,
                'read_only' => null,
                'use_soft_tabs' => null,
                'use_wrap_mode' => true,
                'show_print_margin' => null,
                'show_invisibles' => null,
                'highlight_active_line' => null,
                'options_enable_basic_autocompletion' => true,
                'options_enable_live_autocompletion' => true,
                'options_enable_snippets' => false,
                'keyboard_handler' => null
              ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BisonLab\SakonninBundle\Entity\SakonninTemplate'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sakonnintemplate';
    }
}
